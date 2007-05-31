<?php
require 'include/functions.inc';
if (!isset($_GET['bug'])) {
    response_header('Error :: no bug selected');
    display_bug_error('No bug selected to add a patch to');
    response_footer();
    exit;
}
require 'bugs/patchtracker.php';
$patchinfo = new Bugs_Patchtracker;
if (PEAR::isError($buginfo = $patchinfo->getBugInfo($_GET['bug']))) {
    response_header('Error :: invalid bug selected');
    display_bug_error('Invalid bug "' . (int)$GET['bug'] . '" selected');
    response_footer();
    exit;
}
if (isset($_GET['patch']) && isset($_GET['revision'])) {
    if ($_GET['revision'] == 'latest') {
        $revisions = $patchinfo->listRevisions($buginfo['id'], $_GET['patch']);
        if (isset($revisions[0])) {
            $_GET['revision'] = $revisions[0][0];
        }
    }
    if (!file_exists($path = $patchinfo->getPatchFullpath($_GET['bug'], $_GET['patch'],
                                                        $_GET['revision']))) {
        response_header('Error :: no such patch/revision');
        display_bug_error('Invalid patch/revision specified');
        response_footer();
        exit;
    }
    if ($patchinfo->userNotRegistered($_GET['bug'], $_GET['patch'], $_GET['revision'])) {
        response_header('User has not confirmed identity');
        display_bug_error('The user who submitted this patch has not yet confirmed ' .
            'their email address.  ');
        echo '<p>If you submitted this patch, please check your email.</p>' .
            '<p><strong>If you do not have a confirmation message</strong>, <a href="resend-request-email.php?' .
            'handle=' . urlencode($patchinfo->getDeveloper($_GET['bug'], $_GET['patch'],
                $_GET['revision'])) . '">click here to re-send</a> or write a message to' .
            ' <a href="mailto:pear-dev@lists.php.net">pear-dev@lists.php.net</a> asking for manual approval of your account.</p>';
        response_footer();
        exit;
    }
    require_once 'HTTP.php';
    if (isset($_GET['download'])) {
        header('Last-modified: ' . HTTP::date(filemtime($path)));
        header('Content-type: application/octet-stream');
        header('Content-disposition: attachment; filename="' . $_GET['patch'] . '.patch.txt"');
        header('Content-length: '.filesize($path));
        readfile($path);
        exit;
    }
    $patchcontents = $patchinfo->getPatch($buginfo['id'], $_GET['patch'], $_GET['revision']);

    if (PEAR::isError($patchcontents)) {
        response_header('Error :: Cannot retrieve patch');
        display_bug_error('Internal error: Invalid patch/revision specified (is in database, but not in filesystem)');
        response_footer();
        exit;
    }
    $package = $buginfo['package_name'];
    $bug = $buginfo['id'];
    $handle = $patchinfo->getDeveloper($bug, $_GET['patch'], $_GET['revision']);
    $revision = $_GET['revision'];
    $patch = $_GET['patch'];
    response_header('Bug #' . clean($buginfo['id']) . ' :: Patches');
    $bug = $buginfo['id'];
    $obsoletedby = $patchinfo->getObsoletingPatches($bug, $_GET['patch'], $_GET['revision']);
    $obsoletes = $patchinfo->getObsoletePatches($bug, $_GET['patch'], $_GET['revision']);
    $patches = $patchinfo->listPatches($buginfo['id']);
    $canpatch = auth_check('pear.bug') || auth_check('pear.dev');
    include dirname(dirname(dirname(__FILE__))) . '/templates/bugs/listpatches.php';
    $revisions = $patchinfo->listRevisions($buginfo['id'], $_GET['patch']);
    $revision = $_GET['revision'];
    $patch = $_GET['patch'];
    if (isset($_GET['diff']) && $_GET['diff'] && isset($_GET['old']) && is_numeric($_GET['old'])) {
        $old = $patchinfo->getPatchFullpath($_GET['bug'], $_GET['patch'],
                                            $_GET['old']);
        $new = $path;
        if (!realpath($old) || !realpath($new)) {
            response_header('Error :: Cannot retrieve patch');
            display_bug_error('Internal error: Invalid patch revision specified for diff');
            response_footer();
            exit;
        }
        require_once 'Text/Diff.php';
        require_once 'bugs/Diff/pearweb.php';
        assert_options(ASSERT_WARNING, 0);
        $d = new Text_Diff($orig = file($old), $now = file($new));
        $diff = new Text_Diff_Renderer_Bugtracker($d);
        include dirname(dirname(dirname(__FILE__))) . '/templates/bugs/patchdiff.php';
        response_footer();
        exit;
    }
    include dirname(dirname(dirname(__FILE__))) . '/templates/bugs/patchdisplay.php';
    response_footer();
    exit;
}
response_header('Bug #' . clean($buginfo['id']) . ' :: Patches');
$bug = $buginfo['id'];
$patches = $patchinfo->listPatches($buginfo['id']);
$canpatch = auth_check('pear.bug') || auth_check('pear.dev');
include dirname(dirname(dirname(__FILE__))) . '/templates/bugs/listpatches.php';
response_footer();
