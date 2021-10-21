<?php

use App\Repository\PackageRepository;
use App\Repository\ReasonRepository;
use App\Utils\Cache;
use App\Utils\Captcha;
use App\Utils\PatchTracker;
use App\Utils\Versions\Client;
use App\Utils\Versions\Generator;

// Obtain common includes
require_once '../include/prepend.php';

// Start session
session_start();

// Init variables
$errors = [];
$ok_to_submit_report = false;

$packageRepository = $container->get(PackageRepository::class);
$pseudo_pkgs = $packageRepository->findEnabled($_GET['project'] ?? '');

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

$versionsClient = new Client();
$cacheDir = (defined('DEVBOX') && true === DEVBOX) ? __DIR__.'/../var/cache' : '/tmp';
$cache = new Cache($cacheDir);
$versionsGenerator = new Generator($versionsClient, $cache);
$versions = $versionsGenerator->getVersions();

// captcha is not necessary if the user is logged in
if (!$logged_in) {
    $captcha = $container->get(Captcha::class);
}

$packageAffectedScript = <<<SCRIPT
    <script src="$site_method://$site_url$basedir/js/package-affected.js"></script>
SCRIPT;

// Handle input
if (isset($_POST['in'])) {

    $errors = incoming_details_are_valid($_POST['in'], 1, $logged_in);

    // Check if session answer is set, then compare it with the post captcha value.
    // If it's not the same, then it's an incorrect password.
    if (!$logged_in) {
        if (!isset($_SESSION['answer'])) {
            $errors[] = 'Please enable cookies so the Captcha system can work';
        } elseif ($_POST['captcha'] != $_SESSION['answer']) {
            $errors[] = 'Incorrect Captcha';
        }
        if (is_spam($_POST['in']['ldesc']) ||
            is_spam($_POST['in']['expres']) ||
            is_spam($_POST['in']['repcode'])) {
            $errors[] = 'Spam detected';
        }
    }

    // Set auto-generated password when not supplied or logged in
    if ($logged_in || $_POST['in']['passwd'] == '') {
        $_POST['in']['passwd'] = uniqid();
    }

    // try to verify the user
    $_POST['in']['email']  = $auth_user->email;

    $package_name = $_POST['in']['package_name'];

    if (!$errors) {
        // When user submits a report, do a search and display the results before allowing them to continue.
        if (!isset($_POST['preview']) && empty($_POST['in']['did_luser_search'])) {

            $_POST['in']['did_luser_search'] = 1;

            $where_clause = "WHERE package_name != 'Feature/Change Request'";

            if (!($user_flags & BUGS_SECURITY_DEV)) {
                $where_clause .= " AND private = 'N' ";
            }

            // search for a match using keywords from the subject
            list($sql_search, $ignored) = format_search_string($_POST['in']['sdesc']);

            $where_clause .= $sql_search;

            $query = "SELECT * from bugdb $where_clause LIMIT 5";

            $possible_duplicates = $dbh->prepare($query)->execute()->fetchAll();

            if (!$possible_duplicates) {
                $ok_to_submit_report = true;
            } else {
                response_header("Report - Confirm", $packageAffectedScript);
                if (count($_FILES)) {
                    echo '<h1>WARNING: YOU MUST RE-UPLOAD YOUR PATCH, OR IT WILL BE IGNORED</h1>';
                }
?>
                <p>
                    Are you sure that you searched before you submitted your bug report? We
                    found the following bugs that seem to be similar to yours; please check
                    them before submitting the report as they might contain the solution you
                    are looking for.
                </p>

                <p>
                    If you're sure that your report is a genuine bug that has not been reported
                    before, you can scroll down and click the "Send Bug Report" button again to
                    really enter the details into our database.
                </p>

                <div class="warnings">
                    <table class="lusersearch">
                        <tr>
                            <th>Description</th>
                            <th>Possible Solution</th>
                        </tr>
<?php

                foreach ($possible_duplicates as $row) {
                    $resolution = $dbh->prepare("
                        SELECT comment
                        FROM bugdb_comments
                        WHERE bug = ?
                        ORDER BY id DESC
                        LIMIT 1
                    ")->execute([$row['id']])->fetch(\PDO::FETCH_NUM)[0];

                    $summary = $row['ldesc'];
                    if (strlen($summary) > 256) {
                        $summary = substr(trim($summary), 0, 256) . ' ...';
                    }

                    $bug_url = "bug.php?id={$row['id']}";

                    $sdesc        = htmlspecialchars($row['sdesc']);
                    $summary    = htmlspecialchars($summary);
                    $resolution    = htmlspecialchars($resolution);

                    echo <<< OUTPUT
                        <tr>
                            <td colspan='2'><strong>{$row['package_name']}</strong> : <a href='{$bug_url}'>Bug #{$row['id']}: {$sdesc}</a></td>
                        </tr>
                        <tr>
                            <td><pre class='note'>{$summary}</pre></td>
                            <td><pre class='note'>{$resolution}</pre></td>
                        </tr>
OUTPUT;
                }

                echo "
                    </table>
                </div>
                ";
            }
        } else {
            // We displayed the luser search and they said it really was not already submitted, so let's allow them to submit.
            $ok_to_submit_report = true;
        }

        if (isset($_POST['edit_after_preview'])) {
            $ok_to_submit_report = false;
            response_header("Report - New", $packageAffectedScript);
        }

        if ($ok_to_submit_report) {
            $_POST['in']['reporter_name'] = $auth_user->name;
            $_POST['in']['handle'] = $auth_user->handle;

            // Put all text areas together.
            $fdesc = "Description:\n------------\n" . $_POST['in']['ldesc'] . "\n\n";
            if (!empty($_POST['in']['repcode'])) {
                $fdesc .= "Test script:\n---------------\n";
                $fdesc .= $_POST['in']['repcode'] . "\n\n";
            }
            if (!empty($_POST['in']['expres']) || $_POST['in']['expres'] === '0') {
                $fdesc .= "Expected result:\n----------------\n";
                $fdesc .= $_POST['in']['expres'] . "\n\n";
            }
            if (!empty($_POST['in']['actres']) || $_POST['in']['actres'] === '0') {
                $fdesc .= "Actual result:\n--------------\n";
                $fdesc .= $_POST['in']['actres'] . "\n";
            }

            // Bug type 'Security' marks automatically the report as private
            $_POST['in']['private'] = ($_POST['in']['bug_type'] == 'Security') ? 'Y' : 'N';
            $_POST['in']['block_user_comment'] = 'N';

            if (isset($_POST['preview'])) {
                $_POST['in']['status'] = 'Open';
                $_SESSION['bug_preview'] = $_POST['in'];
                $_SESSION['bug_preview']['ldesc_orig'] = $_POST['in']['ldesc'];
                $_SESSION['bug_preview']['ldesc'] = $fdesc;
                $_SESSION['captcha'] = $_POST['captcha'];
                redirect('bug.php?id=preview');
            }

            $res = $dbh->prepare('
                INSERT INTO bugdb (
                    package_name,
                    bug_type,
                    email,
                    sdesc,
                    ldesc,
                    php_version,
                    php_os,
                    passwd,
                    reporter_name,
                    status,
                    ts1,
                    private,
                    visitor_ip
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "Open", NOW(), ?, INET6_ATON(?))
            ')->execute([
                    $package_name,
                    $_POST['in']['bug_type'],
                    $_POST['in']['email'],
                    $_POST['in']['sdesc'],
                    $fdesc,
                    $_POST['in']['php_version'],
                    $_POST['in']['php_os'],
                    bugs_get_hash($_POST['in']['passwd']),
                    $_POST['in']['reporter_name'],
                    $_POST['in']['private'],
                    $_SERVER['REMOTE_ADDR']
                ]
            );

            $cid = $dbh->lastInsertId();

            $redirectToPatchAdd = false;
            if (!empty($_POST['in']['patchname']) && $_POST['in']['patchname']) {
                $tracker = $container->get(PatchTracker::class);

                try {
                    $developer = !empty($_POST['in']['handle']) ? $_POST['in']['handle'] : $_POST['in']['email'];
                    $patchrevision = $tracker->attach($cid, 'patchfile', $_POST['in']['patchname'], $developer, []);
                } catch (\Exception $e) {
                    $redirectToPatchAdd = true;
                }
            }

            if (empty($_POST['in']['handle'])) {
                $mailfrom = spam_protect($_POST['in']['email'], 'text');
            } else {
                $mailfrom = $_POST['in']['handle'];
            }

            $report = <<< REPORT
From:             {$mailfrom}
Operating system: {$_POST['in']['php_os']}
PHP version:      {$_POST['in']['php_version']}
Package:          {$package_name}
Bug Type:         {$_POST['in']['bug_type']}
Bug description:
REPORT;

            $ascii_report = "{$report}{$_POST['in']['sdesc']}\n\n" . wordwrap($fdesc, 72);
            $ascii_report.= "\n-- \nEdit bug report at ";
            $ascii_report.= "{$site_method}://{$site_url}{$basedir}/bug.php?id=$cid&edit=";

            list($mailto, $mailfrom, $bcc, $params) = get_package_mail($package_name, false, $_POST['in']['bug_type']);

            $protected_email = '"' . spam_protect($_POST['in']['email'], 'text') . '"' .  "<{$mailfrom}>";

            $extra_headers = "From: {$protected_email}\n";
            $extra_headers.= "X-PHP-BugTracker: {$siteBig}bug\n";
            $extra_headers.= "X-PHP-Bug: {$cid}\n";
            $extra_headers.= "X-PHP-Type: {$_POST['in']['bug_type']}\n";
            $extra_headers.= "X-PHP-Version: {$_POST['in']['php_version']}\n";
            $extra_headers.= "X-PHP-Category: {$package_name}\n";
            $extra_headers.= "X-PHP-OS: {$_POST['in']['php_os']}\n";
            $extra_headers.= "X-PHP-Status: Open\n";
            $extra_headers.= "Message-ID: <bug-{$cid}@{$site_url}>";

            if (isset($bug_types[$_POST['in']['bug_type']])) {
                $type = $bug_types[$_POST['in']['bug_type']];
            } else {
                $type = 'unknown';
            }

            // provide shortcut URLS for "quick bug fixes"
            $reasonRepository = $container->get(ReasonRepository::class);
            list($RESOLVE_REASONS, $FIX_VARIATIONS) = $reasonRepository->findByProject($_GET['project'] ?? '');

            $dev_extra = '';
            $maxkeysize = 0;
            foreach ($RESOLVE_REASONS as $v) {
                if (!$v['webonly']) {
                    $actkeysize = strlen($v['title']) + 1;
                    $maxkeysize = (($maxkeysize < $actkeysize) ? $actkeysize : $maxkeysize);
                }
            }
            foreach ($RESOLVE_REASONS as $k => $v) {
                if (!$v['webonly']) {
                    $dev_extra .= str_pad("{$v['title']}:", $maxkeysize) . " {$site_method}://{$site_url}/fix.php?id={$cid}&r={$k}\n";
                }
            }

            // mail to reporter
            bugs_mail(
                $_POST['in']['email'],
                "$type #$cid: {$_POST['in']['sdesc']}",
                "{$ascii_report}2\n",
                "From: $siteBig Bug Database <$mailfrom>\n" .
                "X-PHP-Bug: $cid\n" .
                "X-PHP-Site: {$siteBig}\n" .
                "Message-ID: <bug-$cid@{$site_url}>"
            );

            // mail to package mailing list
            bugs_mail(
                $mailto,
                "[$siteBig-BUG] $type #$cid [NEW]: {$_POST['in']['sdesc']}",
                $ascii_report . "1\n-- \n{$dev_extra}",
                $extra_headers,
                $params
            );

            if ($redirectToPatchAdd) {
                $patchname = urlencode($_POST['in']['patchname']);
                $patchemail= urlencode($_POST['in']['email']);
                redirect("patch-add.php?bug_id={$cid}&patchname={$patchname}&email={$patchemail}");
            }
            redirect("bug.php?id={$cid}&thanks=4");
        }
    } else {
        // had errors...
        response_header('Report - Problems', $packageAffectedScript);
    }
} // end of if input

