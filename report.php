<?php /* vim: set noet ts=4 sw=4: : */
require_once 'prepend.inc';
require_once 'cvs-auth.inc';

if (isset($save) && isset($pw)) { # non-developers don't have $user set
  setcookie("MAGIC_COOKIE",base64_encode("$user:$pw"),time()+3600*24*12,'/','.php.net');
}
if (isset($MAGIC_COOKIE) && !isset($user) && !isset($pw)) {
  list($user,$pw) = explode(":", base64_decode($MAGIC_COOKIE));
}

/* See bugs.sql for the table layout. */

$mail_bugs_to = "php-dev@lists.php.net";

@mysql_connect("localhost","nobody","")
	or die("Unable to connect to SQL server.");
@mysql_select_db("php3");

commonHeader("Report");

if ($cmd == "send") {
	if (incoming_details_are_valid(1,1)) {
		$ret = mysql_query("INSERT INTO bugdb (bug_type,email,sdesc,ldesc,php_version,php_os,status,ts1,passwd) VALUES ('$bug_type','$email','$sdesc','$ldesc','$php_version','$php_os','Open',NOW(),'$passwd')");
    
		$cid = mysql_insert_id();

		$report = "";

		$ldesc = stripslashes($ldesc);
		$sdesc = stripslashes($sdesc);

		echo "<pre>\n";

		$report .= "From:             $email\n";
		$report .= "Operating system: $php_os\n";
		$report .= "PHP version:      $php_version\n";
		$report .= "PHP Bug Type:     $bug_type\n";
		$report .= "Bug description:  ";

		echo $report;

        echo htmlspecialchars($sdesc), "\n\n";

        echo wordwrap(htmlspecialchars($ldesc));

		echo "</pre>\n";

		$ascii_report = "$report$sdesc\n\n".wordwrap($ldesc);
		$ascii_report.= "\n-- \nEdit bug report at: http://bugs.php.net/?id=$cid&edit=1\n";

		list($mailto,$mailfrom) = get_bugtype_mail($bug_type);

		if (mail($mailto, "Bug #$cid: $sdesc", $ascii_report, "From: $email\nX-PHP-Bug: $cid\nMessage-ID: <bug-$cid@bugs.php.net>")) {
		    @mail($email, "Bug #$cid: $sdesc", $ascii_report, "From: PHP Bug Database <$mailfrom>\nX-PHP-Bug: $cid\nMessage-ID: <bug-$cid@bugs.php.net>");
			echo "<p><h2>Mail sent to $mailto...</h2></p>\n";
			echo "<p>Thank you for your help!</p>";
			echo "<p><i>The password for this report is</i>: <b>".htmlentities($passwd)."</b><br>";
			echo "If the status of the bug report you submitted\n";
			echo "changes, you will be notified. You may return here and check on the status\n";
			echo "or update your report at any time. The URL for your bug report is: <a href=\"http://bugs.php.net/?id=$cid\">";
			echo "http://bugs.php.net/?id=$cid</a></p>\n";
		} else {
			echo "<p><h2>Mail not sent!</h2>\n";
			echo "Please send this page in a mail to " .
			     "<a href=\"mailto:$mailto\">$mailto</a> manually.</p>\n";
	    }
	}
} elseif (!isset($cmd)) {
?>

<p>Before you report a bug, make sure to search for similar bugs using the form
at the top of the page or our <a href="search.php">advanced search page</a>.
Also, read the instructions for <a href="how-to-report.php">how to report a bug
that someone will want to help fix</a>.</p>

<p>If you aren't sure that what you're about to report is a bug, you should ask for help using one of the means for support <a href="http://www.php.net/support.php">listed here</a>.</p>

<p><strong>Failure to follow these instructions may result in your bug being
simply being marked as "bogus".</strong></p>

<?php
}
?>
<form method="POST" action="<?php echo $PHP_SELF;?>">
<input type="hidden" name="cmd" value="send" />
<table>
 <tr>
  <th align="right">Your email address:</th>
  <td colspan="2">
   <input type="text" size="20" maxlength="40" name="email" value="<?php echo htmlspecialchars(stripslashes($email));?>" />
  </td>
 </tr><tr>
  <th align="right">PHP version:</th>
  <td>
   <select name="php_version"><?php show_version_options($php_version);?></select>
  </td>
 </tr><tr>
  <th align="right">Type of bug:</th>
  <td colspan="2">
    <select name="bug_type"><?php show_types($bug_type,0);?></select>
  </td>
 </tr><tr>
  <th align="right">Operating system:</th>
  <td colspan="2">
   <input type="text" size="20" maxlength="32" name="php_os" value="<?php echo htmlspecialchars(stripslashes($php_os));?>" />
  </td>
 </tr><tr>
  <th align="right">Summary:</th>
  <td colspan="2">
   <input type="text" size="40" maxlength="79" name="sdesc" value="<?php echo htmlspecialchars(stripslashes($sdesc));?>" />
  </td></tr>
 </tr><tr>
  <th align="right">Password:</th>
  <td>
   <input type="text" size="20" maxlength="20" name="passwd" value="<?php echo htmlspecialchars(stripslashes($passwd));?>" />
  </td>
  <td><font size="-2">
    You may enter any password here. This password allows you to come back and
    modify your submitted bug report at a later date. [<a
    href="/bug-pwd-finder.php">Lost your password?</a>]
  </font></td>
 </tr>
</table>

<table>
 <tr>
  <td valign="top">
   <b>Description:</b><br /><font size="-1">
   Please supply any information that may be helpful in fixing the bug:
   <ul>
    <li>A short script that reproduces the problem.</li>
    <li>The list of modules you compiled PHP with (your configure line).</li>
    <li>Any other information unique or specific to your setup.</li>
    <li>A <a href="bugs-generating-backtrace.php">gdb backtrace</a>.</li>
   </ul>
   </font>
   <div align="center"><input type="submit" value="Send bug report" /></div>
  </td>
  <td>
   <textarea cols="60" rows="15" name="ldesc" wrap="physical"><?php echo htmlspecialchars(stripslashes($ldesc));?></textarea>
  </td>
 </tr>
</table>
</form>
<?php
commonFooter();
