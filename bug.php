<?php /* vim: set noet ts=4 sw=4: : */
$id = (int)$id;
if (!$id) {
  header("Location: /");
  exit;
}
$edit = (int)$edit;

require_once 'prepend.inc';
require_once 'cvs-auth.inc';

$mail_bugs_to = 'php-bugs@lists.php.net';

if (isset($save) && isset($pw)) { # non-developers don't have $user set
	setcookie("MAGIC_COOKIE",
	          base64_encode("$user:$pw"),
	          time()+3600*24*12,
	          '/','.php.net');
}
if (isset($MAGIC_COOKIE) && !isset($user) && !isset($pw)) {
  list($user,$pw) = explode(":", base64_decode($MAGIC_COOKIE));
}

@mysql_pconnect("localhost","nobody","")
	or die("Unable to connect to SQL server.");
@mysql_select_db("php3");

# fetch info about the bug into $bug
$query = "SELECT id,bug_type,email,passwd,sdesc,ldesc,"
       . "php_version,php_os,status,ts1,ts2,assign,"
       . "UNIX_TIMESTAMP(ts1) AS submitted, UNIX_TIMESTAMP(ts2) AS modified,"
       . "COUNT(bug=id) AS votes,"
       . "SUM(reproduced) AS reproduced,SUM(tried) AS tried,"
       . "SUM(sameos) AS sameos, SUM(samever) AS samever,"
       . "AVG(score)+3 AS average,STD(score) AS deviation"
       . " FROM bugdb LEFT JOIN bugdb_votes ON id=bug WHERE id=$id"
       . " GROUP BY bug";

$res = mysql_query($query);

if ($res) $bug = mysql_fetch_array($res,MYSQL_ASSOC);
if (!$res || !$bug) {
  commonHeader("No such bug.");
  echo "<h1 class=\"error\">No such bug #$id!</h1>";
  commonFooter();
  exit;
}

# handle any updates, displaying errors if there were any
$success = !isset($in);
$errors = array();

if ($in && $edit == 3) {
	if (!preg_match("/[.\\w+-]+@[.\\w-]+\\.\\w{2,}/i",$in['commentemail'])) {
		$errors[] = "You must provide a valid email address.";
	}

	$ncomment = trim($ncomment);
	if (!$ncomment) {
		$errors[] = "You must provide a comment.";
	}

	if (!$errors) {
		$query = "INSERT INTO bugdb_comments (bug,email,ts,comment) VALUES"
		       . " ('$id','$in[commentemail]',NOW(),'$ncomment')";
		$success = @mysql_query($query);
	}
	$from = stripslashes($in['commentemail']);
}
elseif ($in && $edit == 2) {
	if (!$bug[passwd] || $bug[passwd] != stripslashes($pw)) {
		$errors[] = "The password you supplied was incorrect.";
	}

	$ncomment = trim($ncomment);
	if (!$ncomment) {
		$errors[] = "You must provide a comment.";
	}

	if (!$errors && !($errors = incoming_details_are_valid($in))) {
		/* update bug record */
		$query = "UPDATE bugdb SET sdesc='$in[sdesc]',status='$in[status]', bug_type='$in[bug_type]', php_version='$in[php_version]', php_os='$in[php_os]', ts2=NOW(), email='$in[email]' WHERE id=$id";
		$success = @mysql_query($query);
		
		/* add comment */
		if ($success && !empty($ncomment)) {
			$query = "INSERT INTO bugdb_comments (bug, email, ts, comment) VALUES ($id,'$in[email]',NOW(),'$ncomment')";
			$success = @mysql_query($query);
		}
	}
	$from = stripslashes($in['email']);
}
elseif ($in && $edit == 1) {
	if (!verify_password($user,stripslashes($pw))) {
		$errors[] = "The username or password you supplied was incorrect.";
	}

	if ($in['resolve']) {
		if (!$trytoforce && $RESOLVE_REASONS[$in['resolve']]['status'] == $bug['status']) {
			$errors[] = "The bug is already marked '$bug[status]'. (Submit again to ignore this.)";
		}
		elseif (!$errors)  {
			if ($in['status'] == $bug['status']) {
				$in['status'] = $RESOLVE_REASONS[$in['resolve']]['status'];
			}
			$ncomment = $RESOLVE_REASONS[$in['resolve']]['message']
			          . "\n\n$ncomment";
		}
	}
	if (!$errors && !($errors = incoming_details_are_valid($in))) {
		$query = "UPDATE bugdb SET sdesc='$in[sdesc]',status='$in[status]', bug_type='$in[bug_type]', assign='$in[assign]', php_version='$in[php_version]', php_os='$in[php_os]', ts2=NOW() WHERE id=$id";
		$success = @mysql_query($query);
		if ($success && !empty($ncomment)) {
			$query = "INSERT INTO bugdb_comments (bug, email, ts, comment) VALUES ($id,'$user@php.net',NOW(),'$ncomment')";
			$success = @mysql_query($query);
		}
	}
	$from = "$user@php.net";
}

