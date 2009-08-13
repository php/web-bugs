<?php

/**
 * User interface for viewing and editing bug details
 */

// Start session 
session_start();

// Handle preview
if ($_REQUEST['id'] == 'preview') {
	$bug_id = 'PREVIEW';
	$bug = $_SESSION['bug_preview'];
	$bug['submitted'] = time();
	$bug['modified'] = null;
	$bug['votes'] = 0;
	$bug['assign'] = '';
	
	if (!$bug) {
		redirect('index.php');
		exit;
	}
} else {
	// Bailout early if no/invalid bug id is passed
	if (empty($_REQUEST['id']) || !((int) $_REQUEST['id'])) {
		redirect('index.php');
		exit;
	} else {
		$bug_id = (int) $_REQUEST['id'];
	}
}

// Init common variables
$errors = array();

// Obtain common includes
require_once '../include/prepend.inc';

// Set pseudo_pkgs array
$pseudo_pkgs = get_pseudo_packages($site, false); // false == no read-only packages included

// Set edit mode
$edit = isset($_REQUEST['edit']) ? (int) $_REQUEST['edit'] : 0;

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $is_trusted_developer);

// Handle unsubscription
if (isset($_GET['unsubscribe'])) {
	$unsubcribe = (int) $_GET['unsubscribe'];

	$hash = isset($_GET['t']) ? $_GET['t'] : false;

	if (!$hash) {
		redirect("bug.php?id={$bug_id}");
		exit;
	}
	unsubscribe($bug_id, $hash);
	$_GET['thanks'] = 9;
}

// Subscription / Unsubscription
if (isset($_POST['subscribe_to_bug']) || isset($_POST['unsubscribe_to_bug'])) {

	/**
	 * Check if session answer is set, then compare
	 * it with the post captcha value. If it's not
	 * the same, then it's an incorrect password.
	 */
	if (isset($_SESSION['answer']) && strlen(trim($_SESSION['answer'])) > 0) {
		if ($_POST['captcha'] != $_SESSION['answer']) {
			$errors[] = 'Incorrect Captcha';
		}
	}

	if (empty($errors)) {
		if ($logged_in && $auth_user->registered && !empty($auth_user->email)) {
			$email = $auth_user->email;
		} else {
			$email = isset($_POST['in']['commentemail']) ? $_POST['in']['commentemail'] : '';
		}
		if ($email == '' || !is_valid_email($email)) {
			$errors[] = 'You must provide a valid email address.';
		} else {
			// Unsubscribe
			if (isset($_POST['unsubscribe_to_bug'])) {
				/* Generate the hash */
				unsubscribe_hash($bug_id, $email);
				$thanks = 8;
			}
			else // Subscribe
			{
				$dbh->prepare('REPLACE INTO bugdb_subscribe SET bug_id = ?, email = ?')->execute(array($bug_id, $email));
				$thanks = 7;
			}
			redirect("bug.php?id={$bug_id}&thanks={$thanks}");
			exit;
		}
	}
	// If we get here, display errors
	response_header('Error in subscription');
	display_bug_error($errors);
	response_footer();
	exit;
}

// Delete comment
if ($edit == 1 && $is_trusted_developer && isset($_GET['delete_comment'])) {
	$delete_comment = (int) $_GET['delete_comment'];
	$addon = '';

	if ($delete_comment) {
		delete_comment($bug_id, $delete_comment);
		$addon = '&thanks=1';
	}
	redirect("bug.php?id=$bug_id&edit=1$addon");
	exit;
}

// captcha is not necessary if the user is logged in
if ($logged_in) {
	unset($_SESSION['answer']);
} else {
	require_once 'Text/CAPTCHA/Numeral.php';
	$numeralCaptcha = new Text_CAPTCHA_Numeral();
}

$trytoforce = isset($_POST['trytoforce']) ? (int) $_POST['trytoforce'] : 0;

// fetch info about the bug into $bug
if (!isset($bug)) {
	$bug = bugs_get_bug($bug_id, true);
}

// DB error
if (is_object($bug)) {
	response_header('DB error');
	display_bug_error($bug);
	response_footer();
	exit;
}

// Bug not found with passed id
if (!$bug) {
	response_header('No Such Bug');
	display_bug_error("No such bug #{$bug_id}");
	response_footer();
	exit;
}

// Handle any updates, displaying errors if there were any
$previous = $current = array();

/* Fetch RESOLVE_REASONS array */
list($RESOLVE_REASONS, $FIX_VARIATIONS) = get_resolve_reasons($site);