$package = !empty($_REQUEST['package']) ? $_REQUEST['package'] : (!empty($package_name) ? $package_name : (isset($_POST['in']) && $_POST['in'] && isset($_POST['in']['package_name']) ? $_POST['in']['package_name'] : ''));

if (!is_string($package)) {
    response_header('Report - Problems', $packageAffectedScript);
    $errors[] = 'Invalid package name passed. Please fix it and try again.';
    display_bug_error($errors);
    response_footer();
    exit;
}

if (!isset($_POST['in'])) {

    $_POST['in'] = [
             'package_name' => isset($_GET['package_name']) ? clean($_GET['package_name']) : '',
             'bug_type' => isset($_GET['bug_type']) ? clean($_GET['bug_type']) : '',
             'email' => '',
             'sdesc' => '',
             'ldesc' => isset($_GET['manpage']) ? clean("\n---\nFrom manual page: https://php.net/" . ltrim($_GET['manpage'], '/') . "\n---\n") : '',
             'repcode' => '',
             'expres' => '',
             'actres' => '',
             'php_version' => '',
             'php_os' => '',
             'passwd' => '',
    ];


    response_header('Report - New', $packageAffectedScript);
?>

    <p>
        Before you report a bug, make sure to search for similar bugs using the &quot;Bug List&quot; link.
        Also, read the instructions for <a target="top" href="how-to-report.php">how to report a bug that someone will want to help fix</a>.
    </p>

    <p>
        If you aren't sure that what you're about to report is a bug, you should ask for help using one of the means for support
        <a href="https://php.net/support.php">listed here</a>.
    </p>

    <p>
        <strong>Failure to follow these instructions may result in your bug simply being marked as &quot;not a bug.&quot;</strong>
    </p>

    <p style="background-color: #ffa;">
        <strong>Documentation issues should now be reported on the <a href="https://github.com/php/doc-en/issues">php/doc-en</a> repository.</strong>
    </p>

    <p>Report <img src="images/pear_item.gif"><b>PEAR</b> related bugs <a href="https://pear.php.net/bugs/">here</a>.</p>

    <p>
        <strong>If you feel this bug concerns a security issue, e.g. a buffer overflow, weak encryption, etc, then email

        <?php echo make_mailto_link("{$site_data['security_email']}?subject=%5BSECURITY%5D+possible+new+bug%21", $site_data['security_email']); ?>
        who will assess the situation or use <strong>Security</strong> as bug type in the form below.</strong>
    </p>

<?php

}