if ($in && !$errors && $success) {
	mail_bug_updates($bug,$in,$from,$ncomment);
	header("Location: $PHP_SELF?id=$id&thanks=$edit");
	exit;
}

commonHeader("#$id: ".htmlspecialchars($bug['sdesc']));

/* DISPLAY BUG */
if ($thanks == 1 || $thanks == 2) {
  echo '<div class="thanks">The bug was updated successfully.</div>';
}
elseif ($thanks == 3) {
  echo '<div class="thanks">Your comment was added to the bug successfully.</div>';
}
elseif ($thanks == 4) {?>
<div class="thanks">
Thank you for your help! If the status of the bug report you submitted changes,
you will be notified. You may return here and check on the status or update
your report at any time. That URL for your bug report is: <a
href="http://bugs.php.net/<?php echo $id?>">http://bugs.php.net/<?php echo
$id?></a>.
</div>
<?php
}
elseif ($thanks == 6) {?>
<div class="thanks">
Thanks for voting! Your vote should be reflected in the
statistics below.
</div>
<?php
}
?>

<div id="bugheader">
 <table id="details">
  <tr id="title">
   <th id="number">Bug&nbsp;#<?php echo $id?></th>
   <td id="summary" colspan="5"><?php echo htmlspecialchars($bug['sdesc'])?></td>
  </tr>
  <tr id="submission">
   <th>Submitted:</th><td><?php echo format_date($bug['submitted'])?></td>
<?php if ($bug['modified']) {?>
   <th>Modified:</th><td> <?php echo format_date($bug['modified'])?></td>
<?php }?>
  </tr>
  <tr id="submitter">
   <th>From:</th><td><?php echo htmlspecialchars($bug['email'])?></td>
  </tr>
  <tr id="categorization">
   <th>Status:</th><td><?php echo htmlspecialchars($bug['status'])?></td>
<?php/*   <th>Type:</th><td><?php echo htmlspecialchars($bug['type'])?></td> */?>
   <th>Category:</th><td colspan="3"><?php echo htmlspecialchars($bug['bug_type'])?></td>
  </tr>
  <tr id="situation">
   <th>Version:</th><td><?php echo htmlspecialchars($bug['php_version'])?></td>
   <th>OS:</th><td colspan="3"><?php echo htmlspecialchars($bug['php_os'])?></td>
  </tr>

<?php if ($bug['votes']) {?>
  <tr id="votes">
   <th>Votes:</th><td><?php echo $bug['votes'];?></td>
   <th>Avg. Score:</th><td><?php printf("%.1f &plusmn; %.1f", $bug['average'], $bug['deviation'])?></td>
   <th>Reproduced:</th><td><?php printf("%d of %d (%.1f%%)",$bug['reproduced'],$bug['tried'],$bug['tried']?($bug['reproduced']/$bug['tried'])*100:0);?></td>
  </tr>
<?php if ($bug['reproduced']) {?>
  <tr id="reproduced">
   <td colspan="2"></td>
   <th>Same Version:</th><td><?php printf("%d (%.1f%%)",$bug['samever'],($bug['samever']/$bug['reproduced'])*100);?></td>
   <th>Same OS:</th><td><?php printf("%d (%.1f%%)",$bug['sameos'],($bug['sameos']/$bug['reproduced'])*100);?></td>
  </tr>
<?php }?>
<?php }?>
</table>
</div>