if (isset($_POST['ncomment']) && !isset($_POST['preview']) && $edit == 3) {
	// Submission of additional comment by others

	/**
	 * Check if session answer is set, then compare
	 * it with the post captcha value. If it's not
	 * the same, then it's an incorrect password.
	 */
	if (isset($_SESSION['answer']) && strlen(trim($_SESSION['answer'])) > 0) {
		if ($_POST['captcha'] != $_SESSION['answer']) {
			$errors[] = 'Incorrect Captcha';
		}
	}

	// Defaults to '' if not logged in
	$_POST['in']['handle'] = $auth_user->handle;

	$ncomment = trim($_POST['ncomment']);
	if (!$ncomment) {
		$errors[] = 'You must provide a comment.';
	}

	if (!$errors) {
		do {
			if (!$logged_in) {

				if (!is_valid_email($_POST['in']['commentemail'])) {
					$errors[] = 'You must provide a valid email address.';
					response_header('Add Comment - Problems');
					break; // skip bug comment addition
				}

				$_POST['in']['name'] = '';
			} else {
				$_POST['in']['commentemail'] = $auth_user->email;
				$_POST['in']['handle'] = $auth_user->handle;
				$_POST['in']['name'] = $auth_user->name;
			}

			$res = $dbh->prepare('
				INSERT INTO bugdb_comments (bug, email, comment, reporter_name, ts)
				VALUES (?, ?, ?, ?, NOW())
			')->execute(array(
				$bug_id,
				$_POST['in']['commentemail'],
				$ncomment,
				$_POST['in']['name'],
			));
		} while (false);

		if (isset($auth_user) && $auth_user) {
			$from = $auth_user->email;
		} else {
			$from = '';
		}
	} else {
		$from = '';
	}
} elseif (isset($_POST['ncomment']) && isset($_POST['preview']) && $edit == 3) {
	$ncomment = trim($_POST['ncomment']);
	$from = spam_protect($_POST['in']['commentemail']);
	
} elseif (isset($_POST['in']) && !isset($_POST['preview']) && $edit == 2) {
	// Edits submitted by original reporter for old bugs

	if (!verify_bug_passwd($bug_id, $pw)) {
		$errors[] = 'The password you supplied was incorrect.';
	}

	$ncomment = trim($_POST['ncomment']);
	if (!$ncomment) {
		$errors[] = 'You must provide a comment.';
	}

	/* check that they aren't being bad and setting a status they
	   aren't allowed to (oh, the horrors.) */
	if ($_POST['in']['status'] != $bug['status'] && $state_types[$_POST['in']['status']] != 2) {
		$errors[] = 'You aren\'t allowed to change a bug to that state.';
	}

	/* check that they aren't changing the mail to a php.net address
	   (gosh, somebody might be fooled!) */
	if (preg_match('/^(.+)@php\.net/i', $_POST['in']['email'], $m)) {
		if ($user != $m[1] || $logged_in != 'developer') {
			$errors[] = 'You have to be logged in as a developer to use your php.net email address.';
			$errors[] = 'Tip: log in via another browser window then resubmit the form in this window.';
		}
	}

	if (!empty($_POST['in']['email']) &&
		$bug['email'] != $_POST['in']['email']
	) {
		$from = $_POST['in']['email'];
	} else {
		$from = $bug['email'];
	}

	if (!$errors && !($errors = incoming_details_are_valid($_POST['in'], false))) {
		$dbh->prepare("
			UPDATE bugdb
			SET
				sdesc = ?,
				status = ?,
				package_name = ?,
				bug_type = ?,
				php_version = ?,
				php_os = ?,
				email = ?,
				ts2 = NOW()
			WHERE id={$bug_id}
		")->execute(array(
			$_POST['in']['sdesc'],
			$_POST['in']['status'],
			$_POST['in']['package_name'],
			$_POST['in']['bug_type'],
			$_POST['in']['php_version'],
			$_POST['in']['php_os'],
			$from,
		));

		if (!empty($ncomment)) {
			$dbh->prepare("
				INSERT INTO bugdb_comments (bug, email, comment, ts)
				VALUES ({$bug_id}, ?, ?, NOW())
			")->execute(array(
				$from, $ncomment,
			));
		}
	}
} elseif (isset($_POST['in']) && isset($_POST['preview']) && $edit == 2) {
	$ncomment = trim($_POST['ncomment']);
	$from = $_POST['in']['commentemail'];
} elseif (isset($_POST['in']) && is_array($_POST['in']) && !isset($_POST['preview']) && $edit == 1) {
	// Edits submitted by developer
	if ($logged_in != 'developer') {
		$errors[] = 'You have to login first in order to edit the bug report.';
	}
	$comment_name = $auth_user->name;
	if (empty($_POST['ncomment'])) {
		$ncomment = '';
	} else {
		$ncomment = trim($_POST['ncomment']);
	}

	/* Require comment for open bugs only */
	if ($_POST['in']['status'] == 'Bogus' && !in_array($bug['status'], array ('Bogus', 'Closed', 'Duplicate', 'No feedback', 'Wont fix')) &&
	 	strlen(trim($ncomment)) == 0
	) {
		$errors[] = "You must provide a comment when marking a bug 'Bogus'";
	} elseif (($_POST['in']['status'] == 'To be documented' && $bug['status'] != $_POST['in']['status']) ||
		(!empty($_POST['in']['resolve']) && $RESOLVE_REASONS[$_POST['in']['resolve']]['status'] == 'To be documented')
	) {
		/* Require explanation */
		if (strlen(trim($ncomment)) == 0) {
			$errors[] = "You must provide a comment to help in the feature/issue documentation";
		} else if ($bug['status'] != 'To be documented' && $bug['assign'] == $_POST['in']['assign']) {
			/*
			* Reset the assigned value when changing the status to 'To be documented',
			* as more probably the developer (which was marked as assigned) won't document
			* the fix.
			*/
			$_POST['in']['assign'] = '';
		}
		$_POST['in']['status'] = 'To be documented';
	} elseif (!empty($_POST['in']['resolve'])) {
		if (!$trytoforce && isset($RESOLVE_REASONS[$_POST['in']['resolve']]) &&
			$RESOLVE_REASONS[$_POST['in']['resolve']]['status'] == $bug['status'])
		{
			$errors[] = 'The bug is already marked "'.$bug['status'].'". (Submit again to ignore this.)';
		} elseif (!$errors)  {
			if ($_POST['in']['status'] == $bug['status']) {
				$_POST['in']['status'] = $RESOLVE_REASONS[$_POST['in']['resolve']]['status'];
			}
			if ($_POST['in']['status'] == 'Closed' && $bug['status'] == 'To be documented') {
				$reason = $FIX_VARIATIONS['fixed']['Documentation problem'];
 			} elseif (isset($FIX_VARIATIONS) && isset($FIX_VARIATIONS[$_POST['in']['resolve']][$bug['package_name']])) {
				$reason = $FIX_VARIATIONS[$_POST['in']['resolve']][$bug['package_name']];
			} else {
				$reason = isset($RESOLVE_REASONS[$_POST['in']['resolve']]) ? $RESOLVE_REASONS[$_POST['in']['resolve']]['message'] : '';
			}

			// do a replacement on @svn@ to the likely location of SVN for this package
			if ($_POST['in']['resolve'] == 'trysvn') {
				switch ($bug['package_name']) {
					case 'Documentation' :
					case 'Web Site' :
					case 'Bug System' :
					case 'PEPr' :
						$errors[] = 'Cannot use "try svn" with ' . $bug['package_name'];
						break;
					case 'PEAR' :
						$reason = str_replace('@svn@', 'pear-core', $reason);
						$ncomment = "$reason\n\n$ncomment";
						break;
					default :
						$reason = str_replace('@svn@', $bug['package_name'], $reason);
						$ncomment = "$reason\n\n$ncomment";
						break;
				}
			} else {
				$ncomment = "$reason\n\n$ncomment";
			}
		}
	}

	$from = $auth_user->email;

	if (!$errors && !($errors = incoming_details_are_valid($_POST['in']))) {
		$query = 'UPDATE bugdb SET';

		// Update email only if it's passed
		if ($bug['email'] != $_POST['in']['email'] && !empty($_POST['in']['email']))
		{
			$query .= " email='{$_POST['in']['email']}',";
		}

		if ($logged_in != 'developer') {
			// don't reset assigned status
			$_POST['in']['assign'] = $bug['assign'];
		}
		if (!empty($_POST['in']['assign']) && $_POST['in']['status'] == 'Open') {
			$status = 'Assigned';
		} elseif (empty($_POST['in']['assign']) && $_POST['in']['status'] == 'Assigned') {
			$status = 'Open';
		} else {
			$status = $_POST['in']['status'];
		}

		// Assign automatically when closed
		if ($status == 'Closed' && $_POST['in']['assign'] == '') {
			$_POST['in']['assign'] = $auth_user->handle;
		}

		$dbh->prepare($query . "
				sdesc = ?, 
				status = ?, 
				package_name = ?,
				bug_type = ?,
				assign = ?,
				php_version = ?,
				php_os = ?,
				ts2 = NOW()
			WHERE id = {$bug_id}
		")->execute(array (
			$_POST['in']['sdesc'],
			$status,
			$_POST['in']['package_name'],
			$_POST['in']['bug_type'],
			$_POST['in']['assign'],
			$_POST['in']['php_version'],
			$_POST['in']['php_os'],
		));

		$changed = bug_diff($bug, $_POST['in']);
		if (!empty($changed)) {
			$log_comment = bug_diff_render_html($changed);
		}

		if (!empty($log_comment)) {
			$dbh->prepare("
				INSERT INTO bugdb_comments (bug, email, comment, reporter_name, comment_type, ts)
				VALUES (?, ?, ?, ?, 'log', NOW())
			")->execute(array(
				$bug_id, $from, $log_comment, $comment_name,
			));
		}

		if (!empty($ncomment)) {
			$dbh->prepare("
				INSERT INTO bugdb_comments (bug, email, comment, reporter_name, comment_type, ts)
				VALUES (?, ?, ?, ?, 'comment', NOW())
			")->execute(array(
				$bug_id, $from, $ncomment, $comment_name,
			));
		}
	}
} elseif (isset($_POST['in']) && isset($_POST['preview']) && $edit == 1) {
	$ncomment = trim($_POST['ncomment']);
	$from = $auth_user->email;
} elseif (isset($_POST['in'])) {
	$errors[] = 'Invalid edit mode.';
	$ncomment = '';
} else {
	$ncomment = '';
}