display_bug_error($errors);

?>
    <form method="post" action="report.php?package=<?php echo htmlspecialchars($package); ?>" name="bugreport" id="bugreport" enctype="multipart/form-data">
        <input type="hidden" name="in[did_luser_search]" value="<?php echo isset($_POST['in']['did_luser_search']) ? $_POST['in']['did_luser_search'] : 0; ?>">
        <table class="form-holder" cellspacing="1">
<?php if ($logged_in) { ?>
            <tr>
                <th class="form-label_left">Your handle:</th>
                <td class="form-input">
                    <?php echo $auth_user->handle; ?>
                    <input type="hidden" name="in[email]" value="<?php echo $auth_user->email; ?>">
                </td>
            </tr>
<?php } else { ?>
            <tr>
                <th class="form-label_left">Y<span class="accesskey">o</span>ur email address:<br><strong>MUST BE VALID</strong></th>
                    <td class="form-input">
                        <input type="text" size="20" maxlength="40" name="in[email]" value="<?php echo htmlspecialchars($_POST['in']['email'], ENT_COMPAT, 'UTF-8'); ?>" accesskey="o">
                    </td>
                </th>
            </tr>

            <tr>
                <th class="form-label_left"><span class="accesskey">P</span>assword:</th>
                <td class="form-input">
                    <input type="password" size="20" maxlength="20" name="in[passwd]" value="<?php echo htmlspecialchars($_POST['in']['passwd'], ENT_COMPAT, 'UTF-8');?>" accesskey="p"><br>
                    You <strong>must</strong> enter any password here, which will be stored for this bug report.<br>
                    This password allows you to come back and modify your submitted bug report at a later date.
                    [<a href="bug-pwd-finder.php">Lost a bug password?</a>]
                </td>
            </tr>
<?php } ?>

            <tr>
                <th class="form-label_left">PHP version:</th>
                <td class="form-input">
                    <select name="in[php_version]">
                        <?php show_version_options($_POST['in']['php_version']); ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th class="form-label_left">Package affected:</th>
                <td class="form-input">
                    <select name="in[package_name]">
                        <?php show_package_options($_POST['in']['package_name'], 0, htmlspecialchars($package)); ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th class="form-label_left">Bug Type:</th>
                <td class="form-input">
                    <select name="in[bug_type]">
                        <?php show_type_options($_POST['in']['bug_type'], /* deprecated */ false); ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th class="form-label_left">Operating system:</th>
                <td class="form-input">
                    <input type="text" size="20" maxlength="32" name="in[php_os]" value="<?php echo htmlspecialchars($_POST['in']['php_os'], ENT_COMPAT, 'UTF-8'); ?>">
                </td>
            </tr>

            <tr>
                <th class="form-label_left">Summary:</th>
                <td class="form-input">
                    <input type="text" size="40" maxlength="79" name="in[sdesc]" value="<?php echo htmlspecialchars($_POST['in']['sdesc'], ENT_COMPAT, 'UTF-8'); ?>">
                </td>
            </tr>

            <tr>
                <th class="form-label_left">Note:</th>
                <td class="form-input">
                    Please supply any information that may be helpful in fixing the bug:
                    <ul>
                        <li>The version number of the <?php echo $siteBig; ?> package or files you are using.</li>
                        <li>A short script that reproduces the problem.</li>
                        <li>The list of modules you compiled PHP with (your configure line).</li>
                        <li>Any other information unique or specific to your setup.</li>
                        <li>Any changes made in your php.ini compared to php.ini-dist or php.ini-recommended (<strong>not</strong> your whole php.ini!)</li>
                        <li>A <a href="bugs-generating-backtrace.php">gdb backtrace</a>.</li>
                    </ul>
                </td>
            </tr>

            <tr>
                <th class="form-label_left">
                    Description:
                    <p class="cell_note">
                        Put short code samples in the &quot;Test script&quot; section <strong>below</strong>
                        and upload patches <strong>below</strong>.
                    </p>
                </th>
                <td class="form-input">
                    <textarea cols="80" rows="15" name="in[ldesc]" wrap="soft"><?php echo htmlspecialchars($_POST['in']['ldesc'], ENT_COMPAT, 'UTF-8'); ?></textarea>
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
                    <textarea cols="80" rows="15" name="in[repcode]" wrap="no"><?php echo htmlspecialchars($_POST['in']['repcode'], ENT_COMPAT, 'UTF-8'); ?></textarea>
                </td>
            </tr>