<div id="controls">
<?php
function control($num,$desc) {
  $active = ($GLOBALS['edit'] == $num);
  echo "<span id=\"control_$num\" class=\"control", ($active ? ' active' : ''), "\">",
       !$active ? "<a href=\"$PHP_SELF?id=$GLOBALS[id]".($num ? "&amp;edit=$num" : "")."\">" : "",
       $desc, !$active ? "</a>" : "", "</span> ";
}

control(0,'View/Vote');
control(3,'Add Comment');
control(1,'Developer');
control(2,'Edit Submission');
?>
</div>
<br clear="all" />

<?php
if ($errors) display_errors($errors);
if (!$errors && !$success) {?>
<div class="errors">
Some sort of database error has happened. Maybe this will be illuminating:
<?php echo mysql_error();?> This was the last query attempted: <tt><?php echo htmlspecialchars($query)?></tt>
</div>
<?php
}

if ($edit == 1 || $edit == 2) {?>
<form id="update" action="<?php echo $PHP_SELF?>" method="post">
<?php
if ($edit == 2) {
	if (!$in && $pw && $bug['passwd'] && stripslashes($pw)==$bug['passwd']) {?>
<div class="explain">
Welcome back! Since you opted to store your bug's password in a cookie, you can
just go ahead and add more information to this bug or edit the other fields.
</div>
<?php
	}
	else {?>
<div class="explain">
<?php if (!$in) {?>
Welcome back! If you're the original bug submitter, here's where you can edit
the bug or add additional notes. If this is not your bug, you can <a
href="<?php echo "$PHP_SELF?id=$id&amp;edit=3"?>">add a comment by following
this link</a> or the box above that says 'Add Comment'. If this is your bug,
but you forgot your password, <a href="bug-pwd-finder.php">you can retrieve
your password here</a>.
<?php }?>
<table>
 <tr>
  <th>Password:</th>
  <td><input type="password" name="pw" value="<?php echo clean($pw)?>" size="10" maxlength="20" /></td>
  <th>
   <label for="save">Check to remember your password for next time:</label>
  </th>
  <td>
   <input type="checkbox" id="save" name="save"<?php if ($save) echo ' checked="checked"'?> />
  </td>
 </tr>
</table>
</div>
<?php
	}
}
else {
	if ($user && $pw && verify_password($user,stripslashes($pw))) {
		if (!$in) {?>
<div class="explain">
Welcome back, <?php echo $user?>! (Not <?php echo $user?>? <a href="logout.php">Log out.</a>)
</div>
<?php
		}
	}
	else {?>
<div class="explain">
<?php if (!$in) {?>
Welcome! If you don't have a CVS account, you can't do anything here. You can
<a href="<?php echo "$PHP_SELF?id=$id&amp;edit=3"?>">add a comment by following
this link</a> or if you reported this bug, you can <a href="<?php echo
"$PHP_SELF?id=$id&amp;edit=2"?>">edit this bug over here</a>.
<?php }?>
<table>
 <tr>
  <th>CVS Username:</th>
  <td><input type="text" name="user" value="<?php echo clean($user)?>" size="10" maxlength="20" /></td>
  <th>CVS Password:</th>
  <td><input type="password" name="pw" value="<?php echo clean($pw)?>" size="10" maxlength="20" /></td>
  <th>
   <label for="save">Remember:</label>
  </th>
  <td>
   <input type="checkbox" id="save" name="save"<?php if ($save) echo ' checked="checked"'?> />
  </td>
 </tr>
</table>
</div>
<?php
  }
}
?>
<table>
<?php if ($edit == 1) {?>
 <tr>
  <th>Quick Fix:</th>
  <td colspan="5"><select name="in[resolve]"><?php show_reason_types($in['resolve'],1);?></select><?php if ($in['resolve']) {?><input type="hidden" name="trytoforce" value="1" /><?php }?></td>
 </tr>
<?php }?>
 <tr>
  <th>Status:</th>
  <td><select name="in[status]"><?php show_state_options($in['status'],$edit,$bug['status'])?></select></td>
<?php if ($edit ==1) {?>
  <th>Assign to:</th>
  <td><input type="text" size="10" maxlength="16" name="in[assign]" value="<?php echo field('assign')?>" /></td>
<?php }?>
  <td><input type="hidden" name="id" value="<?php echo $id?>" /><input type="hidden" name="edit" value="<?php echo $edit?>" /><input type="submit" value="Submit" /></td>
 </tr>
 <tr>
  <th>Category:</th>
  <td colspan="3"><select name="in[bug_type]"><?php show_types($in['bug_type'],0,$bug['bug_type'])?></select></td>
<?php /* severity goes here. */ ?>
 </tr>
 <tr>
  <th>Summary:</th>
  <td colspan="5"><input type="text" size="60" maxlength="80" name="in[sdesc]" value="<?php echo field('sdesc')?>" /></td>
 </tr>
 <tr>
  <th>From:</th>
  <td colspan="5"><input type="text" size="40" maxlength="40" name="in[email]" value="<?php echo field('email')?>" /></td>
 </tr>
 <tr>
  <th>Version:</th>
  <td><input type="text" size="20" maxlength="16" name="in[php_version]" value="<?php echo field('php_version')?>" /></td>
  <th>OS:</th>
  <td colspan="3"><input type="text" size="20" maxlength="32" name="in[php_os]" value="<?php echo field('php_os')?>" /></td>
 </tr>
</table>
<b>New<?php if ($edit==1) echo "/Additional"?> Comment:</b><br />
<textarea cols="60" rows="8" name="ncomment" wrap="physical"><?php echo clean($ncomment)?></textarea>
<br /><input type="submit" value="Submit" />
</form>
<?php }?>
<?php if ($edit == 3) {?>
<form id="comment" action="<?php echo $PHP_SELF?>" method="post">
<?php if (!$in) {?>
<div class="explain">
Anyone can comment on a bug. Have a simpler test case? Does it work for you on
a different platform? Let us know! Just going to say 'Me too!'? Don't clutter
the database with that &mdash; but make sure to <a href="<?php echo "$PHP_SELF?id=$id"?>">vote on the bug</a>!
</div>
<?php }?>
<table>
 <tr>
  <th>Your email address:</th>
  <td><input type="text" size="40" maxlength="40" name="in[commentemail]" value="<?php echo clean($in['commentemail'])?>" /></td>
  <td><input type="hidden" name="id" value="<?php echo $id?>" /><input type="hidden" name="edit" value="<?php echo $edit?>" /><input type="submit" value="Submit" /></td>
 </tr>
</table>
<div>
 <textarea cols="60" rows="10" name="ncomment" wrap="physical"><?php echo clean($ncomment);?></textarea>
 <br /><input type="submit" value="Submit" />
</div>
</form>
<?php }?>
<?php if (!$edit && $thanks != 4 && $thanks != 6 && $bug['status'] != "Closed" && $bug['status'] != "Bogus" && $bug['status'] != 'Duplicate') {?>
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
   <input type="hidden" name="id" value="<?php echo $id?>" />
   <input type="submit" value="Vote" />
  </div>
  </form>
  <br clear="all" />
<?php }

/* ORIGINAL REPORT */
if ($bug['ldesc'])
  output_note($bug['submitted'], $bug['email'], $bug['ldesc']);

/* COMMENTS */
$query = "SELECT email,comment,UNIX_TIMESTAMP(ts) AS added"
       . " FROM bugdb_comments WHERE bug=$id ORDER BY ts";
$res = @mysql_query($query);
if ($res) {
	while ($row = mysql_fetch_array($res,MYSQL_ASSOC)) {
		output_note($row['added'], $row['email'], $row['comment']);
	}
}

commonFooter();

function output_note($ts,$email,$comment) {
	echo "<div class=\"comment\">";
	echo "<b>[",format_date($ts),"] ",htmlspecialchars($email),
         "</b>\n";
	echo "<pre class=\"note\">";
	echo addlinks(preg_replace("/(\r?\n){3,}/","\n\n",wordwrap($comment,72,"\n",1)));
	echo "</pre>\n";
	echo "</div>";
}
