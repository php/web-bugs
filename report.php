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

$mail_bugs_to = "php-bugs@lists.php.net";

@mysql_pconnect("localhost","nobody","")
    or die("Unable to connect to SQL server.");
@mysql_select_db("php3");

$errors = array();
if ($in) {
    if (!($errors = incoming_details_are_valid($in,1))) {
        $query = "INSERT INTO bugdb (bug_type,email,sdesc,ldesc,php_version,php_os,status,ts1,passwd) VALUES ('$in[bug_type]','$in[email]','$in[sdesc]','$in[ldesc]','$in[php_version]','$in[php_os]','Open',NOW(),'$in[passwd]')";
        $ret = mysql_query($query);
    
        $cid = mysql_insert_id();

        $report = "";
        $report .= "From:             ".stripslashes($in['email'])."\n";
        $report .= "Operating system: ".stripslashes($in['php_os'])."\n";
        $report .= "PHP version:      ".stripslashes($in['php_version'])."\n";
        $report .= "PHP Bug Type:     $in[bug_type]\n";
        $report .= "Bug description:  ";

        $ldesc = stripslashes($in['ldesc']);
        $sdesc = stripslashes($in['sdesc']);

        $ascii_report = "$report$sdesc\n\n".wordwrap($ldesc);
        $ascii_report.= "\n-- \nEdit bug report at http://bugs.php.net/?id=$cid&edit=";
        
        list($mailto,$mailfrom) = get_bugtype_mail($in['bug_type']);

        $email = stripslashes($in['email']);

        # provide shortcut URLS for "quick bug fixes"
        $dev_extra = ""; 
        $maxkeysize = 0;
        foreach ($RESOLVE_REASONS as $v) {
            if (!$v['webonly']) {
                $actkeysize = strlen($v['desc']) + 1;
                $maxkeysize = (($maxkeysize < $actkeysize) ? $actkeysize : $maxkeysize);
            }
        }
        foreach ($RESOLVE_REASONS as $k => $v) {
            if (!$v['webonly'])
                $dev_extra .= str_pad($v['desc'] . ":", $maxkeysize) .
                              " http://bugs.php.net/fix.php?id=$cid&r=$k\n";
        }

        # mail to appropriate mailing lists
        if (mail($mailto, "Bug #$cid: $sdesc", $ascii_report."1\n-- \n$dev_extra", "From: $email\nX-PHP-Bug: $cid\nMessage-ID: <bug-$cid@bugs.php.net>")) {
            # mail to reporter
            @mail($email, "Bug #$cid: $sdesc", $ascii_report."2\n", "From: PHP Bug Database <$mailfrom>\nX-PHP-Bug: $cid\nMessage-ID: <bug-$cid@bugs.php.net>");

            header("Location: bug.php?id=$cid&thanks=4");
            exit;

        } else {
        
            commonHeader("Report - Error");
            
            echo "<pre>\n";

            echo $report;

            echo htmlspecialchars($sdesc), "\n\n";

            echo wordwrap(htmlspecialchars($ldesc));

            echo "</pre>\n";

            echo "<p><h2>Mail not sent!</h2>\n";
            echo "Please send this page in a mail to " .
                 "<a href=\"mailto:$mailto\">$mailto</a> manually.</p>\n";
        }
    }

    commonHeader("Report - Problems");
}

if (!isset($in)) {
    commonHeader("Report - New");
?>

<p>Before you report a bug, make sure to search for similar bugs using the form
at the top of the page or our <a href="search.php">advanced search page</a>.
Also, read the instructions for <a href="how-to-report.php">how to report a bug
that someone will want to help fix</a>.</p>

<p>If you aren't sure that what you're about to report is a bug, you should ask for help using one of the means for support <a href="http://www.php.net/support.php">listed here</a>.</p>

<p><strong>Failure to follow these instructions may result in your bug
simply being marked as "bogus".</strong></p>

<?php
}

if ($errors) display_errors($errors);
?>
<form method="post" action="<?php echo $PHP_SELF;?>">
<table>
 <tr>
  <th align="right">Your email address:</th>
  <td colspan="2">
   <input type="text" size="20" maxlength="40" name="in[email]" value="<?php echo clean($in['email']);?>" />
  </td>
 </tr><tr>
  <th align="right">PHP version:</th>
  <td>
   <select name="in[php_version]"><?php show_version_options($in['php_version']);?></select>
  </td>
 </tr><tr>
  <th align="right">Type of bug:</th>
  <td colspan="2">
    <select name="in[bug_type]"><?php show_types($in['bug_type'],0);?></select>
  </td>
 </tr><tr>
  <th align="right">Operating system:</th>
  <td colspan="2">
   <input type="text" size="20" maxlength="32" name="in[php_os]" value="<?php echo clean($in['php_os']);?>" />
  </td>
 </tr><tr>
  <th align="right">Summary:</th>
  <td colspan="2">
   <input type="text" size="40" maxlength="79" name="in[sdesc]" value="<?php echo clean($in['sdesc']);?>" />
  </td></tr>
 </tr><tr>
  <th align="right">Password:</th>
  <td>
   <input type="password" size="20" maxlength="20" name="in[passwd]" value="<?php echo clean($in['passwd']);?>" />
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
   <textarea cols="60" rows="15" name="in[ldesc]" wrap="physical"><?php echo clean($in['ldesc']);?></textarea>
  </td>
 </tr>
</table>
</form>
<?php
commonFooter();