if (isset($_POST['in']) && (!isset($_POST['preview']) && $ncomment || $previous != $current)) {
	if (!$errors) {
		mail_bug_updates($bug, $_POST['in'], $from, $ncomment, $edit, $bug_id, $previous, $current);
		redirect("bug.php?id=$bug_id&thanks=$edit");
		exit;
	}
}

switch (txfield('bug_type', $bug, isset($_POST['in']) ? $_POST['in'] : null))
{
	case 'Feature/Change Request':
		$bug_type = 'Request';
		break;
	case 'Documentation Problem':
		$bug_type = 'Doc Bug';
		break;
	default:
	case 'Bug':
		$bug_type = 'Bug';
		break;
}

response_header(
	"{$bug_type} #{$bug_id} :: " . htmlspecialchars($bug['sdesc']),
	($bug_id != 'PREVIEW') ? " 
	  <link rel='alternate' type='application/rss+xml' title='{$bug['package_name']} Bug #{$bug['id']} - RDF' href='rss/bug.php?id={$bug_id}' />
	  <link rel='alternate' type='application/rss+xml' title='{$bug['package_name']} Bug #{$bug['id']} - RSS 2.0' href='rss/bug.php?id={$bug_id}&format=rss2' />
	  <script type='text/javascript' src='js/util.js'></script>	
	" : ''
);

