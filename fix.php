<?php
$id = (int)$id;
if (!$id) {
  header("Location: /");
  exit;
}

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

$errors = array();

if (!isset($user) || !isset($pw)) {
  $errors[] = "You have to log in to resolve a bug this way.";
}
elseif (!verify_password($user,$pw)) {
  $errors[] = "The username or password you supplied was incorrect.";
}
if (!isset($r) || !isset($RESOLVE_REASONS[$r])) {
  $errors[] = "You have to use a valid reason to resolve this bug.";
}

if ($errors) {
  commonHeader("Resolve Bug");
  display_errors($errors);
?>
<form method="post" action="<?php echo $PHP_SELF?>">
<input type="hidden" name="id" value="<?php echo $id?>" />
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
 <tr>
  <th>Reason:</th>
  <td colspan="5"><select name="r"><?php echo show_reason_types($r)?></select></td>
 </tr>
</table>
<input type="submit" value="Resolve" />
</form>
<?php
  commonFooter();
  exit;
}

@mysql_pconnect("localhost","nobody","")
	or die("Unable to connect to SQL server.");
@mysql_select_db("php3");

# fetch info about the bug into $bug
$query = "SELECT id,bug_type,email,passwd,sdesc,ldesc,"
       . "php_version,php_os,status,ts1,ts2,assign,"
       . "UNIX_TIMESTAMP(ts1) AS submitted, UNIX_TIMESTAMP(ts2) AS modified"
       . " FROM bugdb WHERE id=$id";

$res = mysql_query($query);

if ($res) $bug = mysql_fetch_array($res,MYSQL_ASSOC);
if (!$res || !$bug) {
  commonHeader("No such bug.");
  echo "<h1 class=\"error\">No such bug #$id!</h1>";
  commonFooter();
  exit;
}

/* update bug record */
$status = $RESOLVE_REASONS[$r]['status'];
$ncomment = $RESOLVE_REASONS[$r]['message'];

/* if the already has the status of the resolution, bounce over to the
   main bug form. it will show the appropriate error message. */
if ($status == $bug['status']) {
  header("Location: bug.php?id=$id&edit=1&in[resolve]=$r");
  exit;
}

$query = "UPDATE bugdb SET status='$status', ts2=NOW() WHERE id=$id";
$success = @mysql_query($query);
		
/* add comment */
if ($success && !empty($ncomment)) {
  $query = "INSERT INTO bugdb_comments (bug, email, ts, comment) VALUES ($id,'$user@php.net',NOW(),'".addslashes($ncomment)."')";
  $success = @mysql_query($query);
}

if ($success) {
  $in = array('status' => $status);
  mail_bug_updates($bug,$in,"$user@php.net",$ncomment);
  header("Location: bug.php?id=$id&thanks=1");
  exit;
}
else {
  commonHeader("Resolve Bug: Problem");
?>
<p>Something went wrong trying to update the bug.</p>
<?php
  echo '<pre>',mysql_error(),"</pre>\n";
  commonFooter();
}
