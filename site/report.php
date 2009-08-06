<?php 

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
 * Start session 
 */
session_start();

/**
 * Obtain common includes
 */
require_once '../include/prepend.inc';

// Init variables
$errors              = array();
$ok_to_submit_report = false;
$pseudo_pkgs = get_pseudo_packages($site, false); // false == no read-only packages included

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $is_trusted_developer);

// captcha is not necessary if the user is logged in
if ($logged_in) {
	unset($_SESSION['answer']);
} else {
	/**
	 * Numeral Captcha Class
	 */
	require_once 'Text/CAPTCHA/Numeral.php';

	/**
	 * Instantiate the numeral captcha object.
	 */
	$numeralCaptcha = new Text_CAPTCHA_Numeral();
}


if (isset($_POST['in'])) {
    $errors = incoming_details_are_valid($_POST['in'], 1);

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
    $_POST['in']['email']  = $auth_user->email;
    $_POST['in']['handle'] = $auth_user->handle;

    $package_name = $_POST['in']['package_name'];
    $package_version = !empty($_POST['in']['package_version']) ? $_POST['in']['package_version'] : '';

    if (!$errors) {

        /*
         * When user submits a report, do a search and display
         * the results before allowing them to continue.
         */
        if (!isset($_POST['in']['did_luser_search']) || !$_POST['in']['did_luser_search']) {

            $_POST['in']['did_luser_search'] = 1;

            /*
             * If they are filing a feature request,
             * only look for similar features
             */
            $where_clause = 'WHERE bugdb.package_name=p.name ';
            if ($package_name == 'Feature/Change Request') {
                $where_clause .= "AND package_name = '$package_name'";
            } else {
                $where_clause .= "AND package_name != 'Feature/Change Request'";
            }

            // search for a match using keywords from the subject
            list($sql_search, $ignored) = format_search_string($_POST['in']['sdesc']);

            $where_clause .= $sql_search;
            $where_clause .= " AND p.package_type= '{$site}'" . (($site == 'php') ? '' : ' AND bugdb.registered = 1');

            $query = "SELECT bugdb.* from bugdb, packages p $where_clause LIMIT 5";

            $res = $dbh->prepare($query)->execute();

            if ($res->numRows() == 0) {
                $ok_to_submit_report = true;
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

                foreach ($res->fetchAll(MDB2_FETCHMODE_ASSOC) as $row) {

                    $resolution = $dbh->prepare("
						SELECT comment 
						FROM bugdb_comments
						WHERE bug = ?
						ORDER BY id DESC
						LIMIT 1
					")->execute(array($row['id']))->fetchOne();

                    if ($resolution) {
                        $resolution = htmlspecialchars($resolution);
                    }

                    $summary = $row['ldesc'];
                    if (strlen($summary) > 256) {
                        $summary = htmlspecialchars(substr(trim($summary), 0, 256)) . ' ...';
                    } else {
                        $summary = htmlspecialchars($summary);
                    }

                    $bug_url = "bug.php?id={$row['id']}&amp;edit=2";

                    echo " <tr>\n";
                    echo "  <td colspan='2'><strong>{$row['package_name']}</strong> : <a href='{$bug_url}'>Bug #";
                    echo $row['id'] . ': ' . htmlspecialchars($row['sdesc']);
                    echo "</a></td>\n";
                    echo " </tr>\n";
                    echo " <tr>\n";
                    echo "  <td>{$summary}</td>\n";
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
                if ($site != 'php' && !$logged_in) {
                    $registereduser = 0;
                    // user doesn't exist yet
                    require_once "{$ROOT_DIR}/include/classes/bug_accountrequest.php";
                    $buggie = new Bug_Accountrequest;
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
                if (in_array($package_name, array('Web Site', 'PEPr', 'Bug System'), true)) {
                    $package_name = 'pearweb';
                }

				$res = $dbh->prepare('INSERT INTO bugdb (
                          registered,
                          package_name,
                          package_version,
                          bug_type,
                          email,
                          handle,
                          sdesc,
                          ldesc,
                          php_version,
                          php_os,
                          passwd,
                          reporter_name,
                          status,
                          ts1
                         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "Open", NOW())')->execute(array (
                          $registereduser,
                          $package_name,
                          $package_version,
                          $_POST['in']['bug_type'],
                          $_POST['in']['email'],
                          $_POST['in']['handle'],
                          $_POST['in']['sdesc'],
                          $fdesc,
                          $_POST['in']['php_version'],
                          $_POST['in']['php_os'],
                          $_POST['in']['passwd'],
                          $_POST['in']['reporter_name'],
                        )
				);
				if (PEAR::isError($res)) {
					echo "<pre>";
					var_dump($_POST['in'], $fdesc, $package_version,$package_name,$registereduser);
					die($res->getMessage());
				}
                $cid = $dbh->lastInsertId();

                if ($site != 'php')
                {
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
				}
                $redirectToPatchAdd = false;
                if (!empty($_POST['in']['patchname']) && $_POST['in']['patchname']) {
                   require_once "{$ROOT_DIR}/include/classes/bug_patchtracker.php";
                   $tracker = new Bug_Patchtracker;
                   PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
                   $patchrevision = $tracker->attach($cid, 'patchfile', $_POST['in']['patchname'], $_POST['in']['handle'], array());
                   PEAR::staticPopErrorHandling();
                   if (PEAR::isError($patchrevision)) {
                       $redirectToPatchAdd = true;
                   }
                }

                if (!isset($buggie)) {
                    $report  = '';
                    $report .= 'From:             ' . $_POST['in']['handle'] . "\n";
                    $report .= 'Operating system: ' . $_POST['in']['php_os'] . "\n";
                    $report .= "Package version:  $package_version\n";
                    $report .= 'PHP version:      ' . $_POST['in']['php_version'] . "\n";
                    $report .= "Package:          $package_name\n";
                    $report .= 'Bug Type:         ' . $_POST['in']['bug_type'] . "\n";
                    $report .= 'Bug description:  ';

                    $ascii_report  = "$report{$_POST['in']['sdesc']}\n\n" . wordwrap($fdesc);
                    $ascii_report .= "\n-- \nEdit bug report at ";
                    $ascii_report .= "http://{$site_url}{$basedir}/bug.php?id=$cid&edit=";

                    list($mailto, $mailfrom) = get_package_mail($package_name);

                    $protected_email  = '"' . spam_protect($_POST['in']['email'], 'text') . '"';
                    $protected_email .= '<' . $mailfrom . '>';

                    $extra_headers  = "From: {$protected_email}\n";
                    $extra_headers .= "X-PHP-BugTracker: {$siteBig}bug\n";
                    $extra_headers .= "X-PHP-Bug: {$cid}\n";
                    $extra_headers .= "X-PHP-Type: {$_POST['in']['bug_type']}\n";
                    $extra_headers .= "X-PHP-PackageVersion: {$package_version}\n";
                    $extra_headers .= "X-PHP-Version: {$_POST['in']['php_version']}\n";
                    $extra_headers .= "X-PHP-Category: {$package_name}\n";
                    $extra_headers .= "X-PHP-OS: {$_POST['in']['php_os']}\n";
                    $extra_headers .= "X-PHP-Status: Open\n";
                    $extra_headers .= "Message-ID: <bug-{$cid}@{$site_url}>";

					if (isset($bug_types[$_POST['in']['bug_type']])) {
	                    $type = $bug_types[$_POST['in']['bug_type']];
					} else {
						$type = 'unknown';
					}

                    // provide shortcut URLS for "quick bug fixes"
					/* Fetch RESOLVE_REASONS array */
					if ($site != 'php') {
						require_once "{$ROOT_DIR}/include/resolve-{$site}.inc";
					} else {
						list($RESOLVE_REASONS, $FIX_VARIATIONS) = get_resolve_reasons($site);
					}
                    $dev_extra = '';
                    $maxkeysize = 0;
                    foreach ($RESOLVE_REASONS as $v) {
                    	if (!$v['webonly']) {
                    		$actkeysize = strlen($v['title']) + 1;
                    		$maxkeysize = (($maxkeysize < $actkeysize) ? $actkeysize : $maxkeysize);
                    	}
                    }
                    foreach ($RESOLVE_REASONS as $k => $v) {
                        if (!$v['webonly'])
                            $dev_extra .= str_pad("{$v['title']}:", $maxkeysize) . " http://bugs.php.net/fix.php?id={$cid}&r={$k}\n";
                    }

                    // mail to package developers
                    @mail($mailto, "[$siteBig-BUG] $type #$cid [NEW]: {$_POST['in']['sdesc']}",
                          $ascii_report . "1\n-- \n$dev_extra", $extra_headers,
                          '-f bounce-no-user@php.net');
                    // mail to reporter
                    if (!DEVBOX)
                    	@mail($_POST['in']['email'], "[$siteBig-BUG] $type #$cid: {$_POST['in']['sdesc']}",
                    	      $ascii_report . "2\n",
                    	      "From: $siteBig Bug Database <$mailfrom>\n" .
                    	      "X-PHP-Bug: $cid\n" .
                    	      "Message-ID: <bug-$cid@{$site_url}>",
                    	      '-f bounce-no-user@php.net');
                }
                if ($redirectToPatchAdd) {
                	$patchname = urlencode($_POST['in']['patchname']);
                	$patchemail= urlencode($_POST['in']['email']);
                    localRedirect("patch-add.php?bug_id={$cid}&patchname={$patchname}&email={$patchemail}");
                } elseif (!isset($buggie) && !empty($_POST['in']['patchname'])) {
                    require_once "{$ROOT_DIR}/include/classes/bug_accountrequest.php";
                    $r = new Bug_Accountrequest();
                    $info = $r->sendPatchEmail($cid, $patchrevision, $package_name, $auth_user->handle);
                }
//                var_dump ($cid);
                localRedirect("bug.php?id={$cid}&thanks=4");
            }
        } while (false);
    } else {
        // had errors...
        response_header('Report - Problems');
    }

}  // end of if input

$package = !empty($_REQUEST['package']) ? $_REQUEST['package'] : (!empty($package_name) ? $package_name : '');

if (!is_string($package)) {
    response_header('Report - Problems');
    $errors[] = 'Invalid package name passed. Please fix it and try again.';
    display_bug_error($errors);
    response_footer();
    exit;
}

if ($site != 'php' && !package_exists($package)) {
    $errors[] = 'Package "' . htmlspecialchars($package) . '" does not exist.';
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
        show_bugs_menu(htmlspecialchars($package));
?>

<p>
 Before you report a bug, make sure to search for similar bugs using the
 &quot;Bug List&quot; link. Also, read the instructions for
 <a target="top" href="how-to-report.php">how to report
 a bug that someone will want to help fix</a>.
</p>

<p>
 If you aren't sure that what you're about to report is a bug, you should
 ask for help using one of the means for support
 <a href="<?php echo ($site != 'php') ? "http://{$site_data[$site]['url']}/support.php" : 'http://www.php.net/support.php'; ?>">listed here</a>.
</p>

<p>
 <strong>Failure to follow these instructions may result in your bug
 simply being marked as &quot;bogus.&quot;</strong>
</p>

<?php if ($site != 'php') { ?>
<p>Report <img src="images/php_item.gif"><b>PHP</b> related bugs <a href="http://<?php echo $site_data['php']['url'], $site_data['php']['basedir'], '/'; ?>">here</a></p>
<?php } ?>
<?php if ($site != 'pear') { ?>
<p>Report <img src="images/pear_item.gif"><b>PEAR</b> related bugs <a href="http://<?php echo $site_data['pear']['url'], $site_data['pear']['basedir'], '/'; ?>">here</a></p>
<?php } ?>
<?php if ($site != 'pecl') { ?>
<p>Report <img src="images/pecl_item.gif"><b>PECL</b> related bugs <a href="http://<?php echo $site_data['pecl']['url'], $site_data['pecl']['basedir'], '/'; ?>">here</a></p>
<?php } ?>

<p>
 <strong>If you feel this bug concerns a security issue, eg a buffer
 overflow, weak encryption, etc, then email

<?php 

 $email = $site_data[$site]['security_email']; 
 echo make_mailto_link("{$email}?subject=%5BSECURITY%5D+possible+new+bug%21", $email);

?> who will assess the situation.</strong>

</p>

<?php

    }

    display_bug_error($errors);

?>
<form method="post" action="report.php?package=<?php echo htmlspecialchars($package); ?>" name="bugreport" id="bugreport" enctype="multipart/form-data">
<table class="form-holder" cellspacing="1">
 <tr>
  <th class="form-label_left">

<?php if ($logged_in) { ?>
   Your handle:
  </th>
  <td class="form-input">
   <input type="hidden" name="in[did_luser_search]"
    value="<?php echo isset($_POST['in']['did_luser_search']) ? 1 : 0; ?>" />
   <?php echo $auth_user->handle; ?>
  </td>
<?php } else { ?>
   Y<span class="accesskey">o</span>ur email address:<br />
   <strong>MUST BE VALID</strong>
  </th>
  <td class="form-input">
   <input type="hidden" name="in[did_luser_search]"
    value="<?php echo isset($_POST['in']['did_luser_search']) ? 1 : 0; ?>" />
   <input type="text" size="20" maxlength="40" name="in[email]" value="<?php echo htmlspecialchars($_POST['in']['email']); ?>" accesskey="o" />
  </td>
<?php } ?>

<?php if ($site == 'php') { ?>
 <tr>
  <th class="form-label_left"><span class="accesskey">P</span>assword:</th>
  <td class="form-input">
   <input type="password" size="20" maxlength="20" name="in[passwd]" value="<?php echo htmlspecialchars($_POST['in']['passwd']);?>" accesskey="p" />
   <br />
    You <b>must</b> enter any password here, which will be stored for this bug report.<br />
    This password allows you to come back and modify your submitted bug report
    at a later date. [<a href="bug-pwd-finder.php">Lost a bug password?</a>]
  </td>
 </tr>
<?php } ?>

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
 <?php if (!isset($pseudo_pkgs[htmlspecialchars($package)])) { ?>
 <tr>
  <th class="form-label_left">
   Package version:
  </th>
  <td class="form-input">
   <?php echo show_package_version_options(htmlspecialchars($package), htmlspecialchars($_POST['in']['package_version'])); ?>
  </td>
 </tr>
 <?php } ?>
 <tr>
  <th class="form-label_left">
   Package affected:
  </th>
  <td class="form-input">

    <?php

    if (!empty($package)) {
        echo '<input type="hidden" name="in[package_name]" value="', htmlspecialchars($package) , '" />' , htmlspecialchars($package);
        if ($package == 'Bug System') {
            echo <<< DATA
            	<p>
            	 <strong>
            	  WARNING: You are saying the <em>package affected</em> is the &quot;Bug System.&quot;
            	  This category is <em>only</em> for telling us about problems that the {$siteBig} website's
            	  bug user interface is having. If your bug is about a {$siteBig} package or other aspect of the website,
            	  please hit the back button and actually read that page so you can properly categorize your bug.
            	 </strong>
            	</p>
            	<input type="hidden" name="in[package_version]" value="" />
DATA;
        }
    } else {
        echo '<select name="in[package_name]">' , "\n";
        show_types(null, 0, htmlspecialchars($package));
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
<?php if ($logged_in == 'developer') {
    $content = '';
    Bug_DataObject::init();
    $db = Bug_DataObject::bugDB('bugdb_roadmap');
    $db->package = htmlspecialchars($package);
    $db->orderBy('releasedate ASC');
    $myroadmaps = array();
    if (isset($_POST['in']['roadmap']) && is_array($_POST['in']['roadmap'])) {
        $myroadmaps = array_flip($_POST['in']['roadmap']);
    }
    if ($db->find(false)) {
        while ($db->fetch()) {
            $released = $dbh->prepare('
            	SELECT releases.id
                FROM packages, releases, bugdb_roadmap b
                WHERE b.id = ? AND
                      packages.name = b.package AND
                      releases.package = packages.id AND
                      releases.version = b.roadmap_version
			')->execute(array($db->id))->fetchOne();
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
        echo '<a href="report.php?package=' , htmlspecialchars($package) , '">Hide released roadmaps</a>';
    } else {
        echo '<a href="report.php?package=' , htmlspecialchars($package) , '&amp;showold=1">Show released roadmaps</a>';
    }
    echo '<br />' , $content;
   ?>
  </td>
 </tr>

<?php } ?>

 <tr>
  <th class="form-label_left">
   Operating system:
  </th>
  <td class="form-input">
   <input type="text" size="20" maxlength="32" name="in[php_os]" value="<?php echo htmlspecialchars($_POST['in']['php_os']); ?>" />
  </td>
 </tr>

<?php if (!$logged_in) { 
	$captcha_operation = $numeralCaptcha->getOperation();
	$_SESSION['answer'] = $numeralCaptcha->getAnswer()
?>
 <tr>
  <th>Solve the problem : <?php echo $captcha_operation; ?> = ?</th>
  <td class="form-input"><input type="text" name="captcha" /></td>
 </tr>
<?php } ?>

 <tr>
  <th class="form-label_left">
   Summary:
  </th>
  <td class="form-input">
   <input type="text" size="40" maxlength="79" name="in[sdesc]" value="<?php echo htmlspecialchars($_POST['in']['sdesc']); ?>" />
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
    <li>Any changes made in your php.ini compared to php.ini-dist or php.ini-recommended (<strong>not</strong> your whole php.ini!)
    </li>
    <li>
     A <a href="bugs-generating-backtrace.php">gdb backtrace</a>.
    </li>
   </ul>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Description:
   <p class="cell_note">
    Put short code samples in the &quot;Test script&quot; section <strong>below</strong>
    and upload patches and/or long code samples <strong>below</strong>.
   </p>
  </th>
  <td class="form-input">
   <textarea cols="80" rows="15" name="in[ldesc]" wrap="physical"><?php echo htmlspecialchars($_POST['in']['ldesc']); ?></textarea>
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
   <textarea cols="80" rows="15" name="in[repcode]" wrap="no"><?php echo htmlspecialchars($_POST['in']['repcode']); ?></textarea>
  </td>
 </tr>
 <?php
 $patchname = isset($_POST['in']['patchname']) ? $_POST['in']['patchname'] : '';
 $patchfile = isset($_FILES['patchfile']['name']) ? $_FILES['patchfile']['name'] : '';
 include "{$ROOT_DIR}/templates/patchform.php"; ?>
 <tr>
  <th class="form-label_left">
   Expected result:
   <p class="cell_note">
    What do you expect to happen or see when you run the test script above?
   </p>
  </th>
  <td class="form-input">
   <textarea cols="80" rows="15" name="in[expres]" wrap="physical"><?php echo htmlspecialchars($_POST['in']['expres']); ?></textarea>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Actual result:
   <p class="cell_note">
    This could be a <a href="bugs-generating-backtrace.php">backtrace</a> for example.
    Try to keep it as short as possible without leaving anything relevant out.
   </p>
  </th>
  <td class="form-input">
   <textarea cols="80" rows="15" name="in[actres]" wrap="physical"><?php echo htmlspecialchars($_POST['in']['actres']); ?></textarea>
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