// DISPLAY BUG
$thanks = (isset($_GET['thanks'])) ? (int) $_GET['thanks'] : 0;
switch ($thanks)
{
	case 1:
	case 2:
		display_bug_success('The bug was updated successfully.');
		break;
	case 3:
		display_bug_success('Your comment was added to the bug successfully.');
		break;
	case 4:
		$bug_url = "http://{$site_url}{$basedir}/bug.php?id={$bug_id}";
		display_bug_success("
			Thank you for your help!
			If the status of the bug report you submitted changes, you will be notified.
			You may return here and check the status or update your report at any time.<br />
			The URL for your bug report is: <a href='{$bug_url}'>{$bug_url}</a>.
		");
		break;
	case 6:
		display_bug_success('Thanks for voting! Your vote should be reflected in the statistics below.');
		break;
	case 7:
		display_bug_success('Your subscribe request has been processed.');
		break;
	case 8:
		display_bug_success('Your unsubscribe request has been processed, please check your email.');
		break;
	case 9:
		display_bug_success('You have successfully unsubscribed.');
		break;

	default:
		break;
}

display_bug_error($errors);

?>
<div id="bugheader">
 <table id="details">
  <tr id="title">
   <th class="details" id="number"><a href="bug.php?id=<?php echo $bug_id, '">', $bug_type , '</a>&nbsp;#' , $bug_id; ?></th>
   <td id="summary" colspan="5"><?php echo htmlspecialchars($bug['sdesc']); ?></td>
  </tr>
  <tr id="submission">
   <th class="details">Submitted:</th>
   <td style="white-space: nowrap;"><?php echo format_date($bug['submitted']); ?></td>
   <th class="details">Modified:</th>
   <td style="white-space: nowrap;"><?php echo ($bug['modified']) ? format_date($bug['modified']) : '-'; ?></td>
   <td rowspan="5">

<?php if ($bug['votes']) { ?>
	<table id="votes">
	 <tr><th class="details">Votes:</th><td><?php echo $bug['votes'] ?></td></tr>
	 <tr><th class="details">Avg. Score:</th><td><?php printf("%.1f &plusmn; %.1f", $bug['average'], $bug['deviation']) ?></td></tr>
	 <tr><th class="details">Reproduced:</th><td><?php printf("%d of %d (%.1f%%)",$bug['reproduced'],$bug['tried'],$bug['tried']?($bug['reproduced']/$bug['tried'])*100:0) ?></td></tr>
<?php	if ($bug['reproduced']) { ?>
	 <tr><th class="details">Same Version:</th><td><?php printf("%d (%.1f%%)",$bug['samever'],($bug['samever']/$bug['reproduced'])*100) ?></td></tr>
	 <tr><th class="details">Same OS:</th><td><?php printf("%d (%.1f%%)",$bug['sameos'],($bug['sameos']/$bug['reproduced'])*100) ?></td></tr>
<?php	} ?>
	</table>
<?php } ?>

   </td>
  </tr>

  <tr id="submitter">
   <th class="details">From:</th>
   <td><?php echo spam_protect(htmlspecialchars($bug['email'])); ?></td>
   <th class="details">Assigned:</th>
   <td><?php echo htmlspecialchars($bug['assign']); ?></td>
  </tr>
  <tr id="categorization">
   <th class="details">Status:</th>
   <td><?php echo htmlspecialchars($bug['status']); ?></td>
   <th class="details">Package:</th>
   <td>
   <?php echo htmlspecialchars($bug['package_name']); ?>
   </td>
  </tr>
  <tr id="situation">
   <th class="details">PHP Version:</th>
   <td><?php echo htmlspecialchars($bug['php_version']) ?></td>
   <th class="details">OS:</th>
   <td><?php echo htmlspecialchars($bug['php_os']) ?></td>
  </tr>
 </table>
</div>

<?php if ($bug_id !== 'PREVIEW') {
	echo '<div class="controls">', "\n",
		control(0, 'View'),
		control(3, 'Add Comment'),
		control(1, 'Developer'),
		control(2, 'Edit'),
		'</div>', "\n";
?>
<div class="clear"></div>

<?php if (!$edit && canvote($thanks, $bug['status'])) { ?>
  <form id="vote" method="post" action="vote.php">
  <div class="sect">
   <fieldset>
	<legend>Have you experienced this issue?</legend>
	<div>
	 <input type="radio" id="rep-y" name="reproduced" value="1" onchange="show('canreproduce')" /> <label for="rep-y">yes</label>
	 <input type="radio" id="rep-n" name="reproduced" value="0" onchange="hide('canreproduce')" /> <label for="rep-n">no</label>
	 <input type="radio" id="rep-d" name="reproduced" value="2" onchange="hide('canreproduce')" checked="checked" /> <label for="rep-d">don't know</label>
	</div>
   </fieldset>
   <fieldset>
	<legend>Rate the importance of this bug to you:</legend>
	<div>
	 <label for="score-5">high</label>
	 <input type="radio" id="score-5" name="score" value="2" />
	 <input type="radio" id="score-4" name="score" value="1" />
	 <input type="radio" id="score-3" name="score" value="0" checked="checked" />
	 <input type="radio" id="score-2" name="score" value="-1" />
	 <input type="radio" id="score-1" name="score" value="-2" />
	 <label for="score-1">low</label>
	</div>
   </fieldset>
  </div>
  <div id="canreproduce" class="sect" style="display: none">
   <fieldset>
	<legend>Are you using the same PHP version?</legend>
	<div>
	 <input type="radio" id="ver-y" name="samever" value="1" /> <label for="ver-y">yes</label>
	 <input type="radio" id="ver-n" name="samever" value="0" checked="checked" /> <label for="ver-n">no</label>
	</div>
   </fieldset>
   <fieldset>
	<legend>Are you using the same operating system?</legend>
	<div>
	 <input type="radio" id="os-y" name="sameos" value="1" /> <label for="os-y">yes</label>
	 <input type="radio" id="os-n" name="sameos" value="0" checked="checked" /> <label for="os-n">no</label>
	</div>
   </fieldset>
  </div>
  <div id="submit" class="sect">
   <input type="hidden" name="id" value="<?php echo $bug_id?>" />
   <input type="submit" value="Vote" />
  </div>
  </form>
  <br clear="all" />
<?php } 

} // if ($bug_id != 'PREVIEW') { 

//
// FIXME! Do not wrap here either. Re-use the comment display function!
//

if (isset($_POST['preview']) && !empty($ncomment)) {
	$preview = '<div class="comment">';
	$preview .= "<strong>[" . format_date(time()) . "] ";
	$preview .= spam_protect(htmlspecialchars($from));
	$preview .= "</strong>\n<pre class=\"note\">";
	$comment = wordwrap($ncomment, 72);
	$preview .= make_ticket_links(addlinks($comment));
	$preview .= "</pre>\n";
	$preview .= '</div>';
} else {
	$preview = '';
}

if ($edit == 1 || $edit == 2) { ?>

	<form id="update" action="bug.php?id=<?php echo $bug_id; ?>&amp;edit=<?php echo $edit; ?>" method="post">

<?php if ($edit == 2) {
	   if (!isset($_POST['in']) && $pw && verify_bug_passwd($bug['id'], $pw)) { ?>
			<div class="explain">
			 Welcome back! Since you opted to store your bug's password in a
			 cookie, you can just go ahead and add more information to this
			 bug or edit the other fields.
			</div>
<?php  } else { ?>
			<div class="explain">
			<?php if (!isset($_POST['in'])) { ?>
				Welcome back! If you're the original bug submitter, here's
				where you can edit the bug or add additional notes. If this
				is not your bug, you can <a href="bug.php?id=<?php echo $bug_id; ?>&amp;edit=3">add a comment by following this link</a>.
				If this is your bug, but you forgot your password, <a href="bug-pwd-finder.php?id=<?php echo $bug_id; ?>">you can retrieve your password here</a>.
			<?php } ?>

			 <table>
			  <tr>
			   <td class="details">Passw<span class="accesskey">o</span>rd:</td>
			   <td>
				<input type="password" name="pw"
				 value="<?php echo htmlspecialchars($pw) ?>" size="10" maxlength="20"
				 accesskey="o" />
			   </td>
			   <td class="details">
				<label for="save">
				 Check to remember your password for next time:
				</label>
			   </td>
			   <td>
				<input type="checkbox" id="save" name="save" <?php echo (isset($_POST['save'])) ? ' checked="checked"' : ''; ?> />
			   </td>
			  </tr>
			 </table>
			</div>
<?php  }
	} else {
		if ($logged_in == 'developer') {
?>
				<div class="explain">
				 Welcome back, <?php echo $user; ?>! (Not <?php echo $user; ?>?
				 <a href="logout.php">Log out.</a>)
				</div>
<?php } else { ?>
			<div class="explain">
				Welcome! If you don't have a SVN account, you can't do anything here.<br />
				You can <a href="bug.php?id=<?php echo $bug_id; ?>&amp;edit=3">add a comment by following this link</a>
				or if you reported this bug, you can <a href="bug.php?id=<?php echo $bug_id; ?>&amp;edit=2">edit this bug over here</a>.
				<div class="details">
					<label for="svnuser">SVN Username:</label>
					<input type="text" id="svnuser" name="user" value="<?php echo htmlspecialchars($user) ?>" size="10" maxlength="20" />
					<label for="svnpw">SVN Password:</label>
					<input type="password" id="svnpw" name="pw" value="<?php echo htmlspecialchars($pw) ?>" size="10" maxlength="20" />
					<label for="save">Remember:</label><input style="vertical-align:middle;" type="checkbox" id="save" name="save" <?php echo !empty($_POST['save']) ? 'checked="checked"' : ''; ?> />
				</div>
			</div>
<?php
		}
	}
	echo $preview;
?>
	<table>

<?php if ($edit == 1 && $logged_in == 'developer') { // Developer Edit Form ?>
		<tr>
		 <th class="details">
		  <label for="in" accesskey="c">Qui<span class="accesskey">c</span>k Fix:</label>
		 </th>
		 <td colspan="3">
		  <select name="in[resolve]" id="in">
		   <?php show_reason_types((isset($_POST['in']) && isset($_POST['in']['resolve'])) ? $_POST['in']['resolve'] : -1, 1) ?>
		  </select>

<?php	 if (isset($_POST['in']) && !empty($_POST['in']['resolve'])) { ?>
			  <input type="hidden" name="trytoforce" value="1" />
<?php	 } ?>

		  <small>(<a href="quick-fix-desc.php">description</a>)</small>
		 </td>
		</tr>
<?php } ?>

	 <tr>
	  <th class="details">Status:</th>
	  <td <?php echo (($edit != 1) ? 'colspan="3"' : '' ) ?>>
	   <select name="in[status]">
		<?php show_state_options(isset($_POST['in']) && isset($_POST['in']['status']) ?
			$_POST['in']['status'] : '', $edit, $bug['status']) ?>
	   </select>

<?php if ($edit == 1) { ?>

		</td>
		<th class="details">Assign to:</th>
		<td>
		 <input type="text" size="10" maxlength="16" name="in[assign]"
		  value="<?php echo field('assign') ?>" />
<?php } ?>

	   <input type="hidden" name="id" value="<?php echo $bug_id ?>" />
	   <input type="hidden" name="edit" value="<?php echo $edit ?>" />
	   <input type="submit" value="Submit" />
	  </td>
	 </tr>
	 <tr>
	  <th class="details">Package:</th>
	  <td colspan="3">
	   <select name="in[package_name]">
		<?php show_package_options(isset($_POST['in']) && isset($_POST['in']['package_name']) ? $_POST['in']['package_name'] : '', 0, $bug['package_name']) ?>
	   </select>
	  </td>
	 </tr>
	 <tr>
	  <th class="details">Bug Type:</th>
	   <td colspan="3">
		<select name="in[bug_type]">
			<?php show_type_options($bug['bug_type']); ?>
		</select>
	  </td>
	 </tr>
	 <tr>
	  <th class="details">Summary:</th>
	  <td colspan="3">
	   <input type="text" size="60" maxlength="80" name="in[sdesc]"
		value="<?php echo field('sdesc') ?>" />
	  </td>
	 </tr>
	 <tr>
	  <th class="details">From:</th>
	  <td colspan="3">
	   <?php echo spam_protect(field('email')) ?>
	  </td>
	 </tr>
	 <tr>
	  <th class="details">New email:</th>
	  <td colspan="3">
	   <input type="text" size="40" maxlength="40" name="in[email]"
		value="<?php echo (isset($_POST['in']) && isset($_POST['in']['email']) ? $_POST['in']['email'] : '') ?>" />
	  </td>
	 </tr>
	 <tr>
	  <th class="details">PHP Version:</th>
	  <td>
	   <input type="text" size="20" maxlength="100" name="in[php_version]"
		value="<?php echo field('php_version') ?>" />
	  </td>
	  <th class="details">OS:</th>
	  <td>
	   <input type="text" size="20" maxlength="32" name="in[php_os]"
		value="<?php echo field('php_os') ?>" />
	  </td>
	 </tr>
	</table>

	<p style="margin-bottom: 0em">
	<label for="ncomment" accesskey="m"><b>New<?php if ($edit==1) echo "/Additional"?> Co<span class="accesskey">m</span>ment:</b></label>
	</p>

	<textarea cols="80" rows="8" name="ncomment" id="ncomment"
	 wrap="physical"><?php echo htmlspecialchars($ncomment); ?></textarea>

	<p style="margin-top: 0em">
		<input type="submit" name="preview" value="Preview">&nbsp;<input type="submit" value="Submit" />
	</p>
	</form>

<?php } // if ($edit == 1 || $edit == 2) ?>

<?php if ($edit == 3) { ?>

	<form name="comment" id="comment" action="bug.php" method="post">

<?php if ($logged_in) { ?>
	<div class="explain">
	 <h1><a href="patch-add.php?bug_id=<?php echo $bug_id; ?>">Click Here to Submit a Patch</a></h1>
	</div>
<?php } ?>

<?php if (!isset($_POST['in'])) { ?>

		<div class="explain">
		 Anyone can comment on a bug. Have a simpler test case? Does it
		 work for you on a different platform? Let us know!<br />
		 Just going to say 'Me too!'? Don't clutter the database with that please

<?php
		 if (canvote($thanks, $bug['status'])) {
			 echo ' &mdash; but make sure to <a href="bug.php?id=' , $bug_id , '">vote on the bug</a>';
		 }
?>!
		</div>

<?php }

echo $preview;

if (!$logged_in) {
	$captcha = $numeralCaptcha->getOperation();
	$_SESSION['answer'] = $numeralCaptcha->getAnswer();
?>
	<table>
	 <tr>
	  <th class="details">Y<span class="accesskey">o</span>ur email address:<br />
	  <strong>MUST BE VALID</strong></th>
	  <td class="form-input">
	   <input type="text" size="40" maxlength="40" name="in[commentemail]" value="<?php echo isset($_POST['in']['commentemail']) ? htmlspecialchars($_POST['in']['commentemail']) : ''; ?>" accesskey="o" />
	  </td>
	 </tr>
	 <tr>
	  <th>Solve the problem:<br /><?php echo $captcha; ?> = ?</th>
	  <td class="form-input"><input type="text" name="captcha" /></td>
	 </tr>
	 <tr>
	  <th class="details">Subscribe to this entry?</th>
	  <td class="form-input">
	   <form name="subscribetobug" action="bug.php?id=<?php echo $bug_id; ?>" method="post">
		<input type="submit" name="subscribe_to_bug" value="Subscribe" />
		<input type="submit" name="unsubscribe_to_bug" value="Unsubscribe" />
	   </form>
	  </td>
	 </tr>
	</table>
   </div>
<?php } ?>

	<div>
	 <input type="hidden" name="id" value="<?php echo $bug_id; ?>" />
	 <input type="hidden" name="edit" value="<?php echo $edit; ?>" />
	 <textarea cols="80" rows="10" name="ncomment" wrap="physical"><?php echo htmlspecialchars($ncomment); ?></textarea>
	 <br /><input type="submit" name="preview" value="Preview">&nbsp;<input type="submit" value="Submit" />
	</div>

	</form>

<?php } ?>

<?php

// Display original report
if ($bug['ldesc']) {
	output_note(0, $bug['submitted'], $bug['email'], $bug['ldesc'], 'comment', $bug['reporter_name']);
}

// Display patches
if ($bug_id != 'PREVIEW') {
	require_once "{$ROOT_DIR}/include/classes/bug_patchtracker.php";
	$patches = new Bug_Patchtracker;
	$p = $patches->listPatches($bug_id);
	
	echo "<h2>Patches</h2>\n";

	foreach ($p as $name => $revisions)
	{
		$obsolete = $patches->getObsoletingPatches($bug_id, $name, $revisions[0][0]);
		$style = !empty($obsolete) ? ' style="background-color: yellow; text-decoration: line-through;" ' : '';
		$url_name = urlencode($name);
		$clean_name = clean($name);
		$formatted_date = format_date($revisions[0][0]);
		$submitter = spam_protect($revisions[0][1]);

		echo <<< OUTPUT
<a href="patch-display.php?bug_id={$bug_id}&amp;patch={$url_name}&amp;revision=latest" {$style}>{$clean_name}</a>
(last revision {$formatted_date}) by {$submitter})
<br />
OUTPUT;
	}
	echo "<p><a href='patch-add.php?bug_id={$bug_id}'>Add a Patch</a></p>";
}

