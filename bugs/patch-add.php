<?php
session_start();
$canpatch = true;
require_once 'bugs/patchtracker.php';
require_once 'include/functions.inc';
/**
 * Numeral Captcha Class
 */
require_once 'Text/CAPTCHA/Numeral.php';
/**
 * Instantiate the numeral captcha object.
 */
$numeralCaptcha = new Text_CAPTCHA_Numeral();
$patchinfo = new Bugs_Patchtracker;
// captcha is not necessary if the user is logged in
if (isset($auth_user) && $auth_user->registered) {
    auth_require('pear.dev', 'pear.bug');
    if (isset($_SESSION['answer'])) {
        unset($_SESSION['answer']);
    }
}
$loggedin = isset($auth_user) && $auth_user->registered;
if (isset($_POST['addpatch'])) {
    if (!isset($_POST['obsoleted'])) {
        $_POST['obsoleted'] = array();
    }
    if (!isset($_POST['bug'])) {
        response_header('Error :: no bug selected');
        display_bug_error('No bug selected to add a patch to');
        response_footer();
        exit;
    }
    if (PEAR::isError($buginfo = $patchinfo->getBugInfo($_POST['bug']))) {
        response_header('Error :: invalid bug selected');
        display_bug_error('Invalid bug "' . $id . '" selected');
        response_footer();
        exit;
    }
    if (isset($_POST['email'])) {
        $email = $_POST['email'];
    } else {
        $email = '';
    }
    if (!isset($_POST['name']) || empty($_POST['name']) || !is_string($_POST['name'])) {
        $package = $buginfo['package_name'];
        $bug = $buginfo['id'];
        if (!is_string($_POST['name'])) {
            $_POST['name'] = '';
        }
        $name = $_POST['name'];
        $patches = $patchinfo->listPatches($bug);
        $errors[] = 'No patch name entered';
        $captcha = $numeralCaptcha->getOperation();
        include dirname(dirname(dirname(__FILE__))) . 
                '/templates/bugs/addpatch.php';
        exit;
    }
    if (!$loggedin) {
        try {
            $errors = array();
            if (empty($_POST['email'])) {
                $errors[] = 'Email address must be valid!';
            }
            if (!preg_match("/^[.\\w+-]+@[.\\w-]+\\.\\w{2,}\z/i",$_POST['email'])) {
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
            require_once 'bugs/pear-bug-accountrequest.php';
            $buggie = new PEAR_Bug_Accountrequest;
            $salt = $buggie->addRequest($_POST['email']);
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

            $bug = $buginfo['id'];
            PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
            $e = $patchinfo->attach($bug, 'patch', $_POST['name'],
                $buggie->handle, $_POST['obsoleted']);
            PEAR::popErrorHandling();
            if (PEAR::isError($e)) {
                $buggie->deleteRequest();
                $package = $buginfo['package_name'];
                $bug = $buginfo['id'];
                if (!is_string($_POST['name'])) {
                    $_POST['name'] = '';
                }
                $name = $_POST['name'];
                $patches = $patchinfo->listPatches($bug);
                $errors[] = $e->getMessage();
                $errors[] =
                    'Could not attach patch "' . 
                    htmlspecialchars($_POST['name']) . 
                    '" to Bug #' . $bug;
                $captcha = $numeralCaptcha->getOperation();
                $_SESSION['answer'] = $numeralCaptcha->getAnswer();
                include dirname(dirname(dirname(__FILE__))) . 
                        '/templates/bugs/addpatch.php';
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
            localRedirect('/bugs/patch-display.php?bug=' . $bug . '&patch=' .
                urlencode($_POST['name']) . '&revision=' . $e);
            exit;
        } catch (Exception $e) {
            $package = $buginfo['package_name'];
            $bug = $buginfo['id'];
            if (!is_string($_POST['name'])) {
                $_POST['name'] = '';
            }
            $name = $_POST['name'];
            $patches = $patchinfo->listPatches($bug);
            $captcha = $numeralCaptcha->getOperation();
            $_SESSION['answer'] = $numeralCaptcha->getAnswer();
            include dirname(dirname(dirname(__FILE__))) . 
                    '/templates/bugs/addpatch.php';
            exit;
        }
    }
    $bug = $buginfo['id'];
    PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
    $e = $patchinfo->attach($bug, 'patch', $_POST['name'], $auth_user->handle,
        $_POST['obsoleted']);
    PEAR::popErrorHandling();
    if (PEAR::isError($e)) {
        $package = $buginfo['package_name'];
        $bug = $buginfo['id'];
        if (!is_string($_POST['name'])) {
            $_POST['name'] = '';
        }
        $name = $_POST['name'];
        $patches = $patchinfo->listPatches($bug);
        $errors = array($e->getMessage(),
            'Could not attach patch "' . 
            htmlspecialchars($_POST['name']) . 
            '" to Bug #' . $bug);
        $captcha = $numeralCaptcha->getOperation();
        $_SESSION['answer'] = $numeralCaptcha->getAnswer();

        include dirname(dirname(dirname(__FILE__))) . 
                '/templates/bugs/addpatch.php';
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

        $patchName = htmlspecialchars($_POST['name']);

        $rev       = $e;

        $mailData = array(
            'id'         => $bug,
            'url'        => 'http://' . PEAR_CHANNELNAME . 
                        "/bugs/patch-display.php?bug=$bug&patch=$patchName&revision=$rev&display=1",
            'package'    => $buginfo['package_name'],
            'summary'    => $dbh->getOne('SELECT sdesc from bugdb
                WHERE id=?', array($bug)),
            'date'       => date('Y-m-d H:i:s'),
            'name'       => $_POST['name'],
            'packageUrl' => 'http://' . PEAR_CHANNELNAME .
                            '/bugs/bug.php?id=' . $bug,
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
    $package = $buginfo['package_name'];
    $bug = $buginfo['id'];
    $name = $_POST['name'];
    $patches = $patchinfo->listPatches($bug);
    $errors = array();
    include dirname(dirname(dirname(__FILE__))) . '/templates/bugs/patchadded.php';
    exit;
}
if (!isset($_GET['bug'])) {
    response_header('Error :: no bug selected');
    display_bug_error('No bug selected to add a patch to');
    response_footer();
    exit;
}
if (PEAR::isError($buginfo = $patchinfo->getBugInfo($_GET['bug']))) {
    response_header('Error :: invalid bug selected');
    display_bug_error('Invalid bug "' . $id . '" selected');
    response_footer();
    exit;
}
$email = isset($_GET['email']) ? $_GET['email'] : '';
$errors = array();
$package = $buginfo['package_name'];
$bug = $buginfo['id'];
$name = isset($_GET['patch']) ? $_GET['patch'] : '';
$patches = $patchinfo->listPatches($bug);
$captcha = $numeralCaptcha->getOperation();
$_SESSION['answer'] = $numeralCaptcha->getAnswer();
include dirname(dirname(dirname(__FILE__))) . '/templates/bugs/addpatch.php';
