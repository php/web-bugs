<?php
/* User interface for viewing and editing bug details */

use App\Repository\BugRepository;
use App\Repository\CommentRepository;
use App\Repository\ObsoletePatchRepository;
use App\Repository\PackageRepository;
use App\Repository\PatchRepository;
use App\Utils\Captcha;
use App\Repository\PullRequestRepository;
use App\Repository\ReasonRepository;

// Obtain common includes
require_once '../include/prepend.php';

// Start session
session_start();

$obsoletePatchRepository = $container->get(ObsoletePatchRepository::class);
$patchRepository = $container->get(PatchRepository::class);

define('SPAM_REJECT_MESSAGE', 'Your comment looks like SPAM by its content. Please consider rewording.');
$email = null;

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
$errors = [];

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
                $dbh->prepare('REPLACE INTO bugdb_subscribe SET bug_id = ?, email = ?')->execute([$bug_id, $email]);
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
    $captcha = $container->get(Captcha::class);
}

$trytoforce = isset($_POST['trytoforce']) ? (int) $_POST['trytoforce'] : 0;

// fetch info about the bug into $bug
if (!isset($bug)) {
    $bugRepository = $container->get(BugRepository::class);
    $bug = $bugRepository->findOneById($bug_id);
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
$RESOLVE_REASONS = $FIX_VARIATIONS = $pseudo_pkgs = [];

$project = $bug['project'];

// Only fetch stuff when it's really needed
if ($edit && $edit < 3) {
    $packageRepository = $container->get(PackageRepository::class);
    $pseudo_pkgs = $packageRepository->findEnabled();
}

// Fetch RESOLVE_REASONS array
if ($edit === 1) {
    $reasonRepository = $container->get(ReasonRepository::class);
    list($RESOLVE_REASONS, $FIX_VARIATIONS) = $reasonRepository->findByProject($project);
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
    if (is_spam_user($_POST['in']['commentemail'])) {
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
    if (is_spam_user($from)) {
        $errors[] = "Please do not SPAM our bug system.";
    }

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

    if (is_spam_user($from)) {
        $errors[] = "Please do not SPAM our bug system.";
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
        ")->execute([
            $_POST['in']['sdesc'],
            $_POST['in']['status'],
            $_POST['in']['package_name'],
            $_POST['in']['bug_type'],
            $_POST['in']['php_version'],
            $_POST['in']['php_os'],
            $from,
            $is_private
        ]);

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
    if (is_spam_user($from)) {
        $errors[] = "Please do not SPAM our bug system.";
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

    global $state_types;
    $allowed_state_types = array_filter($state_types, function ($var) {
        return $var !== 0;
    });
    // Require comment for open bugs only
    if (empty($_POST['in']['status']) || !isset($allowed_state_types[$_POST['in']['status']])) {
        $errors[] = "You must provide a status";
    } else {
        if ($_POST['in']['status'] == 'Not a bug' &&
            !in_array($bug['status'], ['Not a bug', 'Closed', 'Duplicate', 'No feedback', 'Wont fix']) &&
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
        ")->execute([
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
        ]);

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

response_header(
    $show_bug_info ? "{$bug_type} #{$bug_id} :: " . htmlspecialchars($bug['sdesc']) : "You must be logged in",
    ($bug_id != 'PREVIEW') ? "
        <link rel='alternate' type='application/rss+xml' title='{$bug['package_name']} Bug #{$bug['id']} - RDF' href='rss/bug.php?id={$bug_id}'>
        <link rel='alternate' type='application/rss+xml' title='{$bug['package_name']} Bug #{$bug['id']} - RSS 2.0' href='rss/bug.php?id={$bug_id}&format=rss2'>
    " : ''
);

// DISPLAY BUG
$thanks = (isset($_GET['thanks'])) ? (int) $_GET['thanks'] : 0;
switch ($thanks)
{
    case 1:
    case 2:
        echo '<div class="success">The bug was updated successfully.</div>';
        break;
    case 3:
        echo '<div class="success">Your comment was added to the bug successfully.</div>';
        break;
    case 4:
        $bug_url = "{$site_method}://{$site_url}{$basedir}/bug.php?id={$bug_id}";
        echo '<div class="success">
            Thank you for your help!
            If the status of the bug report you submitted changes, you will be notified.
            You may return here and check the status or update your report at any time.<br>
            The URL for your bug report is: <a href="'.$bug_url.'">'.$bug_url.'</a>.
            </div>';
        break;
    case 6:
        echo '<div class="success">Thanks for voting! Your vote should be reflected in the statistics below.</div>';
        break;
    case 7:
        echo '<div class="success">Your subscribe request has been processed.</div>';
        break;
    case 8:
        echo '<div class="success">Your unsubscribe request has been processed, please check your email.</div>';
        break;
    case 9:
        echo '<div class="success">You have successfully unsubscribed.</div>';
        break;
    case 10:
        echo '<div class="success">Your vote has been updated.</div>';
    break;

    default:
        break;
}

display_bug_error($errors);

if (!$show_bug_info) {
    echo '<div id="bugheader"></div>';
} else{
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
            <td rowspan="6">

<?php    if ($bug['votes']) { ?>
                <table id="votes">
                    <tr><th class="details">Votes:</th><td><?php echo $bug['votes'] ?></td></tr>
                    <tr><th class="details">Avg. Score:</th><td><?php printf("%.1f &plusmn; %.1f", $bug['average'], $bug['deviation']); ?></td></tr>
                    <tr><th class="details">Reproduced:</th><td><?php printf("%d of %d (%.1f%%)", $bug['reproduced'], $bug['tried'], $bug['tried'] ? ($bug['reproduced'] / $bug['tried']) * 100 : 0); ?></td></tr>
<?php        if ($bug['reproduced']) { ?>
                    <tr><th class="details">Same Version:</th><td><?php printf("%d (%.1f%%)", $bug['samever'], ($bug['samever'] / $bug['reproduced']) * 100); ?></td></tr>
                    <tr><th class="details">Same OS:</th><td><?php printf("%d (%.1f%%)", $bug['sameos'], ($bug['sameos'] / $bug['reproduced']) * 100); ?></td></tr>
<?php        } ?>
                </table>
<?php    } ?>

            </td>
        </tr>

        <tr id="submitter">
            <th class="details">From:</th>
            <td><?php echo ($bug['status'] !== 'Spam') ? spam_protect(htmlspecialchars($bug['email'])) : 'Hidden because of SPAM'; ?></td>
            <th class="details">Assigned:</th>
<?php if (!empty($bug['assign'])) { ?>
            <td><a href="search.php?cmd=display&amp;assign=<?php echo urlencode($bug['assign']), '">', htmlspecialchars($bug['assign']); ?></a> (<a href="https://people.php.net/<?php echo urlencode($bug['assign']); ?>">profile</a>)</td>
<?php } else { ?>
            <td><?php echo htmlspecialchars($bug['assign']); ?></td>
<?php } ?>
        </tr>

        <tr id="categorization">
            <th class="details">Status:</th>
            <td><?php echo htmlspecialchars($bug['status']); ?></td>
            <th class="details">Package:</th>
            <td><a href="search.php?cmd=display&amp;package_name[]=<?php echo urlencode($bug['package_name']), '">', htmlspecialchars($bug['package_name']); ?></a><?php echo $bug['project'] == 'pecl' ? ' (<a href="https://pecl.php.net/package/'. htmlspecialchars($bug['package_name']) . '" target="_blank">PECL</a>)' : ''; ?></td>
        </tr>

        <tr id="situation">
            <th class="details">PHP Version:</th>
            <td><?php echo htmlspecialchars($bug['php_version']); ?></td>
            <th class="details">OS:</th>
            <td><?php echo htmlspecialchars($bug['php_os']); ?></td>
        </tr>

        <tr id="private">
            <th class="details">Private report:</th>
            <td><?php echo $bug['private'] == 'Y' ? 'Yes' : 'No'; ?></td>
            <th class="details">CVE-ID:</th>
            <td><?php if (!empty($bug['cve_id'])) { printf('<a href="https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-%s" target="_blank">%1$s</a>', htmlspecialchars($bug['cve_id'])); } else { ?><em>None</em><?php } ?></td>
        </tr>
    </table>
</div>

<?php
}

if ($bug_id !== 'PREVIEW') {
    echo '<div class="controls">', "\n",
        control(0, 'View'),
        ($bug['private'] == 'N' ? control(3, 'Add Comment') : ''),
        control(1, 'Developer'),
        (!$email || $bug['email'] == $email? control(2, 'Edit') : ''),
        '</div>', "\n";
?>
<div class="clear"></div>

<?php if ($show_bug_info && !$edit && canvote($thanks, $bug['status'])) { ?>
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
<?php    }

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

<?php
    if ($edit == 2) {
?>
        <div class="explain">
        <?php if (!isset($_POST['in'])) { ?>
            Welcome back! If you're the original bug submitter, here's
            where you can edit the bug or add additional notes.<br>
            If this is not your bug, you can
            <a href="bug.php?id=<?php echo $bug_id; ?>&amp;edit=3">add a comment by following this link</a>.<br>
            If this is your bug, but you forgot your password, <a href="bug-pwd-finder.php?id=<?php echo $bug_id; ?>">you can retrieve your password here</a>.<br>
        <?php } ?>

            <table>
                <tr>
                    <td class="details">Passw<span class="accesskey">o</span>rd:</td>
                    <td><input type="password" name="pw" value="<?php echo htmlspecialchars($pw); ?>" size="10" maxlength="20" accesskey="o"></td>
                    <?php if (!$show_bug_info) { ?>
                    <td><input type="submit" value="Submit"></td>
                    <?php } ?>
                </tr>
            </table>
        </div>
<?php
    } elseif ($logged_in == 'developer') {
?>
        <div class="explain">
            Welcome back, <?php echo $user; ?>!
            (Not <?php echo $user; ?>? <a href="logout.php">Log out.</a>)
        </div>
<?php
    } else {
?>
        <div class="explain">
            Welcome! If you don't have a Git account, you can't do anything here.<br>
            You can <a href="bug.php?id=<?php echo $bug_id; ?>&amp;edit=3">add a comment by following this link</a>
            or if you reported this bug, you can <a href="bug.php?id=<?php echo $bug_id; ?>&amp;edit=2">edit this bug over here</a>.
            <div class="details">
                <label for="svnuser">php.net Username:</label>
                <input type="text" id="svnuser" name="user" value="<?php echo htmlspecialchars($user); ?>" size="10" maxlength="20">
                <label for="svnpw">php.net Password:</label>
                <input type="password" id="svnpw" name="pw" value="<?php echo htmlspecialchars($pw); ?>" size="10">
                <!--<label for="save">Remember:</label><input style="vertical-align:middle;" type="checkbox" id="save" name="save" <?php echo !empty($_POST['save']) ? 'checked="checked"' : ''; ?>>-->
                <?php if (!$show_bug_info) { ?>
                <input type="submit" value="Submit">
                <?php } ?>
            </div>
        </div>
<?php
    }

    echo $preview;
?>
    <table>

<?php
      if ($edit == 1 && $show_bug_info) { /* Developer Edit Form */
        if (isset($RESOLVE_REASONS) && $RESOLVE_REASONS) {
?>
        <tr>
            <th class="details"><label for="in" accesskey="c">Qui<span class="accesskey">c</span>k Fix:</label></th>
            <td colspan="3">
                <select name="in[resolve]" id="in">
                    <?php show_reason_types((isset($_POST['in']) && isset($_POST['in']['resolve'])) ? $_POST['in']['resolve'] : -1, 1); ?>
                </select>

<?php      if (isset($_POST['in']) && !empty($_POST['in']['resolve'])) { ?>
                <input type="hidden" name="trytoforce" value="1">
<?php      } ?>

                <small>(<a href="quick-fix-desc.php">description</a>)</small>
            </td>
        </tr>
<?php
        }

        if ($is_security_developer) { ?>
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
<?php } ?>

<?php if ($show_bug_info) { ?>

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
                    <?php show_type_options($bug['bug_type'], /* deprecated */ true); ?>
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
    </table>

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
} // if ($edit == 1 || $edit == 2)
?>

<?php
    if ($edit == 3 && $bug['private'] == 'N') {

    if ($bug['status'] === 'Spam') {
        echo 'This bug has a SPAM status, so no additional comments are needed.';
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

        <div class="explain">
            Anyone can comment on a bug. Have a simpler test case? Does it
            work for you on a different platform? Let us know!<br>
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
    $_SESSION['answer'] = $captcha->getAnswer();
?>
    <table>
        <tr>
            <th class="details">Y<span class="accesskey">o</span>ur email address:<br><strong>MUST BE VALID</strong></th>
            <td class="form-input">
                <input type="text" size="40" maxlength="40" name="in[commentemail]" value="<?php echo isset($_POST['in']['commentemail']) ? htmlspecialchars($_POST['in']['commentemail'], ENT_COMPAT, 'UTF-8') : ''; ?>" accesskey="o">
            </td>
        </tr>
        <tr>
            <th>Solve the problem:<br><?php echo htmlspecialchars($captcha->getQuestion()); ?></th>
            <td class="form-input"><input type="text" name="captcha"></td>
        </tr>
        <tr>
            <th class="details">Subscribe to this entry?</th>
            <td class="form-input">
                <input type="submit" name="subscribe_to_bug" value="Subscribe">
                <input type="submit" name="unsubscribe_to_bug" value="Unsubscribe">
            </td>
        </tr>
    </table>
</div>
<?php } ?>

    <div>
        <input type="hidden" name="id" value="<?php echo $bug_id; ?>">
        <input type="hidden" name="edit" value="<?php echo $edit; ?>">

    <?php
    if ($bug['block_user_comment'] == 'Y' && $logged_in != 'developer') {
        echo 'Further comment on this bug is unnecessary.';
    } elseif ($bug['status'] === 'Spam' && $logged_in != 'developer') {
        echo 'This bug has a SPAM status, so no additional comments are needed.';
    } else {
    ?>
        <textarea cols="80" rows="10" name="ncomment" wrap="soft"><?php echo htmlspecialchars($ncomment); ?></textarea>
    <?php
    }
    ?>

        <br><input type="submit" name="preview" value="Preview">&nbsp;<input type="submit" value="Submit">
    </div>

    </form>

<?php } ?>

<?php

// Display original report
if ($bug['ldesc']) {
    if (!$show_bug_info) {
        echo 'This bug report is marked as private.';
    } else if ($bug['status'] !== 'Spam') {
        output_note(0, $bug['submitted'], $bug['email'], $bug['ldesc'], 'comment', $bug['reporter_name'], false);
    } else {
        echo 'The original report has been hidden, due to the SPAM status.';
    }
}

// Display patches
if ($show_bug_info && $bug_id != 'PREVIEW' && $bug['status'] !== 'Spam') {
    $p = $patchRepository->findAllByBugId($bug_id);
    $revs = [];
    echo "<h2>Patches</h2>\n";

    foreach ($p as $patch) {
        $revs[$patch['patch']][] = [$patch['revision'], $patch['developer']];
    }

    foreach ($revs as $name => $revisions)
    {
        $obsolete = $obsoletePatchRepository->findObsoletingPatches($bug_id, $name, $revisions[0][0]);
        $style = !empty($obsolete) ? ' style="background-color: yellow; text-decoration: line-through;" ' : '';
        $url_name = urlencode($name);
        $clean_name = clean($name);
        $formatted_date = format_date($revisions[0][0]);
        $submitter = spam_protect($revisions[0][1]);

        echo <<< OUTPUT
<a href="patch-display.php?bug_id={$bug_id}&amp;patch={$url_name}&amp;revision=latest" {$style}>{$clean_name}</a>
(last revision {$formatted_date} by {$submitter})
<br>
OUTPUT;
    }
    echo "<p><a href='patch-add.php?bug_id={$bug_id}'>Add a Patch</a></p>";

    $pullRequestRepository = $container->get(PullRequestRepository::class);
    $pulls = $pullRequestRepository->findAllByBugId($bug_id);
    echo "<h2>Pull Requests</h2>\n";

    require "{$ROOT_DIR}/templates/listpulls.php";
    echo "<p><a href='gh-pull-add.php?bug_id={$bug_id}'>Add a Pull Request</a></p>";
}

// Display comments
$commentRepository = $container->get(CommentRepository::class);
$bug_comments = is_int($bug_id) ? $commentRepository->findByBugId($bug_id) : [];

if ($show_bug_info && is_array($bug_comments) && count($bug_comments) && $bug['status'] !== 'Spam') {
    $history_tabs = [
        'type_all'     => 'All',
        'type_comment' => 'Comments',
        'type_log'     => 'Changes',
        'type_svn'     => 'Git/SVN commits',
        'type_related' => 'Related reports'
    ];

    if (!isset($_COOKIE['history_tab']) || !isset($history_tabs[$_COOKIE['history_tab']])) {
        $active_history_tab = 'type_all';
    } else {
        $active_history_tab = $_COOKIE['history_tab'];
    }
    echo '<h2 style="border-bottom:2px solid #666;margin-bottom:0;padding:5px 0;">History</h2>',
            "<div id='comment_filter' class='controls comments'>";

    foreach ($history_tabs as $id => $label)
    {
        $class_extra = ($id == $active_history_tab) ? 'active' : '';
        echo "<span id='{$id}' class='control {$class_extra}' onclick='do_comment(this);'>{$label}</span>";
    }

    echo '            </div>
            ';

    echo "<div id='comments_view' style='clear:both;'>\n";
    foreach ($bug_comments as $row) {
        output_note($row['id'], $row['added'], $row['email'], $row['comment'], $row['comment_type'], $row['comment_name'], !($active_history_tab == 'type_all' || ('type_' . $row['comment_type']) == $active_history_tab));
    }
    echo "</div>\n";
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

$bug_JS = <<< bug_JS
<script src='js/util.js'></script>
<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js'></script>
<script src="js/jquery.cookie.js"></script>
<script>
function do_comment(nd)
{
    $('#comment_filter > .control.active').removeClass("active");
    $(nd).addClass("active");

    $.cookie('history_tab', nd.id, { expires: 365 });

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

if ($edit == 1) {
    $bug_JS .= '
<script src="js/jquery.autocomplete-min.js"></script>
<script src="js/userlisting.php"></script>
<script src="js/search.js"></script>
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

function link_to_people($email, $text)
{
    $domain = strstr($email, "@");
    if ($domain == "@php.net") {
        $username = strstr($email, "@", true);
        return '<a href="//people.php.net/' . urlencode($username) . '">' . $text . '</a>';
    }
    return $text;
}

function output_note($com_id, $ts, $email, $comment, $comment_type, $comment_name, $is_hidden = false)
{
    global $edit, $bug_id, $dbh, $is_trusted_developer, $logged_in;

    $display = (!$is_hidden) ? '' : 'style="display:none;"';

    echo "<div class='comment type_{$comment_type}' {$display}>";
    echo '<a name="' , urlencode($ts) , '">&nbsp;</a>';
    echo "<strong>[" , format_date($ts) , "] ";
    echo link_to_people($email, spam_protect(htmlspecialchars($email))) , "</strong>\n";

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

    $dbh->prepare("DELETE FROM bugdb_comments WHERE bug='{$bug_id}' AND id='{$com_id}'")->execute();
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
    return ($thanks != 4 && $thanks != 6 && $status != 'Closed' && $status != 'Not a bug' && $status != 'Duplicate');
}