// Display comments
$bug_comments = bugs_get_bug_comments($bug_id);
if (is_array($bug_comments) && count($bug_comments)) {
	echo '<h2 style="border-bottom:2px solid #666;margin-bottom:0;padding:5px 0;">History</h2>',
		 "
		 	<div id='comment_filter' class='controls comments'>
		 		<span id='type_all'	 class='control' onclick='do_comment(this);'>All</span>
		 		<span id='type_comment' class='control active' onclick='do_comment(this);'>Comments</span>
		 		<span id='type_log'	 class='control' onclick='do_comment(this);'>Changes</span>
		 		<span id='type_svn'	 class='control' onclick='do_comment(this);'>SVN commits</span>
			</div>
		 ";

	echo "<div id='comments_view' style='clear:both;'>\n";
	foreach ($bug_comments as $row) {
		output_note($row['id'], $row['added'], $row['email'], $row['comment'], $row['comment_type'], $row['comment_name']);
	}
	echo "</div>\n";
}

if ($bug_id == 'PREVIEW') {
?>

<form action="report.php?package=<?php $_SESSION['bug_preview']['package_name']; ?>" method="post">
<?php foreach($_SESSION['bug_preview'] as $k => $v) {
	echo "<input type='hidden' name='in[{$k}]' value='{$v}'/>";
}
	echo "<input type='hidden' name='captcha' value='{$_SESSION['captcha']}'/>";
?>
	<input type='submit' value='Send bug report' /> <input type='submit' name='edit_after_preview' value='Edit' />
</form>

<?php }

