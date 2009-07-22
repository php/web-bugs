<?php

session_start();

require_once '../include/prepend.inc';

$canpatch = true;

/* Input vars */
$bug_id = !empty($_REQUEST['bug']) ? (int) $_REQUEST['bug'] : 0;
if (empty($bug_id)) {
    $bug_id = !empty($_REQUEST['bug_id']) ? (int) $_REQUEST['bug_id'] : 0;
}

if (empty($bug_id)) {
    response_header('Error :: no bug selected');
    display_bug_error('No bug selected to add a patch to (no bug or bug_id!)');
    response_footer();
    exit;
}

require_once "{$ROOT_DIR}/include/classes/bug_patchtracker.php";

// Numeral Captcha Class
require_once 'Text/CAPTCHA/Numeral.php';
// Instantiate the numeral captcha object.
$numeralCaptcha = new Text_CAPTCHA_Numeral();

$patchinfo = new Bug_Patchtracker;
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
        report_error('No bug selected to add a patch to');
        response_footer();
        exit;
    }

    if (PEAR::isError($buginfo = $patchinfo->getBugInfo($_POST['bug']))) {
        response_header('Error :: invalid bug selected');
        report_error("Invalid bug #{$bug_id} selected");
        response_footer();
        exit;
    }

    $email = isset($_POST['email']) ? $_POST['email'] : '';

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
        include "{$ROOT_DIR}/templates/addpatch.php";
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
			if ($site != 'php') {
				require_once "{$ROOT_DIR}/include/classes/bug_accountrequest.php";
	            $buggie = new Bug_Accountrequest;
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
			}

            $bug = $buginfo['id'];
            $handle = (isset($buggie)) ? $buggie->handle : $_POST['email'];

            PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
            $e = $patchinfo->attach($bug, 'patch', $_POST['name'], $handle, $_POST['obsoleted']);
            PEAR::popErrorHandling();

            if (PEAR::isError($e)) {
            	if (isset($buggie)) {
	                $buggie->deleteRequest();
				}
                $package = $buginfo['package_name'];
                $bug = $buginfo['id'];
                if (!is_string($_POST['name'])) {
                    $_POST['name'] = '';
                }
                $name = $_POST['name'];
                $patches = $patchinfo->listPatches($bug);
                $errors[] = $e->getMessage();
                $errors[] = 'Could not attach patch "' . htmlspecialchars($_POST['name']) . '" to Bug #' . $bug;
                $captcha = $numeralCaptcha->getOperation();
                $_SESSION['answer'] = $numeralCaptcha->getAnswer();
                include "{$ROOT_DIR}/templates/addpatch.php";
                exit;
            }

            if ($site != 'php') {
	            try {
	                $buggie->sendEmail();
	            } catch (Exception $e) {
	                response_header('Error sending confirmation email');
	                report_error(array('Patch was successfully attached, but account confirmation email not sent, please report to ' .  PEAR_DEV_EMAIL, $e));
	                response_footer();
	                exit;
	            }
			}
            localRedirect('patch-display.php?bug=' . $bug . '&patch=' . urlencode($_POST['name']) . '&revision=' . $e);
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
            include "{$ROOT_DIR}/templates/addpatch.php";
            exit;
        }
    }

    $bug = $buginfo['id'];
    PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
    $e = $patchinfo->attach($bug, 'patch', $_POST['name'], $auth_user->handle, $_POST['obsoleted']);
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

        include "{$ROOT_DIR}/templates/addpatch.php";
        exit;
    }

    // {{{ Email after the patch is added and add a comment to the bug report.
    if (!isset($buggie)) {
        $patch_name = $_POST['name'];
        $url = "patch-display.php?bug=$bug&patch=$patch_name&revision=$e&display=1";
        $bugurl ='http://' . PEAR_CHANNELNAME . '/bugs/' . $url;
        // Add a comment about this in the bug report
        $text = <<<TXT
The following patch has been added/updated:

Patch Name:  $patch_name
Revision:    $e
URL:         $bugurl
TXT;

        $query = 'INSERT INTO bugdb_comments (bug, email, ts, comment, reporter_name, handle) VALUES (?, ?, NOW(), ?, ?, ?)';
        $dbh->query($query, array($bug, $auth_user->email, $text, $auth_user->name, $auth_user->handle));

        /**
         * Email the package maintainers/leaders about
         * the new patch added to their bug request.
         */
        if ($site != 'php') {
	        require_once 'bugs/pear-bugs-utils.php';
	        $patch = array(
	            'patch'        => $patch_name,
	            'bug_id'       => $bug,
	            'revision'     => $e,
	            'package_name' => $buginfo['package_name'],
	        );
	        $res = PEAR_Bugs_Utils::sendPatchEmail($patch);
	        if (PEAR::isError($res)) {
    	        // Patch email not sent. Let's handle it here but not now..
    	    }
		}
    }
    // }}}

    $package = $buginfo['package_name'];
    $bug     = $buginfo['id'];
    $name    = $_POST['name'];
    $patches = $patchinfo->listPatches($bug);
    $errors  = array();
    include "{$ROOT_DIR}/templates/patchadded.php";
    exit;
}

if (PEAR::isError($buginfo = $patchinfo->getBugInfo($bug_id))) {
    response_header('Error :: invalid bug selected');
    display_bug_error("Invalid bug #{$bug_id} selected");
    response_footer();
    exit;
}

$email   = isset($_GET['email']) ? $_GET['email'] : '';
$errors  = array();
$package = $buginfo['package_name'];
$bug     = $buginfo['id'];
$name    = isset($_GET['patch']) ? $_GET['patch'] : '';
$patches = $patchinfo->listPatches($bug);
$captcha = $numeralCaptcha->getOperation();
$_SESSION['answer'] = $numeralCaptcha->getAnswer();

include "{$ROOT_DIR}/templates/addpatch.php";
