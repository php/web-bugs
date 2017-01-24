<?php
/* User interface for viewing and editing bug details */

// Obtain common includes
require_once '../include/prepend.php';

// Start session 
session_start();

define('SPAM_REJECT_MESSAGE', 'Your comment looks like SPAM by its content. Please consider rewording.');

// Handle preview
if (isset($_REQUEST['id']) && $_REQUEST['id'] == 'preview') {
	$bug_id = 'PREVIEW';
	$bug = $_SESSION['bug_preview'];
	$bug['submitted'] = time();
	$bug['modified'] = null;
	$bug['votes'] = 0;
	$bug['assign'] = '';
	
	if (!$bug) {
		redirect('index.php');
	}
} else {
	// Bailout early if no/invalid bug id is passed
	if (empty($_REQUEST['id']) || !((int) $_REQUEST['id'])) {
		redirect('index.php');
	} else {
		$bug_id = (int) $_REQUEST['id'];
	}
}

// Init common variables
$errors = array();

// Set edit mode
$edit = isset($_REQUEST['edit']) ? (int) $_REQUEST['edit'] : 0;

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

$is_trusted_developer = ($user_flags & BUGS_TRUSTED_DEV);
$is_security_developer = ($user_flags & (BUGS_TRUSTED_DEV | BUGS_SECURITY_DEV));

// Handle unsubscription
if (isset($_GET['unsubscribe'])) {
	$unsubcribe = (int) $_GET['unsubscribe'];

	$hash = isset($_GET['t']) ? $_GET['t'] : false;

	if (!$hash) {
		redirect("bug.php?id={$bug_id}");
	}
	unsubscribe($bug_id, $hash);
	$_GET['thanks'] = 9;
}