$bug_JS = <<< bug_JS
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<script type="text/javascript">
function do_comment(nd)
{
	$('#comment_filter > .control.active').removeClass("active");
	$(nd).addClass("active");
	
	if (nd.id == 'type_all') { 
		$('#comments_view > .comment:hidden').show('slow');
	} else {
		$('#comments_view > .comment').each(function(i) {
			if ($(this).hasClass(nd.id)) {
				$(this).show('slow');
			} else {
				$(this).hide('slow');
			}
		});
	}
	return false;
}
</script>
bug_JS;

response_footer($bug_JS);

/** 
 * Helper functions 
 */
function output_note($com_id, $ts, $email, $comment, $comment_type, $comment_name)
{
	global $edit, $bug_id, $dbh, $is_trusted_developer, $logged_in;

	$display = ($comment_type == 'comment') ? '' : 'style="display:none;"';
	echo "<div class='comment type_{$comment_type}' {$display}>";
	echo '<a name="' , urlencode($ts) , '">&nbsp;</a>';
	echo "<strong>[" , format_date($ts) , "] ";
	echo spam_protect(htmlspecialchars($email)) , "</strong>\n";

	switch ($comment_type)
	{
		case 'log':
			echo "<div class='log_note'>{$comment}</div>";
			break;

		default:
			// Delete comment action only for trusted developers
			echo ($edit == 1 && $com_id !== 0 && $is_trusted_developer) ? "<a href='bug.php?id={$bug_id}&amp;edit=1&amp;delete_comment={$com_id}'>[delete]</a>\n" : '';

			$comment = make_ticket_links(addlinks($comment));
			echo "<pre class='note'>{$comment}\n</pre>\n";
	}

	echo '</div>';
}

function delete_comment($bug_id, $com_id)
{
	global $dbh;
	
	$res = $dbh->prepare("DELETE FROM bugdb_comments WHERE bug='{$bug_id}' AND id='{$com_id}'")->execute();
}

function control($num, $desc)
{
	global $bug_id, $edit;

	$str = "<span id='control_{$num}' class='control";
	if ($edit == $num) {
		$str .= " active'>{$desc}";
	} else {
		$str .= "'><a href='bug.php?id={$bug_id}" . (($num) ? "&amp;edit={$num}" : '') . "'>{$desc}</a>";
	}
	return "{$str}</span>\n";
}

function canvote($thanks, $status)
{
	return ($thanks != 4 && $thanks != 6 && $status != 'Closed' && $status != 'Bogus' && $status != 'Duplicate');
}
