<?php /* vim: set noet ts=4 sw=4: : */
session_start();
/**
 * Procedures for reporting bugs
 *
 * See pearweb/sql/bugs.sql for the table layout.
 *
 * This source file is subject to version 3.0 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * @category  pearweb
 * @package   Bugs
 * @copyright Copyright (c) 1997-2005 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License
 * @version   $Id$
 */

/**
 * Obtain common includes
 */
require_once './include/prepend.inc';

/**
 * Get user's CVS password
 */
require_once './include/cvs-auth.inc';

/**
 * Numeral Captcha Class
 */
require_once 'Text/CAPTCHA/Numeral.php';

$errors              = array();
$ok_to_submit_report = false;

/**
 * Instantiate the numeral captcha object.
 */
$numeralCaptcha = new Text_CAPTCHA_Numeral();

if (isset($_POST['save']) && isset($_POST['pw'])) {
    // non-developers don't have $user set
    setcookie('MAGIC_COOKIE', base64_encode(':' . $_POST['pw']),
              time() + 3600 * 24 * 12, '/', '.php.net');
}

// captcha is not necessary if the user is logged in
if (isset($auth_user) && $auth_user->registered) {
    if (!auth_check('pear.dev') && auth_check('pear.voter') && !auth_check('pear.bug')) {
        // auto-grant bug tracker karma if it isn't present
        require_once 'Damblan/Karma.php';
        $karma = new Damblan_Karma($dbh);
        $karma->grant($auth_user->user, 'pear.bug');
    }
    if (isset($_SESSION['answer'])) {
        unset($_SESSION['answer']);
    }
    if (isset($_POST['in'])) {
        $_POST['in']['email'] = $auth_user->email;
    }
}

