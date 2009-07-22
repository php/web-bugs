<?php

require_once '../include/prepend.inc';

if (!isset($_GET['bug_id']) && !isset($_GET['bug'])) {
    response_header('Error :: no bug selected');
    display_bug_error('No patch selected to view');
    response_footer();
    exit;
}

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $is_trusted_developer);
$canpatch = ($logged_in == 'developer');

$revision = isset($_GET['revision']) ? $_GET['revision'] : null;
$patch    = isset($_GET['patch'])    ? $_GET['patch'] : null;

$bug_id = !empty($_GET['bug']) ? (int) $_GET['bug'] : 0;
if (empty($bug_id)) {
    $bug_id = (int) $_GET['bug_id'];
}

require "{$ROOT_DIR}/include/classes/bug_patchtracker.php";
$patchinfo = new Bug_Patchtracker;

if (PEAR::isError($buginfo = $patchinfo->getBugInfo($bug_id))) {
    response_header('Error :: invalid bug selected');
    display_bug_error("Invalid bug #{$bug_id} selected");
    response_footer();
    exit;
}

$pseudo_pkgs = get_pseudo_packages($site);

if (isset($patch) && isset($revision)) {
    if ($revision == 'latest') {
        $revisions = $patchinfo->listRevisions($buginfo['id'], $patch);
        if (isset($revisions[0])) {
            $revision = $revisions[0][0];
        }
    }

    $path = $patchinfo->getPatchFullpath($bug_id, $patch, $revision);
    if (!file_exists($path)) {
        response_header('Error :: no such patch/revision');
        display_bug_error('Invalid patch/revision specified');
        response_footer();
        exit;
    }

    if ($site != 'php' && $patchinfo->userNotRegistered($bug_id, $patch, $revision)) {
        response_header('User has not confirmed identity');
        display_bug_error('The user who submitted this patch has not yet confirmed their email address.');
        echo '<p>If you submitted this patch, please check your email.</p>' .
            '<p><strong>If you do not have a confirmation message</strong>, <a href="resend-request-email.php?' .
            'handle=' . urlencode($patchinfo->getDeveloper($bug_id, $patch, $revision))
            . '">click here to re-send</a> or write a message to' .
            ' <a href="mailto:' . PEAR_DEV_EMAIL . '">' . PEAR_DEV_EMAIL . '</a> asking for manual approval of your account.</p>';
        response_footer();
        exit;
    }

    require_once 'HTTP.php';
    if (isset($_GET['download'])) {
        header('Last-modified: ' . HTTP::date(filemtime($path)));
        header('Content-type: application/octet-stream');
        header('Content-disposition: attachment; filename="' . $patch . '.patch.txt"');
        header('Content-length: '.filesize($path));
        readfile($path);
        exit;
    }
    $patchcontents = $patchinfo->getPatch($buginfo['id'], $patch, $revision);

    if (PEAR::isError($patchcontents)) {
        response_header('Error :: Cannot retrieve patch');
        display_bug_error('Internal error: Invalid patch/revision specified (is in database, but not in filesystem)');
        response_footer();
        exit;
    }

    $package     = $buginfo['package_name'];
    $bug         = $buginfo['id'];
    $handle      = $patchinfo->getDeveloper($bug, $patch, $revision);
    $obsoletedby = $patchinfo->getObsoletingPatches($bug, $patch, $revision);
    $obsoletes   = $patchinfo->getObsoletePatches($bug, $patch, $revision);
    $patches     = $patchinfo->listPatches($bug);
    $revisions   = $patchinfo->listRevisions($bug, $patch);

    response_header('Bug #' . clean($bug) . ' :: Patches');
    include "{$ROOT_DIR}/templates/listpatches.php";

    if (isset($_GET['diff']) && $_GET['diff'] && isset($_GET['old']) && is_numeric($_GET['old'])) {
        $old = $patchinfo->getPatchFullpath($bug_id, $patch, $_GET['old']);
        $new = $path;
        if (!realpath($old) || !realpath($new)) {
            response_header('Error :: Cannot retrieve patch');
            display_bug_error('Internal error: Invalid patch revision specified for diff');
            response_footer();
            exit;
        }

        require_once "{$ROOT_DIR}/include/classes/bug_diff_renderer.php";

        assert_options(ASSERT_WARNING, 0);
        $d    = new Text_Diff($orig = file($old), $now = file($new));
        $diff = new Bug_Diff_Renderer($d);
        include "{$ROOT_DIR}/templates/patchdiff.php";
        response_footer();
        exit;
    }
    include "{$ROOT_DIR}/templates/patchdisplay.php";
    response_footer();
    exit;
}

$bug      = $buginfo['id'];
$patches  = $patchinfo->listPatches($bug);
response_header('Bug #' . clean($bug) . ' :: Patches');
include "{$ROOT_DIR}/templates/listpatches.php";
response_footer();
