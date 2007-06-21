<?php

session_start();
$canpatch = true;

require_once './include/prepend.inc';

/* Input vars */
$bug_id = isset ($_REQUEST['bug_id']) ? (int) $_REQUEST['bug_id'] : 0;
$patchname = isset($_REQUEST['patchname']) ? $_REQUEST['patchname'] : '';
$email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';

if (empty($bug_id)) {
    response_header('Error :: no bug selected');
    display_bug_error('No bug selected to add a patch to');
    response_footer();
    exit;
}

/**
 * Numeral Captcha Class
 */
require_once 'Text/CAPTCHA/Numeral.php';

/**
 * Instantiate the numeral captcha object.
 */
$numeralCaptcha = new Text_CAPTCHA_Numeral();

// captcha is not necessary if the user is logged in
if (isset($auth_user) && $auth_user->registered) {
    auth_require('pear.dev', 'pear.bug');
    if (isset($_SESSION['answer'])) {
        unset($_SESSION['answer']);
    }
}

/**
 * Bug Patch tracker class 
 */
require_once 'include/classes/bug_patchtracker.php';
$patchinfo = new Bug_Patchtracker;

if (PEAR::isError($buginfo = $patchinfo->getBugInfo($bug_id))) {
    response_header('Error :: invalid bug selected');
    display_bug_error("Invalid bug #{bug_id} selected");
    response_footer();
    exit;
}
$package = $buginfo['package_name'];

$loggedin = isset($auth_user) && $auth_user->registered;

if (isset($_POST['addpatch'])) {
    $obsoleted = (!isset($_POST['obsoleted']) || !is_array($_POST['obsoleted'])) ? array() : $_POST['obsoleted'];

    if (empty($patchname)) {
        $patches = $patchinfo->listPatches($bug_id);
        $errors[] = 'Invalid or empty patch name entered';
        $captcha = $numeralCaptcha->getOperation();
        include $templates_path . '/templates/addpatch.php';
        exit;
    }
    if (!$loggedin) {
        try {
            $errors = array();
            if (!empty($email) && !is_valid_email($email)) {
                $errors[] = 'Email address must be valid!';
            }
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
            if (count($errors)) {
                throw new Exception('');
            }
            // user doesn't exist yet
            require_once 'include/classes/bug_accountrequest.php';
            $buggie = new Bug_Accountrequest;
            $salt = $buggie->addRequest($email);
            if (is_array($salt)) {
                $errors = $salt;
                response_header('Add patch - Problems');
                throw new Exception('');
            }
            if (PEAR::isError($salt)) {
                $errors[] = $salt;
                response_header('Add patch - Problems');
                throw new Exception('');
            }
            if ($salt === false) {
                $errors[] = 'Your account cannot be added to the queue.'
                     . ' Please write a mail message to the '
                     . ' <i>pear-dev</i> mailing list.';
                response_header('Add patch - Problems');
                throw new Exception('');
            }

            PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
            $e = $patchinfo->attach($bug_id, 'patch', $patchname, $buggie->handle, $obsoleted);
            PEAR::popErrorHandling();
            if (PEAR::isError($e)) {
                $buggie->deleteRequest();
                $patches = $patchinfo->listPatches($bug_id);
                $errors[] = $e->getMessage();
                $errors[] = 'Could not attach patch "' . clean($patchname) . '" to Bug #' . $bug_id;
                $captcha = $numeralCaptcha->getOperation();
                $_SESSION['answer'] = $numeralCaptcha->getAnswer();
                include $templates_path . '/templates/addpatch.php';
                exit;
            }
            try {
                $buggie->sendEmail();
            } catch (Exception $e) {
                response_header('Error sending confirmation email');
                report_error(array('Patch was successfully attached, but account confirmation email not sent, please report to pear-core@lists.php.net', $e));
                response_footer();
                exit;
            }
            localRedirect("patch-display.php?bug_id={$bug_id}&patch=" . urlencode($patchname) . '&revision=' . $e);
            exit;
        } catch (Exception $e) {
            $patches = $patchinfo->listPatches($bug_id);
            $captcha = $numeralCaptcha->getOperation();
            $_SESSION['answer'] = $numeralCaptcha->getAnswer();
            include $templates_path . '/templates/addpatch.php';
            exit;
        }
    }
    PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
    $e = $patchinfo->attach($bug_id, 'patch', $patchname, $auth_user->handle, $obsoleted);
    PEAR::popErrorHandling();
    if (PEAR::isError($e)) {
        $patches = $patchinfo->listPatches($bug_id);
        $errors = array($e->getMessage(), 'Could not attach patch "' . clean($patchname) . '" to Bug #' . $bug_id);
        $captcha = $numeralCaptcha->getOperation();
        $_SESSION['answer'] = $numeralCaptcha->getAnswer();
        include $templates_path . '/templates/addpatch.php';
        exit;
    }
    // {{{ Email after the patch is added.
    if (!isset($buggie)) {
        /**
         * Email the package maintainers/leaders about
         * the new patch added to their bug request.
         */
        require_once 'Damblan/Mailer.php';
        require_once 'Damblan/Bugs.php';

        $patchName = clean($patchname);

        $rev       = $e;

        $mailData = array(
            'id'         => $bug_id,
            'url'        => "http://{$site_url}{$basedir}/patch-display.php?bug_id={$bug_id}&patch={$patchName}&revision={$rev}&display=1",
            'package'    => $package,
            'summary'    => $dbh->getOne("SELECT sdesc from bugdb WHERE id = {$bug_id}"),
            'date'       => date('Y-m-d H:i:s'),
            'name'       => $patchname,
            'packageUrl' => "http://{$site_url}{$basedir}/bug.php?id={$bug_id}",
        );

        $additionalHeaders['To'] = Damblan_Bugs::getMaintainers($buginfo['package_name']);

        $mailer = Damblan_Mailer::create('Patch_Added', $mailData);

        $res = true;

        if (!DEVBOX) {
            $res = $mailer->send($additionalHeaders);
        }

        if (PEAR::isError($res)) {
            // Patch not sent. Let's handle it here but not now..
        }
    }
    // }}}

    $patches = $patchinfo->listPatches($bug_id);
    $errors = array();
    include $templates_path . '/templates/patchadded.php';
    exit;
}

$errors = array();
$patches = $patchinfo->listPatches($bug_id);
$captcha = $numeralCaptcha->getOperation();
$_SESSION['answer'] = $numeralCaptcha->getAnswer();

include $templates_path . '/templates/addpatch.php';