if (isset($_POST['in'])) {
    $errors = incoming_details_are_valid($_POST['in'], 1, (isset($auth_user) && $auth_user->registered));

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

    // try to verify the user
    if (isset($auth_user)) {
        $_POST['in']['handle'] = $auth_user->handle;
    }

    if (!$errors) {

        /*
         * When user submits a report, do a search and display
         * the results before allowing them to continue.
         */
        if (!isset($_POST['in']['did_luser_search']) || !$_POST['in']['did_luser_search']) {

            $_POST['in']['did_luser_search'] = 1;

            // search for a match using keywords from the subject
            $sdesc = rinse($_POST['in']['sdesc']);

            /*
             * If they are filing a feature request,
             * only look for similar features
             */
            $package_name = $_POST['in']['package_name'];
            $where_clause = 'WHERE bugdb.package_name=p.name ';
            if ($package_name == 'Feature/Change Request') {
                $where_clause .= "AND package_name = '$package_name'";
            } else {
                $where_clause .= "AND package_name != 'Feature/Change Request'";
            }

            list($sql_search, $ignored) = format_search_string($sdesc);

            $where_clause .= $sql_search;

            $where_clause .= ' AND p.package_type="pear"';

            $query = "SELECT bugdb.* from bugdb, packages p $where_clause LIMIT 5";

            $res =& $dbh->query($query);

            if ($res->numRows() == 0) {
                $ok_to_submit_report = 1;
            } else {
                response_header("Report - Confirm");
                if (count($_FILES)) {
                    echo '<h1>WARNING: YOU MUST RE-UPLOAD YOUR PATCH, OR IT WILL BE IGNORED</h1>';

                }

                ?>

<p>
 Are you sure that you searched before you submitted your bug report? We
 found the following bugs that seem to be similar to yours; please check
 them before sumitting the report as they might contain the solution you
 are looking for.
</p>

<p>
 If you're sure that your report is a genuine bug that has not been reported
 before, you can scroll down and click the submit button to really enter the
 details into our database.
</p>

<div class="warnings">

<table class="lusersearch">
 <tr>
  <th>Description</th>
  <th>Possible Solution</th>
 </tr>

                <?php

                while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {

                    $resolution =& $dbh->getOne('SELECT comment FROM' .
                            ' bugdb_comments where bug = ' .
                            $row['id'] . ' ORDER BY id DESC LIMIT 1');

                    if ($resolution) {
                        $resolution = htmlspecialchars($resolution);
                    }

                    $summary = $row['ldesc'];
                    if (strlen($summary) > 256) {
                        $summary = htmlspecialchars(substr(trim($summary),
                                                    0, 256)) . ' ...';
                    } else {
                        $summary = htmlspecialchars($summary);
                    }

                    $bug_url = "/bugs/bug.php?id=$row[id]&amp;edit=2";

                    echo " <tr>\n";
                    echo '  <td colspan="2"><strong>' . $row['package_name'] . '</strong> : <a href="' . $bug_url . '">Bug #';
                    echo $row['id'] . ': ' . htmlspecialchars($row['sdesc']);
                    echo "</a></td>\n";
                    echo " </tr>\n";
                    echo " <tr>\n";
                    echo '  <td>' . $summary . "</td>\n";
                    echo '  <td>' . nl2br($resolution) . "</td>\n";
                    echo " </tr>\n";

                }

                echo "</table>\n";
                echo "</div>\n";
            }
        } else {
            /*
             * We displayed the luser search and they said it really
             * was not already submitted, so let's allow them to submit.
             */
            $ok_to_submit_report = true;
        }

        do {
            if ($ok_to_submit_report) {
                if (!isset($auth_user)) {
                    $registereduser = 0;
                    // user doesn't exist yet
                    require 'bugs/pear-bug-accountrequest.php';
                    $buggie = new PEAR_Bug_Accountrequest;
                    $salt = $buggie->addRequest($_POST['in']['email']);
                    if (is_array($salt)) {
                        $errors = $salt;
                        response_header('Report - Problems');
                        break; // skip bug addition
                    }
                    if (PEAR::isError($salt)) {
                        $errors[] = $salt;
                        response_header('Report - Problems');
                        break;
                    }
                    if ($salt === false) {
                        $errors[] = 'Your account cannot be added to the queue.'
                             . ' Please write a mail message to the '
                             . ' <i>pear-dev</i> mailing list.';
                        response_header('Report - Problems');
                        break;
                    }

                    $_POST['in']['handle'] =
                    $_POST['in']['reporter_name'] = $buggie->handle;
                    try {
                        $buggie->sendEmail();
                    } catch (Exception $e) {
                        $errors[] = 'Critical internal error: could not send' .
                            ' email to your address ' . $_POST['in']['email'] .
                            ', please write a mail message to the <i>pear-dev</i>' .
                            'mailing list and report this problem with details.' .
                            '  We apologize for the problem, your report will help' .
                            ' us to fix it for future users: ' . $e->getMessage();
                        response_header('Report - Problems');
                        break;
                    }
                } else {
                    $registereduser = 1;
                    $_POST['in']['reporter_name'] = $auth_user->name;
                    $_POST['in']['handle'] = $auth_user->handle;
                }
                // Put all text areas together.
                $fdesc = "Description:\n------------\n" . $_POST['in']['ldesc'] . "\n\n";
                if (!empty($_POST['in']['repcode'])) {
                    $fdesc .= "Test script:\n---------------\n";
                    $fdesc .= $_POST['in']['repcode'] . "\n\n";
                }
                if (!empty($_POST['in']['expres']) ||
                    $_POST['in']['expres'] === '0')
                {
                    $fdesc .= "Expected result:\n----------------\n";
                    $fdesc .= $_POST['in']['expres'] . "\n\n";
                }
                if (!empty($_POST['in']['actres']) ||
                    $_POST['in']['actres'] === '0')
                {
                    $fdesc .= "Actual result:\n--------------\n";
                    $fdesc .= $_POST['in']['actres'] . "\n";
                }

                // shunt website bugs to the website package
                if (in_array($_POST['in']['package_name'], array(
                            'Web Site', 'PEPr', 'Bug System'), true)) {
                    $_POST['in']['package_name'] = 'pearweb';
                }

                $query = 'INSERT INTO bugdb (
                          registered,
                          package_name,
                          bug_type,
                          email,
                          handle,
                          sdesc,
                          ldesc,
                          package_version,
                          php_version,
                          php_os,
                          status, ts1,
                          passwd,
                          reporter_name
                         ) VALUES (' . $registereduser . ',' . 
                         " '" . escapeSQL($_POST['in']['package_name']) . "'," .
                         " '" . escapeSQL($_POST['in']['bug_type']) . "'," .
                         " '" . escapeSQL($_POST['in']['email']) . "'," .
                         " '" . escapeSQL($_POST['in']['handle']) . "'," .
                         " '" . escapeSQL($_POST['in']['sdesc']) . "'," .
                         " '" . escapeSQL($fdesc) . "'," .
                         " '" . escapeSQL($_POST['in']['package_version']) . "'," .
                         " '" . escapeSQL($_POST['in']['php_version']) . "'," .
                         " '" . escapeSQL($_POST['in']['php_os']) . "'," .
                         " 'Open', NOW(), " .
                         " ''," .
                         " '" . escapeSQL($_POST['in']['reporter_name']) . "')";


                $dbh->query($query);

    /*
     * Need to move the insert ID determination to DB eventually...
     */
                if ($dbh->phptype == 'mysql') {
                    $cid = mysql_insert_id();
                } else {
                    $cid = mysqli_insert_id($dbh->connection);
                }

                Bug_DataObject::init();
                $link = Bug_DataObject::bugDB('bugdb_roadmap_link');
                $link->id = $cid;
                $link->delete();
                if (isset($_POST['in']['roadmap'])) {
                    foreach ($_POST['in']['roadmap'] as $rid) {
                        $link->id = $cid;
                        $link->roadmap_id = $rid;
                        $link->insert();
                    }
                }

                $redirectToPatchAdd = false;
                if (!empty($_POST['in']['patchname']) && $_POST['in']['patchname']) {
                    require_once 'bugs/patchtracker.php';
                    $tracker = new Bugs_Patchtracker;
                    PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
                    $patchrevision = $tracker->attach($cid, 'patchfile',
                        $_POST['in']['patchname'], $_POST['in']['handle'], array());
                    PEAR::staticPopErrorHandling();
                    if (PEAR::isError($patchrevision)) {
                        $redirectToPatchAdd = true;
                    }
                }

                if (!isset($buggie)) {
                    $report  = '';
                    $report .= 'From:             ' . $_POST['in']['handle'] . "\n";
                    $report .= 'Operating system: ' . rinse($_POST['in']['php_os']) . "\n";
                    $report .= 'Package version:  ' . rinse($_POST['in']['package_version']) . "\n";
                    $report .= 'PHP version:      ' . rinse($_POST['in']['php_version']) . "\n";
                    $report .= 'Package:          ' . $_POST['in']['package_name'] . "\n";
                    $report .= 'Bug Type:         ' . $_POST['in']['bug_type'] . "\n";
                    $report .= 'Bug description:  ';

                    $fdesc = rinse($fdesc);
                    $sdesc = rinse($_POST['in']['sdesc']);

                    $ascii_report  = "$report$sdesc\n\n" . wordwrap($fdesc);
                    $ascii_report .= "\n-- \nEdit bug report at ";
                    $ascii_report .= "http://$site.php.net/bugs/bug.php?id=$cid&edit=";

                    list($mailto, $mailfrom) = get_package_mail($_POST['in']['package_name']);

                    $email = rinse($_POST['in']['email']);
                    $protected_email  = '"' . spam_protect($email, 'text') . '"';
                    $protected_email .= '<' . $mailfrom . '>';

                    $extra_headers  = 'From: '           . $protected_email . "\n";
                    $extra_headers .= 'X-PHP-BugTracker: PEARbug' . "\n";
                    $extra_headers .= 'X-PHP-Bug: '      . $cid . "\n";
                    $extra_headers .= 'X-PHP-Type: '     . rinse($_POST['in']['bug_type']) . "\n";
                    $extra_headers .= 'X-PHP-PackageVersion: '  . rinse($_POST['in']['package_version']) . "\n";
                    $extra_headers .= 'X-PHP-Version: '  . rinse($_POST['in']['php_version']) . "\n";
                    $extra_headers .= 'X-PHP-Category: ' . rinse($_POST['in']['package_name']) . "\n";
                    $extra_headers .= 'X-PHP-OS: '       . rinse($_POST['in']['php_os']) . "\n";
                    $extra_headers .= 'X-PHP-Status: Open' . "\n";
                    $extra_headers .= 'Message-ID: <bug-' . $cid . '@'.$site.'.php.net>';

                    $type = @$types[$_POST['in']['bug_type']];

                    if (DEVBOX == false) {
                        // mail to package developers
                        @mail($mailto, "[$siteBig-BUG] $type #$cid [NEW]: $sdesc",
                              $ascii_report . "1\n-- \n$dev_extra", $extra_headers,
                              '-f bounce-no-user@php.net');
                        // mail to reporter
                        @mail($email, "[$siteBig-BUG] $type #$cid: $sdesc",
                              $ascii_report . "2\n",
                              "From: $siteBig Bug Database <$mailfrom>\n" .
                              "X-PHP-Bug: $cid\n" .
                              "Message-ID: <bug-$cid@$site.php.net>",
                              '-f bounce-no-user@php.net');
                    }
                }
                if ($redirectToPatchAdd) {
                    localRedirect('patch-add.php?bug=' . $cid . '&patch=' .
                        $_POST['in']['patchname'] . '&email=' .
                        $_POST['in']['email']);
                } elseif (!isset($buggie) && !empty($_POST['in']['patchname'])) {
                    require_once 'bugs/pear-bug-accountrequest.php';
                    $r = new PEAR_Bug_Accountrequest();
                    $info = $r->sendPatchEmail($cid, $patchrevision,
                        $_POST['in']['package_name'], $auth_user->handle);
                }
                localRedirect('bug.php?id=' . $cid . '&thanks=4');
                exit;
            }
        } while (false);
    } else {
        // had errors...
        response_header('Report - Problems');
    }

}  // end of if input


