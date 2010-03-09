<?php

// Obtain common includes
require_once '../include/prepend.php';

session_start();

$canpatch = true;

/// Input vars
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

if (!($buginfo = bugs_get_bug($bug_id))) {
	response_header('Error :: invalid bug selected');
	display_bug_error("Invalid bug #{$bug_id} selected");
	response_footer();
	exit;
}

$package_name = $buginfo['package_name'];

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $is_trusted_developer);

// captcha is not necessary if the user is logged in
if (!$logged_in) {
	require_once 'Text/CAPTCHA/Numeral.php';
	$numeralCaptcha = new Text_CAPTCHA_Numeral();
}

require_once "{$ROOT_DIR}/include/classes/bug_patchtracker.php";
$patchinfo = new Bug_Patchtracker;

$patch_name = (!empty($_POST['name']) && is_string($_POST['name'])) ? $_POST['name'] : '';
$patch_name_url = urlencode($patch_name);

if (isset($_POST['addpatch'])) {
	if (!isset($_POST['obsoleted'])) {
		$_POST['obsoleted'] = array();
	}

	// Check that patch name is given (required always)
	if (empty($patch_name)) {
		$patches = $patchinfo->listPatches($bug_id);
		$errors[] = 'No patch name entered';
		include "{$ROOT_DIR}/templates/addpatch.php";
		exit;
	}

	if (!$logged_in) {
		try {
			$errors = array();

			$email = isset($_POST['email']) ? $_POST['email'] : '';

			if (!is_valid_email($email, $logged_in)) {
				$errors[] = 'Email address must be valid!';
			}

			/**
			 * Check if session answer is set, then compare
			 * it with the post captcha value. If it's not
			 * the same, then it's an incorrect password.
			 */
			if (!isset($_SESSION['answer']) || $_POST['captcha'] != $_SESSION['answer']) {
				$errors[] = 'Incorrect Captcha';
			}

			if (count($errors)) {
				throw new Exception('');
			}

			PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
			$e = $patchinfo->attach($bug_id, 'patch', $patch_name, $email, $_POST['obsoleted']);
			PEAR::popErrorHandling();

			if (PEAR::isError($e)) {
				$patches = $patchinfo->listPatches($bug_id);
				$errors[] = $e->getMessage();
				$errors[] = 'Could not attach patch "' . htmlspecialchars($patch_name) . '" to Bug #' . $bug_id;
				include "{$ROOT_DIR}/templates/addpatch.php";
				exit;
			}

			redirect("patch-display.php?bug={$bug_id}&patch={$patch_name_url}&revision={$e}");
			exit;
		} catch (Exception $e) {
			$patches = $patchinfo->listPatches($bug_id);
			include "{$ROOT_DIR}/templates/addpatch.php";
			exit;
		}
	} else {
		$email = $auth_user->email;
	}

	PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
	$e = $patchinfo->attach($bug_id, 'patch', $patch_name, $auth_user->email, $_POST['obsoleted']);
	PEAR::popErrorHandling();
	if (PEAR::isError($e)) {
		$patches = $patchinfo->listPatches($bug_id);
		$errors = array($e->getMessage(),
			'Could not attach patch "' .
			htmlspecialchars($patch_name) .
			'" to Bug #' . $bug_id);
		include "{$ROOT_DIR}/templates/addpatch.php";
		exit;
	}

	// Add a comment to the bug report.
	$patch_url = "http://{$site_url}{$basedir}/patch-display.php?bug={$bug_id}&patch={$patch_name_url}&revision={$e}";

	$text = <<<TXT
The following patch has been added/updated:

Patch Name: {$patch_name}
Revision:   {$e}
URL:        {$patch_url}
TXT;

	$res = bugs_add_comment($bug_id, $auth_user->email, $auth_user->name, $text, 'patch');

	// Send emails 
	list($mailto, $mailfrom) = get_package_mail($package_name);

	$protected_email = '"' . spam_protect($email, 'text') . '"' .  "<{$mailfrom}>";
	$extra_headers = "From: {$protected_email}\n";
	$extra_headers.= "Message-ID: <bug-{$bug_id}@{$site_url}>\n";
	$extra_headers.= "X-PHP-Site: {$siteBig}";

	bugs_mail(
		$mailto,
		"{$buginfo['bug_type']} #{$bug_id} [PATCH]: {$buginfo['sdesc']}",
		$text,
		$extra_headers
	);

	$patches = $patchinfo->listPatches($bug_id);
	$errors = array();
	include "{$ROOT_DIR}/templates/patchadded.php";
	exit;
	
}

$email = isset($_GET['email']) ? $_GET['email'] : '';
$patches = $patchinfo->listPatches($bug_id);

include "{$ROOT_DIR}/templates/addpatch.php";