<?php
    $patchname = isset($_POST['in']['patchname']) ? $_POST['in']['patchname'] : '';
    $patchfile = isset($_FILES['patchfile']['name']) ? $_FILES['patchfile']['name'] : '';
    include "{$ROOT_DIR}/templates/patchform.php";
?>

            <tr>
                <th class="form-label_left">
                    Expected result:
                    <p class="cell_note">
                        Skip if irrelevant.
                        What do you expect to happen or see when you run the test script above?
                    </p>
                </th>
                <td class="form-input">
                    <textarea cols="80" rows="15" name="in[expres]" wrap="soft"><?php echo htmlspecialchars($_POST['in']['expres'], ENT_COMPAT, 'UTF-8'); ?></textarea>
                </td>
            </tr>

            <tr>
                <th class="form-label_left">
                    Actual result:
                    <p class="cell_note">
                        Skip if irrelevant.
                        This could be a <a href="bugs-generating-backtrace.php">backtrace</a> for example.
                        Try to keep it as short as possible without leaving anything relevant out.
                    </p>
                </th>
                <td class="form-input">
                    <textarea cols="80" rows="15" name="in[actres]" wrap="soft"><?php echo htmlspecialchars($_POST['in']['actres'], ENT_COMPAT, 'UTF-8'); ?></textarea>
                </td>
            </tr>

<?php if (!$logged_in) {
    $_SESSION['answer'] = $captcha->getAnswer();

    if (!empty($_POST['captcha']) && empty($ok_to_submit_report)) {
        $captcha_label = '<strong>Solve this <em>new</em> problem:</strong>';
    } else {
        $captcha_label = 'Solve the problem:';
    }
?>
            <tr>
                <th><?php echo $captcha_label; ?><br><?php echo htmlspecialchars($captcha->getQuestion()); ?></th>
                <td class="form-input"><input type="text" name="captcha" autocomplete="off"></td>
            </tr>
<?php } ?>

            <tr>
                <th class="form-label_left">Submit:</th>
                <td class="form-input">
                    <input type="submit" value="Send bug report">
                    <input type="submit" value="Preview" name="preview">
                </td>
            </tr>
        </table>
    </form>
<?php

response_footer();