if (!package_exists($_REQUEST['package'])) {
    $errors[] = 'Package "' . clean($_REQUEST['package']) . '" does not exist.';
    response_header("Report - Invalid bug type");
    display_bug_error($errors);
} else {
    if (!isset($_POST['in'])) {
        $_POST['in'] = array(
                 'package_name' => '',
                 'bug_type' => '',
                 'email' => '',
                 'handle' => '',
                 'sdesc' => '',
                 'ldesc' => '',
                 'repcode' => '',
                 'expres' => '',
                 'actres' => '',
                 'package_version' => '',
                 'php_version' => '',
                 'php_os' => '',
                 'passwd' => '',
            
        );
        response_header('Report - New');
        show_bugs_menu(clean($_REQUEST['package']));

        ?>

<p>
 Before you report a bug, make sure to search for similar bugs using the
 &quot;Bug List&quot; link. Also, read the instructions for
 <a target="top" href="http://bugs.php.net/how-to-report.php">how to report
 a bug that someone will want to help fix</a>.
</p>

<p>
 If you aren't sure that what you're about to report is a bug, you should
 ask for help using one of the means for support
 <a href="/support/">listed here</a>.
</p>

<p>
 <strong>Failure to follow these instructions may result in your bug
 simply being marked as &quot;bogus.&quot;</strong>
</p>

<p>
 <strong>If you feel this bug concerns a security issue, eg a buffer
 overflow, weak encryption, etc, then email
 <?php echo make_mailto_link('pear-group@php.net?subject=%5BSECURITY%5D+possible+new+bug%21', 'pear-group'); ?>
 who will assess the situation.</strong>
</p>

        <?php

    }

    display_bug_error($errors);

    ?>

<?php
$self = htmlspecialchars($_SERVER['PHP_SELF']);
$action = $self . '?package=' . clean($_REQUEST['package']);
?>
<form method="post"
 action="<?php echo $action ?>" name="bugreport" id="bugreport" enctype="multipart/form-data">
<table class="form-holder" cellspacing="1">
 <tr>
  <th class="form-label_left">
<?php if (isset($auth_user)): ?>
   Your handle:
  </th>
  <td class="form-input">
   <input type="hidden" name="in[did_luser_search]"
    value="<?php echo isset($_POST['in']['did_luser_search']) ? 1 : 0; ?>" />
   <?php echo $auth_user->handle; ?>
  </td>
<?php
else: // if (isset($auth_user))
?>
   Y<span class="accesskey">o</span>ur email address:<br />
   <strong>MUST BE VALID</strong>
  </th>
  <td class="form-input">
   <input type="hidden" name="in[did_luser_search]"
    value="<?php echo isset($_POST['in']['did_luser_search']) ? 1 : 0; ?>" />
   <input type="text" size="20" maxlength="40" name="in[email]"
    value="<?php echo clean($_POST['in']['email']); ?>" accesskey="o" />
  </td>
<?php endif; // if (isset($auth_user)) ?>
 </tr>
 <tr>
  <th class="form-label_left">
   PHP version:
  </th>
  <td class="form-input">
   <select name="in[php_version]">
    <?php show_version_options($_POST['in']['php_version']); ?>
   </select>
  </td>
 </tr>
 <?php if (!in_array(clean($_REQUEST['package']), $pseudo_pkgs, true)): ?>
 <tr>
  <th class="form-label_left">
   Package version:
  </th>
  <td class="form-input">
   <?php echo show_package_version_options(clean($_REQUEST['package']),
        clean($_POST['in']['package_version'])); ?>
  </td>
 </tr>
 <?php endif; ?>
 <tr>
  <th class="form-label_left">
   Package affected:
  </th>
  <td class="form-input">

    <?php

    if (!empty($_REQUEST['package'])) {
        echo '<input type="hidden" name="in[package_name]" value="';
        echo clean($_REQUEST['package']) . '" />' . clean($_REQUEST['package']);
        if ($_REQUEST['package'] == 'Bug System') {
            echo '<p><strong>WARNING: You are saying the <em>package';
            echo ' affected</em> is the &quot;Bug System.&quot; This';
            echo ' category is <em>only</em> for telling us about problems';
            echo ' that the '.$siteBig.' website\'s bug user interface is having. If';
            echo ' your bug is about a '.$siteBig.' package or other aspect of the';
            echo ' website, please hit the back button and actually read that';
            echo ' page so you can properly categorize your bug.</strong></p>';
            echo '<input type="hidden" name="in[package_version]" value="" />';
        }
    } else {
        echo '<select name="in[package_name]">' . "\n";
        show_types(null, 0, clean($_REQUEST['package']));
        echo '</select>';
    }

    ?>

  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Bug Type:
  </th>
  <td class="form-input">
   <select name="in[bug_type]">
    <?php show_type_options($_POST['in']['bug_type']); ?>
   </select>
  </td>
 </tr>
<?php
if (auth_check('pear.dev')) {
    $content = '';
    Bug_DataObject::init();
    $db = Bug_DataObject::bugDB('bugdb_roadmap');
    $db->package = clean($_REQUEST['package']);
    $db->orderBy('releasedate ASC');
    $myroadmaps = array();
    if (isset($_POST['in']) && isset($_POST['in']['roadmap']) &&
          is_array($_POST['in']['roadmap'])) {
        $myroadmaps = array_flip($_POST['in']['roadmap']);
    }
    if ($db->find(false)) {
        while ($db->fetch()) {
            $released = $dbh->getOne('SELECT releases.id
                FROM packages, releases, bugdb_roadmap b
                WHERE
                b.id=? AND
                packages.name=b.package AND releases.package=packages.id AND
                releases.version=b.roadmap_version',
                array($db->id));
            if ($released) {
                $content .= '<span class="headerbottom">';
            }

            if (!$released || ($released && isset($_GET['showold']))) {
                $content .= '<input type="checkbox" name="in[roadmap][]" value="' . $db->id . '"';
                if (isset($myroadmaps[$db->id])) {
                    $content .= ' checked="checked" ';
                }
                $content .= '/>';
                $content .= $db->roadmap_version . '<br />';
            }

            if ($released) {
                $content .= '</span>';
            }
        }
    } else {
        $content .= '(No roadmap defined)';
    }

?>
 <tr>
  <th class="form-label_left">
   Milestone:
  </th>
  <td class="form-input">
   <?php
    if (isset($_GET['showold'])) {
        echo '<a href="report.php?package=' . clean($_REQUEST['package']) . '">Hide released roadmaps</a>';
    } else {
        echo '<a href="report.php?package=' . clean($_REQUEST['package']) . '&amp;showold=1">Show released roadmaps</a>';
    }
    echo '<br />' . $content;
   ?>
  </td>
 </tr>
<?php
}
?>
 <tr>
  <th class="form-label_left">
   Operating system:
  </th>
  <td class="form-input">
   <input type="text" size="20" maxlength="32" name="in[php_os]"
    value="<?php echo clean($_POST['in']['php_os']); ?>" />
  </td>
 </tr>
 <?php if (!isset($auth_user)): ?>
 <tr>
  <th>Solve the problem : <?php print $numeralCaptcha->getOperation(); ?> = ?</th>
  <td class="form-input"><input type="text" name="captcha" /></td>
 </tr>
 <?php $_SESSION['answer'] = $numeralCaptcha->getAnswer(); ?>
 <?php endif; // if (!isset($auth_user)): ?>
 <tr>
  <th class="form-label_left">
   Summary:
  </th>
  <td class="form-input">
   <input type="text" size="40" maxlength="79" name="in[sdesc]"
    value="<?php echo clean($_POST['in']['sdesc']); ?>" />
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Note:
  </th>
  <td class="form-input">
   Please supply any information that may be helpful in fixing the bug:
   <ul>
    <li>The version number of the <?php echo $siteBig; ?> package or files you are using.</li>
    <li>A short script that reproduces the problem.</li>
    <li>The list of modules you compiled PHP with (your configure line).</li>
    <li>Any other information unique or specific to your setup.</li>
    <li>
     Any changes made in your php.ini compared to php.ini-dist
     (<strong>not</strong> your whole php.ini!)
    </li>
    <li>
     A <a href="http://bugs.php.net/bugs-generating-backtrace.php">gdb
     backtrace</a>.
    </li>
   </ul>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Description:
   <p class="cell_note">
    Put code samples in the
    &quot;Test script&quot; section <strong>below</strong>
    and upload patches <strong>below</strong>.
   </p>
  </th>
  <td class="form-input">
   <textarea cols="60" rows="15" name="in[ldesc]"
    wrap="physical"><?php echo clean($_POST['in']['ldesc']); ?></textarea>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Test script:
   <p class="cell_note">
    A short test script you wrote that demonstrates the bug.
    Please <strong>do not</strong> post more than 20 lines of code.
    If the code is longer than 20 lines, provide a URL to the source
    code that will reproduce the bug.
   </p>
  </th>
  <td class="form-input">
   <textarea cols="60" rows="15" name="in[repcode]"
    wrap="no"><?php echo clean($_POST['in']['repcode']); ?></textarea>
  </td>
 </tr>
 <?php
 $patchname = isset($_POST['in']['patchname']) ? $_POST['in']['patchname'] : '';
 $patchfile = isset($_FILES['patchfile']['name']) ? $_FILES['patchfile']['name'] : '';
 include dirname(dirname(dirname(__FILE__))) . '/templates/bugs/patchform.php'; ?>
 <tr>
  <th class="form-label_left">
   Expected result:
   <p class="cell_note">
    What do you expect to happen or see when you run the test script above?
   </p>
  </th>
  <td class="form-input">
   <textarea cols="60" rows="15" name="in[expres]"
    wrap="physical"><?php echo clean($_POST['in']['expres']); ?></textarea>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Actual result:
   <p class="cell_note">
    This could be a
    <a href="http://bugs.php.net/bugs-generating-backtrace.php">backtrace</a>
    for example.
    Try to keep it as short as possible without leaving anything relevant out.
   </p>
  </th>
  <td class="form-input">
   <textarea cols="60" rows="15" name="in[actres]"
    wrap="physical"><?php echo clean($_POST['in']['actres']); ?></textarea>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Submit:
  </th>
  <td class="form-input">
   <input type="submit" value="Send bug report" />
  </td>
 </tr>
</table>
</form>

    <?php
}

response_footer();

?>