// Subscription / Unsubscription
if (isset($_POST['subscribe_to_bug']) || isset($_POST['unsubscribe_to_bug'])) {

	// Check if session answer is set, then compare it with the post captcha value.
	// If it's not the same, then it's an incorrect password.
	if (!$logged_in) {
		if (!isset($_SESSION['answer'])) {
			$errors[] = 'Please enable cookies so the Captcha system can work';
		} elseif ($_POST['captcha'] != $_SESSION['answer']) {
			$errors[] = 'Incorrect Captcha';
		}
	}

	if (empty($errors)) {
		if ($logged_in && !empty($auth_user->email)) {
			$email = $auth_user->email;
		} else {
			$email = isset($_POST['in']['commentemail']) ? $_POST['in']['commentemail'] : '';
		}
		if ($email == '' || !is_valid_email($email, $logged_in)) {
			$errors[] = 'You must provide a valid email address.';
		} else {
			// Unsubscribe
			if (isset($_POST['unsubscribe_to_bug'])) {
				// Generate the hash
				unsubscribe_hash($bug_id, $email);
				$thanks = 8;
			}
			else // Subscribe
			{
				$dbh->prepare('REPLACE INTO bugdb_subscribe SET bug_id = ?, email = ?')->execute(array($bug_id, $email));
				$thanks = 7;
			}
			redirect("bug.php?id={$bug_id}&thanks={$thanks}");
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
}

// captcha is not necessary if the user is logged in
if (!$logged_in) {
	require_once 'Text/CAPTCHA/Numeral.php';
	$numeralCaptcha = new Text_CAPTCHA_Numeral();
}

$trytoforce = isset($_POST['trytoforce']) ? (int) $_POST['trytoforce'] : 0;

// fetch info about the bug into $bug
if (!isset($bug)) {
	$bug = bugs_get_bug($bug_id);
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

$show_bug_info = bugs_has_access($bug_id, $bug, $pw, $user_flags);
if ($edit == 2 && !$show_bug_info && $pw && verify_bug_passwd($bug_id, bugs_get_hash($pw))) {
	$show_bug_info = true;
}

if (isset($_POST['ncomment'])) {
	/* Bugs blocked to user comments can only be commented by the team */
	if ($bug['block_user_comment'] == 'Y' && $logged_in != 'developer') {
		response_header('Adding comments not allowed');
		display_bug_error("You're not allowed to add a comment to bug #{$bug_id}");
		response_footer();
		exit;
	}
}

/* Just developers can change private/block_user_comment options */
if (!empty($_POST['in'])) {
	if ($user_flags & BUGS_DEV_USER) {
		$block_user = isset($_POST['in']['block_user_comment']) ? 'Y' : 'N';
	}
	if ($is_security_developer) {
		$is_private = isset($_POST['in']['private']) ? 'Y': 'N';
	}
}

$block_user = isset($block_user) ? $block_user : $bug['block_user_comment'];
$is_private = isset($is_private) ? $is_private : $bug['private'];

// Handle any updates, displaying errors if there were any
$RESOLVE_REASONS = $FIX_VARIATIONS = $pseudo_pkgs = array();

$project = $bug['project'];

// Only fetch stuff when it's really needed
if ($edit && $edit < 3) {
	$pseudo_pkgs = get_pseudo_packages(false, false); // false == no read-only packages included
}

// Fetch RESOLVE_REASONS array
if ($edit === 1) {
	list($RESOLVE_REASONS, $FIX_VARIATIONS) = get_resolve_reasons($project);
}

if (isset($_POST['ncomment']) && !isset($_POST['preview']) && $edit == 3) {
	// Submission of additional comment by others

	// Bug is private (just should be available to trusted developers and to reporter)
	if (!$is_security_developer && $bug['private'] == 'Y') {
		response_header('Private report');
		display_bug_error("The bug #{$bug_id} is not available to public, if you are the original reporter use the Edit tab");
		response_footer();
		exit;
	}

	// Check if session answer is set, then compare it with the post captcha value.
	// If it's not the same, then it's an incorrect password.
	if (!$logged_in) {
		if (!isset($_SESSION['answer'])) {
			$errors[] = 'Please enable cookies so the Captcha system can work';
		} elseif ($_POST['captcha'] != $_SESSION['answer']) {
			$errors[] = 'Incorrect Captcha';
		}
	}

	$ncomment = trim($_POST['ncomment']);
	if (!$ncomment) {
		$errors[] = 'You must provide a comment.';
	}

	// primitive spam detection
	if (is_spam($ncomment)) {
		$errors[] = SPAM_REJECT_MESSAGE;
	}

	if (is_spam($_POST['in']['commentemail'])) {
		$errors[] = "Please do not SPAM our bug system.";
	}

	if (!$errors) {
		do {
			if (!$logged_in) {

				if (!is_valid_email($_POST['in']['commentemail'], $logged_in)) {
					$errors[] = 'You must provide a valid email address.';
					response_header('Add Comment - Problems');
					break; // skip bug comment addition
				}

				$_POST['in']['name'] = '';
			} else {
				$_POST['in']['commentemail'] = $auth_user->email;
				$_POST['in']['name'] = $auth_user->name;
			}

			$res = bugs_add_comment($bug_id, $_POST['in']['commentemail'], $_POST['in']['name'], $ncomment, 'comment');
			
			mark_related_bugs($_POST['in']['commentemail'], $_POST['in']['name'], $ncomment);

		} while (false);

		$from = spam_protect($_POST['in']['commentemail'], 'text');
	} else {
		$from = '';
	}
} elseif (isset($_POST['ncomment']) && isset($_POST['preview']) && $edit == 3) {
	$ncomment = trim($_POST['ncomment']);

	// primitive spam detection
	if (is_spam($ncomment)) {
		$errors[] = SPAM_REJECT_MESSAGE;
	}
	
	$from = $_POST['in']['commentemail'];
	
} elseif (isset($_POST['in']) && !isset($_POST['preview']) && $edit == 2) {
	// Edits submitted by original reporter for old bugs
	
	if (!$show_bug_info || !verify_bug_passwd($bug_id, bugs_get_hash($pw))) {
		$errors[] = 'The password you supplied was incorrect.';
	}
	
	// Bug is private (just should be available to trusted developers, original reporter and assigned dev)
	if (!$show_bug_info && $bug['private'] == 'Y') {
		response_header('Private report');
		display_bug_error("The bug #{$bug_id} is not available to public");
		response_footer();
		exit;
	}
	
	// Just trusted dev can change the package name of a Security related bug to another package
	if ($bug['private'] == 'Y' && !$is_security_developer
		&& $bug['bug_type'] == 'Security'
		&& $_POST['in']['bug_type'] != $bug['bug_type']) {
	
		$errors[] = 'You cannot change the bug type of a Security bug!';	
	}

	$ncomment = trim($_POST['ncomment']);
	if (!$ncomment) {
		$errors[] = 'You must provide a comment.';
	}

	// check that they aren't being bad and setting a status they aren't allowed to (oh, the horrors.)
	if (isset($_POST['in']['status'])
		&& isset($state_types[$_POST['in']['status']])
		&& $_POST['in']['status'] != $bug['status'] && $state_types[$_POST['in']['status']] != 2) {
		$errors[] = 'You aren\'t allowed to change a bug to that state.';
	}

	// check that they aren't changing the mail to a php.net address (gosh, somebody might be fooled!)
	if (preg_match('/^(.+)@php\.net/i', $_POST['in']['email'], $m)) {
		if ($user != $m[1] || $logged_in != 'developer') {
			$errors[] = 'You have to be logged in as a developer to use your php.net email address.';
			$errors[] = 'Tip: log in via another browser window then resubmit the form in this window.';
		}
	}

	// primitive spam detection
	if ($ncomment && is_spam($ncomment)) {
		$errors[] = SPAM_REJECT_MESSAGE;
	}

	if (!empty($_POST['in']['email']) &&
		$bug['email'] != $_POST['in']['email']
	) {
		$from = $_POST['in']['email'];
	} else {
		$from = $bug['email'];
	}

	if (!$errors && !($errors = incoming_details_are_valid($_POST['in'], false))) {
		// Allow the reporter to change the bug type to 'Security', hence mark
		// the report as private
		if ($bug['private'] == 'N' && $_POST['in']['bug_type'] == 'Security'
			&& $_POST['in']['bug_type'] != $bug['bug_type']) {
					
			$is_private = $_POST['in']['private'] = 'Y';
		}
	
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
				ts2 = NOW(),
				private = ?
			WHERE id={$bug_id}
		")->execute(array(
			$_POST['in']['sdesc'],
			$_POST['in']['status'],
			$_POST['in']['package_name'],
			$_POST['in']['bug_type'],
			$_POST['in']['php_version'],
			$_POST['in']['php_os'],
			$from,
			$is_private
		));

		// Add changelog entry
		$changed = bug_diff($bug, $_POST['in']);
		if (!empty($changed)) {
			$log_comment = bug_diff_render_html($changed);

			if (!empty($log_comment)) {
				$res = bugs_add_comment($bug_id, $from, '', $log_comment, 'log');
			}
		}
		
		// Add normal comment
		if (!empty($ncomment)) {
			$res = bugs_add_comment($bug_id, $from, '', $ncomment, 'comment');
			
			mark_related_bugs($from, '', $ncomment);
		}
	}
} elseif (isset($_POST['in']) && isset($_POST['preview']) && $edit == 2) {
	$ncomment = trim($_POST['ncomment']);
	$from = isset($_POST['in']['commentemail']) ? $_POST['in']['commentemail'] : '';

	// primitive spam detection
	if (is_spam($ncomment)) {
		$errors[] = SPAM_REJECT_MESSAGE;
	}

} elseif (isset($_POST['in']) && is_array($_POST['in']) && !isset($_POST['preview']) && $edit == 1) {
	// Edits submitted by developer
	
	// Bug is private (just should be available to trusted developers, submitter and assigned dev)
	if (!$show_bug_info && $bug['private'] == 'Y') {
		response_header('Private report');
		display_bug_error("The bug #{$bug_id} is not available to public");
		response_footer();
		exit;
	}
	
	if ($logged_in != 'developer') {
		$errors[] = 'You have to login first in order to edit the bug report.';
	}
	$comment_name = $auth_user->name;
	if (empty($_POST['ncomment'])) {
		$ncomment = '';
	} else {
		$ncomment = trim($_POST['ncomment']);
	}

	// primitive spam detection
	if ($ncomment && is_spam($ncomment)) {
		$errors[] = SPAM_REJECT_MESSAGE;
	}
	
	// Just trusted dev can set CVE-ID
	if ($is_security_developer && !empty($_POST['in']['cve_id'])) {
		// Remove the CVE- prefix
		$_POST['in']['cve_id'] = preg_replace('/^\s*CVE-/i', '', $_POST['in']['cve_id']);
	}
	if (empty($_POST['in']['cve_id'])) {
		$_POST['in']['cve_id'] = $bug['cve_id'];
	}
	
	if ($bug['private'] == 'N' && $bug['private'] != $is_private) {
		if ($_POST['in']['bug_type'] != 'Security') {
			$errors[] = 'Only Security bugs can be marked as private.';
		}
	}

	// Require comment for open bugs only
	if (empty($_POST['in']['status'])) {
		$errors[] = "You must provide a status";
	} else {
		if ($_POST['in']['status'] == 'Not a bug' &&
			!in_array($bug['status'], array ('Not a bug', 'Closed', 'Duplicate', 'No feedback', 'Wont fix')) &&
			strlen(trim($ncomment)) == 0
		) {
			$errors[] = "You must provide a comment when marking a bug 'Not a bug'";
		} elseif (!empty($_POST['in']['resolve'])) {
			if (!$trytoforce && isset($RESOLVE_REASONS[$_POST['in']['resolve']]) &&
				$RESOLVE_REASONS[$_POST['in']['resolve']]['status'] == $bug['status'])
			{
				$errors[] = 'The bug is already marked "'.$bug['status'].'". (Submit again to ignore this.)';
			} elseif (!$errors) {
				if ($_POST['in']['status'] == $bug['status']) {
					$_POST['in']['status'] = $RESOLVE_REASONS[$_POST['in']['resolve']]['status'];
				}
				if (isset($FIX_VARIATIONS) && isset($FIX_VARIATIONS[$_POST['in']['resolve']][$bug['package_name']])) {
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
	}

	$from = $auth_user->email;

	if (!$errors && !($errors = incoming_details_are_valid($_POST['in']))) {
		$query = 'UPDATE bugdb SET';

		// Update email only if it's passed
		if ($bug['email'] != $_POST['in']['email'] && !empty($_POST['in']['email'])) {
			$query .= " email='{$_POST['in']['email']}',";
		}
		
		// Changing the package to 'Security related' should mark the bug as private automatically
		if ($bug['bug_type'] != $_POST['in']['bug_type']) {
			if ($_POST['in']['bug_type'] == 'Security' && $_POST['in']['status'] != 'Closed') {
				$is_private = $_POST['in']['private'] = 'Y';
			}
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
				block_user_comment = ?,
				cve_id = ?,
				private = ?,
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
			$block_user,
			$_POST['in']['cve_id'],
			$is_private
		));

		// Add changelog entry
		$changed = bug_diff($bug, $_POST['in']);
		if (!empty($changed)) {
			$log_comment = bug_diff_render_html($changed);

			if (!empty($log_comment)) {
				$res = bugs_add_comment($bug_id, $from, $comment_name, $log_comment, 'log');
			}
		}

		// Add normal comment
		if (!empty($ncomment)) {
			$res = bugs_add_comment($bug_id, $from, $comment_name, $ncomment, 'comment');
			
			mark_related_bugs($from, $comment_name, $ncomment);
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

if (isset($_POST['in']) && !isset($_POST['preview']) && !$errors) {
	mail_bug_updates($bug, $_POST['in'], $from, $ncomment, $edit, $bug_id);
	redirect("bug.php?id=$bug_id&thanks=$edit");
}

switch (txfield('bug_type', $bug, isset($_POST['in']) ? $_POST['in'] : null))
{
	case 'Feature/Change Request':
		$bug_type = 'Request';
		break;
	case 'Documentation Problem':
		$bug_type = 'Doc Bug';
		break;
	case 'Security':
		$bug_type = 'Sec Bug';
		break;
	default:
	case 'Bug':
		$bug_type = 'Bug';
		break;
}

// DISPLAY BUG
$thanks = (isset($_GET['thanks'])) ? (int) $_GET['thanks'] : 0;
switch ($thanks)
{
	case 1:
	case 2:
		$flash = 'The bug was updated successfully.';
		break;
	case 3:
		$flash = 'Your comment was added to the bug successfully.';
		break;
	case 4:
		$flash = 'Thank you for your help! You will be notified of any changes regarding your report.';
		break;
	case 6:
		$flash = 'Thanks for voting! Your vote should be reflected in the statistics below.';
		break;
	case 7:
		$flash = 'Your subscribe request has been processed.';
		break;
	case 8:
		$flash = 'Your unsubscribe request has been processed, please check your email.';
		break;
	case 9:
		$flash = 'You have successfully unsubscribed.';
		break;
	case 10:
		$flash = 'Your vote has been updated.';
	break;

	default:
		break;
}

$flash = isset($flash) ? flash_message($flash) : '';

response_header(
	$show_bug_info ? "{$bug_type} #{$bug_id} :: " . htmlspecialchars($bug['sdesc']) : "You must be logged in",
	($bug_id != 'PREVIEW') ? "
		<link rel='alternate' type='application/rss+xml' title='{$bug['package_name']} Bug #{$bug['id']} - RDF' href='rss/bug.php?id={$bug_id}'>
		<link rel='alternate' type='application/rss+xml' title='{$bug['package_name']} Bug #{$bug['id']} - RSS 2.0' href='rss/bug.php?id={$bug_id}&format=rss2'>
	" : '',
	$flash
);

display_bug_error($errors);

if ($show_bug_info) {
?>
<h1 class="bug-header"><span><?= $bug_type ?> #<?= $bug_id ?></span > <?= $bug['sdesc'] ?></h1>
<?php

if ($bug_id !== 'PREVIEW') {
	echo '<div class="controls">', "\n",
		control(0, 'View'),
		($bug['private'] == 'N' ? control(3, 'Add Comment') : ''),
		control(1, 'Developer'),
		(!$email || $bug['email'] == $email ? control(2, 'Edit') : ''),
		'</div>', "\n";
}
?>
	<table border="0" class="bug-details">
		<tr>
			<th>Submitted:</th>
			<td style="white-space: nowrap;"><?php echo format_date($bug['submitted']); ?></td>
			<th>Modified:</th>
			<td style="white-space: nowrap;"><?php echo ($bug['modified']) ? format_date($bug['modified']) : '-'; ?></td>
		</tr>

		<tr>
			<th>From:</th>
			<td><?php echo ($bug['status'] !== 'Spam') ? spam_protect(htmlspecialchars($bug['email'])) : 'Hidden because of SPAM'; ?></td>
			<th>Assigned:</th>
<?php if (!empty($bug['assign'])) { ?>
			<td><a href="search.php?cmd=display&amp;assign=<?php echo urlencode($bug['assign']), '">', htmlspecialchars($bug['assign']); ?></a></td>
<?php } else { ?>
			<td><?php echo htmlspecialchars($bug['assign']); ?></td>
<?php } ?>
		</tr>

		<tr>
			<th>Status:</th>
			<td><?php echo htmlspecialchars($bug['status']); ?></td>
			<th>Package:</th>
			<td><a href="search.php?cmd=display&amp;package_name[]=<?php echo urlencode($bug['package_name']), '">', htmlspecialchars($bug['package_name']); ?></a><?php echo $bug['project'] == 'pecl' ? ' (<a href="http://pecl.php.net/package/'. htmlspecialchars($bug['package_name']) . '" target="_blank">PECL</a>)' : ''; ?></td>
		</tr>

		<tr>
			<th>PHP Version:</th>
			<td><?php echo htmlspecialchars($bug['php_version']); ?></td>
			<th>OS:</th>
			<td><?php echo htmlspecialchars($bug['php_os']); ?></td>
		</tr>
		
		<tr>
			<th>Private report:</th>
			<td><?php echo $bug['private'] == 'Y' ? 'Yes' : 'No'; ?></td>
			<th>CVE-ID:</th>
			<td><?php if (!empty($bug['cve_id'])) { printf('<a href="http://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-%s" target="_blank">%1$s</a>', htmlspecialchars($bug['cve_id'])); } ?></td>
		</tr>
	</table>

<?php // fixme: re-add voting ?>
<?php if ($show_bug_info && !$edit && canvote($thanks, $bug['status']) && false) { ?>
<form id="vote" method="post" action="vote.php">
	<div class="sect">
		<fieldset>
			<legend>Have you experienced this issue?</legend>
			<div>
				<input type="radio" id="rep-y" name="reproduced" value="1" onchange="show('canreproduce')"> <label for="rep-y">yes</label>
				<input type="radio" id="rep-n" name="reproduced" value="0" onchange="hide('canreproduce')"> <label for="rep-n">no</label>
				<input type="radio" id="rep-d" name="reproduced" value="2" onchange="hide('canreproduce')" checked="checked"> <label for="rep-d">don't know</label>
			</div>
		</fieldset>
		<fieldset>
			<legend>Rate the importance of this bug to you:</legend>
			<div>
				<label for="score-5">high</label>
				<input type="radio" id="score-5" name="score" value="2">
				<input type="radio" id="score-4" name="score" value="1">
				<input type="radio" id="score-3" name="score" value="0" checked="checked">
				<input type="radio" id="score-2" name="score" value="-1">
				<input type="radio" id="score-1" name="score" value="-2">
				<label for="score-1">low</label>
			</div>
		</fieldset>
	</div>
	<div id="canreproduce" class="sect" style="display: none">
		<fieldset>
			<legend>Are you using the same PHP version?</legend>
			<div>
				<input type="radio" id="ver-y" name="samever" value="1"> <label for="ver-y">yes</label>
				<input type="radio" id="ver-n" name="samever" value="0" checked="checked"> <label for="ver-n">no</label>
			</div>
		</fieldset>
		<fieldset>
			<legend>Are you using the same operating system?</legend>
			<div>
				<input type="radio" id="os-y" name="sameos" value="1"> <label for="os-y">yes</label>
				<input type="radio" id="os-n" name="sameos" value="0" checked="checked"> <label for="os-n">no</label>
			</div>
		</fieldset>
	</div>
	<div id="submit" class="sect">
		<input type="hidden" name="id" value="<?php echo $bug_id?>">
		<input type="submit" value="Vote">
	</div>
</form>
<br clear="all">
<?php	} 

} // if ($bug_id != 'PREVIEW') { 

if (isset($_POST['preview']) && !empty($ncomment)) {
	$preview = output_note('preview', time(), $from, $ncomment, 'comment');
} else {
	$preview = '';
}

if ($edit == 1 || $edit == 2) { ?>

<form id="update" action="bug.php?id=<?php echo $bug_id; ?>&amp;edit=<?php echo $edit; ?>" method="post">

<?php if ($edit == 2) {
	// Show explanation if the form wasn't' filled yet
	if (!isset($_POST['in'])) {
		echo '<p class="warn">';
		echo "Welcome back! If you're the original bug submitter, here's where you can ";
		echo 'edit the bug or add additional notes.<br>If this is not your bug you can ';
		echo "add a coment <a href='bug.php?id={$bug_id}&amp;edit=3'>here</a>.";
		echo '</p>';
	}
?>
<table border="0" class="standard report-bug-form">
	<tr>
		<th>
			<label for="in_pw" class="required">Password</label>
			<small>[<a href="bug-pwd-finder.php?id=<?= $bug_id ?>">Forgot?</a>]</small>
		</th>
		<td><input type="password" maxlength="20" name="pw" id="in_pw" value="<?= esc($pw) ?>" required></td>
	</tr>
	<?php if (!$show_bug_info): ?>
	<tr>
		<th colspan="2" class="buttons">
			<input type="submit" value="Submit">
		</th>
	</tr>
	<?php endif; ?>
<?php
	// Edit == 1 (developer)
	} else {
		if ($logged_in != 'developer') {
			echo '<p class="warn">You need to <a href="login.php">log in</a> first.</p>';
			response_footer();
			exit;
		}
		echo '<p class="warn">Welcome back, ' . $user . '!</p>';
	}

echo $preview;
?>

<?php if ($edit == 1 && $show_bug_info) { /* Developer Edit Form */ ?>
<table border="0" class="standard report-bug-form">
	<tr>
			<th class="details"><label for="in" accesskey="c">Qui<span class="accesskey">c</span>k Fix:</label></th>
			<td colspan="3">
				<select name="in[resolve]" id="in">
					<?php show_reason_types((isset($_POST['in']) && isset($_POST['in']['resolve'])) ? $_POST['in']['resolve'] : -1, 1); ?>
				</select>

<?php	if (isset($_POST['in']) && !empty($_POST['in']['resolve'])) { ?>
				<input type="hidden" name="trytoforce" value="1">
<?php	} ?>

				<small>(<a href="quick-fix-desc.php">description</a>)</small>
			</td>
		</tr>
<?php   if ($is_security_developer) { ?>
		<tr>
			<th class="details">CVE-ID:</th>
			<td colspan="3">
				<input type="text" size="15" maxlength="15" name="in[cve_id]" value="<?php echo field('cve_id'); ?>" id="cve_id">
			</td>
		</tr>
		<tr>
			<th class="details"></th>
			<td colspan="3">
				<input type="checkbox" name="in[private]" value="Y" <?php print $is_private == 'Y' ? 'checked="checked"' : ''; ?>> Private report (Normal user should not see it)
			</td>
		</tr>
<?php   } ?>
		<tr>
			<th class="details"></th>
			<td colspan="3">
				<input type="checkbox" name="in[block_user_comment]" value="Y" <?php print $block_user == 'Y' ? 'checked="checked"' : ''; ?>> Block user comment
			</td>
		</tr>
<?php } // end of developer edit form ?>

<?php
	// Shared part of edit form (both developer&user),
	// hidden in case bug is private
	if ($show_bug_info) { ?>

		<tr>
			<th class="details">Status:</th>
			<td <?php echo (($edit != 1) ? 'colspan="3"' : '' ); ?>>
				<select name="in[status]">
					<?php show_state_options(isset($_POST['in']) && isset($_POST['in']['status']) ? $_POST['in']['status'] : '', $edit, $bug['status'], $bug['assign']); ?>
				</select>

<?php if ($edit == 1) { ?>
			</td>
			<th class="details">Assign to:</th>
			<td>
				<input type="text" size="10" maxlength="16" name="in[assign]" value="<?php echo field('assign'); ?>" id="assigned_user">
<?php } ?>

				<input type="hidden" name="id" value="<?php echo $bug_id ?>">
				<input type="hidden" name="edit" value="<?php echo $edit ?>">
				<input type="submit" value="Submit">
			</td>
		</tr>
		<tr>
			<th class="details">Package:</th>
			<td colspan="3">
				<select name="in[package_name]">
					<?php show_package_options(isset($_POST['in']) && isset($_POST['in']['package_name']) ? $_POST['in']['package_name'] : '', 0, $bug['package_name']); ?>
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
				<input type="text" size="60" maxlength="80" name="in[sdesc]" value="<?php echo ($bug['status'] !== 'Spam') ? field('sdesc') : 'Hidden because of SPAM'; ?>">
			</td>
		</tr>
		<tr>
			<th class="details">From:</th>
			<td colspan="3">
				<?php echo ($bug['status'] !== 'Spam') ? spam_protect(field('email')) : 'Hidden because of SPAM'; ?>
			</td>
		</tr>
		<tr>
			<th class="details">New email:</th>
			<td colspan="3">
				<input type="text" size="40" maxlength="40" name="in[email]" value="<?php echo isset($_POST['in']) && isset($_POST['in']['email']) ? htmlspecialchars($_POST['in']['email']) : ''; ?>">
			</td>
		</tr>
		<tr>
			<th class="details">PHP Version:</th>
			<td><input type="text" size="20" maxlength="100" name="in[php_version]" value="<?php echo field('php_version'); ?>"></td>
			<th class="details">OS:</th>
			<td><input type="text" size="20" maxlength="32" name="in[php_os]" value="<?php echo field('php_os'); ?>"></td>
		</tr>

	<p style="margin-bottom: 0em;">
		<label for="ncomment" accesskey="m"><b>New<?php if ($edit == 1) echo "/Additional"; ?> Co<span class="accesskey">m</span>ment:</b></label>
	</p>
	<?php
	if ($bug['block_user_comment'] == 'Y' && $logged_in != 'developer') {
		echo 'Further comment on this bug is unnecessary.';
	} elseif ($bug['status'] === 'Spam' && $logged_in != 'developer') {
		echo 'This bug has a SPAM status, so no additional comments are needed.';
	} else {
	?>
		<textarea cols="80" rows="8" name="ncomment" id="ncomment" wrap="soft"><?php echo htmlspecialchars($ncomment); ?></textarea>
	<?php
	}
	?>

	<p style="margin-top: 0em">
		<input type="submit" name="preview" value="Preview">&nbsp;<input type="submit" value="Submit">
	</p>

</form>

<?php } // if ($show_bug_info)
	echo '</table>';
} // if ($edit == 1 || $edit == 2) 
?>

<?php 
	if ($edit == 3 && $bug['private'] == 'N') { 
	
	if ($bug['status'] === 'Spam') {
		echo '<p class="warn">This bug has a SPAM status, so no additional comments are needed.</p>';
		response_footer();
		exit;
	}

	if ($bug['block_user_comment'] == 'Y' && $logged_in != 'developer') {
		echo '<p class="warn">Further comment on this bug is unnecessary.</p>';
		response_footer();
		exit;
	}
?>

	<form name="comment" id="comment" action="bug.php" method="post">

<?php if ($logged_in) { ?>
	<div class="explain">
		<h1>
			<a href="patch-add.php?bug_id=<?php echo $bug_id; ?>">Click Here to Submit a Patch</a>
			<input type="submit" name="subscribe_to_bug" value="Subscribe">
			<input type="submit" name="unsubscribe_to_bug" value="Unsubscribe">
		</h1>
	</div>
<?php } ?>

<?php if (!isset($_POST['in'])) { ?>

		<p class="warn">
			Anyone can comment on a bug. Have a simpler test case? Does it
			work for you on a different platform? Let us know!<br>
			Just going to say <em>Me too</em>? Don't clutter the database with that please

<?php
			if (canvote($thanks, $bug['status'])) {
				echo ' &mdash; but make sure to <a href="bug.php?id=' , $bug_id , '">vote on the bug</a>';
			}
?>!
		</p>

<?php }

echo $preview;

if (!$logged_in) {
	$captcha = $numeralCaptcha->getOperation();
	$_SESSION['answer'] = $numeralCaptcha->getAnswer();
?>
	<table border="0" class="standard report-bug-form">
		<tr>
			<th>
				<label for="in_email" class="required">Your email address</label>
				<small><strong>MUST BE VALID</strong></small>
			</th>
			<td>
				<input type="email" maxlength="40" name="in[commentemail]" id="in_email" value="<?= esc($_POST['in']['commentemail']) ?>" required>
			</td>
		</tr>
		<tr>
			<th><label for="in_captcha" class="required">Human test</label></th>
			<td>
				<span class="captcha-question"><?= esc($captcha) ?> = </span>
				<input type="text" name="captcha" autocomplete="off" required>
			</td>
		</tr>
		<tr>
			<th><label>Subscribe to this entry?</label></th>
			<td>
				<input type="submit" name="subscribe_to_bug" value="Subscribe">
				<input type="submit" name="unsubscribe_to_bug" value="Unsubscribe">
			</td>
		</tr>
<?php } ?>
		<input type="hidden" name="id" value="<?php echo $bug_id; ?>">
		<input type="hidden" name="edit" value="<?php echo $edit; ?>">

		<tr>
			<th><label for="in_ncomment" class="required">Comment</label></th>
			<td>
				<textarea rows="9" name="ncomment" id="in_ncomment" required><?= esc($ncomment) ?></textarea>
			</td>
		</tr>
		<tr>
			<th class="buttons" colspan="2">
				<input type="submit" value="Preview" name="preview"> or 
				<input type="submit" value="Submit">
			</td>
		</tr>
	</table>

	</form>

<?php } ?>

<?php

// Display original report
if ($bug['ldesc']) {
	if (!$show_bug_info) {
		echo '<p class="warn">This bug report is marked as private.</p>';
	} else if ($bug['status'] !== 'Spam') {
		output_note(0, $bug['submitted'], $bug['email'], $bug['ldesc']);
	} else {
		echo '<p class="warn">The original report has been hidden, due to the SPAM status.</p>';
	}
}

// Display patches
if ($show_bug_info && $bug_id != 'PREVIEW' && $bug['status'] !== 'Spam') {
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
<br>
OUTPUT;
	}
	echo "<p><a href='patch-add.php?bug_id={$bug_id}'>Add a Patch</a></p>";

	require_once "{$ROOT_DIR}/include/classes/bug_ghpulltracker.php";
	$pulltracker = new Bug_Pulltracker();
	$pulls = $pulltracker->listPulls($bug_id);
	echo "<h2>Pull Requests</h2>\n";

	require "{$ROOT_DIR}/templates/listpulls.php";
	echo "<p><a href='gh-pull-add.php?bug_id={$bug_id}'>Add a Pull Request</a></p>";
}

// Display comments
$bug_comments = bugs_get_bug_comments($bug_id);
if ($show_bug_info && count($bug_comments) && $bug['status'] !== 'Spam') {
?>
<h3>History</h3>
<div class="controls comments">
	<span data-type="all" class="active">All</span>
	<span data-type="comment">Comments</span>
	<span data-type="log">Changes</span>
	<span data-type="svn">Commits/Patches</span>
	<span data-type="related">Related</span>
</div>

<?php
	foreach ($bug_comments as $row) {
		output_note($row['id'], $row['added'], $row['email'], $row['comment'], $row['comment_type']);
	}
}

if ($bug_id == 'PREVIEW') {
?>

<form action="report.php?package=<?php htmlspecialchars($_SESSION['bug_preview']['package_name']); ?>" method="post">
<?php foreach($_SESSION['bug_preview'] as $k => $v) {
	if ($k !== 'ldesc') {
		if ($k === 'ldesc_orig') {
			$k = 'ldesc';
		}
		echo "<input type='hidden' name='in[", htmlspecialchars($k, ENT_QUOTES), "]' value='", htmlentities($v, ENT_QUOTES, 'UTF-8'), "'>";
	}
}
	echo "<input type='hidden' name='captcha' value='", htmlspecialchars($_SESSION['captcha'], ENT_QUOTES), "'>";
?>
	<input type='submit' value='Send bug report'> <input type='submit' name='edit_after_preview' value='Edit'>
</form>

<?php }

if ($edit == 1) {
	$bug_JS .= '
<script type="text/javascript" src="js/jquery.autocomplete-min.js"></script>
<script type="text/javascript" src="js/userlisting.php"></script> 	
<script type="text/javascript" src="js/search.js"></script>
	';

}

response_footer($bug_JS);

// Helper functions

function mark_related_bugs($from, $comment_name, $ncomment)
{
	global $bug_id;

	$related = get_ticket_links($ncomment);
	
	/**
	 * Adds a new comment on the related bug pointing to the current report
	 */
	foreach ($related as $bug) {
		bugs_add_comment($bug, $from, $comment_name,
			'Related To: Bug #'. $bug_id, 'related');
	}
}

function is_php_user($email)
{
	return strstr($email, '@') == '@php.net';
}

function output_note($com_id, $ts, $email, $comment, $comment_type = null)
{
	global $edit, $bug_id, $is_trusted_developer;

	// $com_id = 0 means the bug report itself is being displayed, not a comment
	if ($com_id === 0) {
		echo '<div class="report" id="report">';
		echo output_vote_buttons($bug_id, 2);
	} else {
		echo '<div class="report comment" data-type="' . $comment_type . '" id="comment-' . $com_id . '">';
	}

	if (is_php_user($email)) {
		echo '<a href="//people.php.net/' . urlencode($user) . '" class="name user">' . spam_protect(esc($email)) . '</a>';
	} else {
		echo '<span class="name">' . spam_protect($email) . '</span>';
	}
	echo '<a class="genanchor" href="#' . ($com_id == 0 ? 'report' : 'comment-' . $com_id) . '"> &para;</a>';

	// Delete comment action only for trusted developers
	echo ($edit == 1 && $com_id !== 0 && $is_trusted_developer && $comment_type != 'log')
		? "<a href='bug.php?id={$bug_id}&amp;edit=1&amp;delete_comment={$com_id}'>[delete]</a>\n"
		: '';

	echo '<time class="date" datetime="' . format_date($ts, DATE_W3C) .'">' . format_date($ts) . '</time>';

	// For text of the report itself strip first two lines (being "Description:\n------------")
	if ($com_id === 0) {
		$comment = implode("\n", array_slice(explode("\n", $comment), 2));
	}

	if ($comment_type != 'log') {
		$comment = '<pre>' . make_ticket_links(addlinks($comment)) . '</pre>';
	}

	echo '<div class="text">' . $comment . '</div>';

	echo '</div>';
}

function output_vote_buttons($bug_id, $score = 0)
{
?>
	<div class="votes">
		<div id="Vu<?= $bug_id ?>">
			<a href="" title="Vote up!" class="usernotes-voteu">up</a>
		</div>
		<div id="Vd<?= $bug_id ?>">
			<a href="" title="Vote down!" class="usernotes-voted">down</a>
		</div>
		<div class="tally"><?= $score ?></div>
	</div>
<?php
}

function delete_comment($bug_id, $com_id)
{
	global $dbh;
	
	$res = $dbh->prepare("DELETE FROM bugdb_comments WHERE bug='{$bug_id}' AND id='{$com_id}'")->execute();
}

function control($num, $desc)
{
	global $bug_id, $edit;

	$str = "<span";
	if ($edit == $num) {
		$str .= " class='active'>{$desc}";
	} else {
		$str .= "><a href='bug.php?id={$bug_id}" . (($num) ? "&amp;edit={$num}" : '') . "'>{$desc}</a>";
	}
	return "{$str}</span>\n";
}

function canvote($thanks, $status)
{
	return ($thanks != 4 && $thanks != 6 && $status != 'Closed' && $status != 'Not a bug' && $status != 'Duplicate');
}
