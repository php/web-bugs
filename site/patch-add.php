<?php

require_once '../include/prepend.inc';

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

if (PEAR::isError($buginfo = bugs_get_bug($bug_id))) {
	response_header('Error :: invalid bug selected');
	report_error("Invalid bug #{$bug_id} selected");
	response_footer();
	exit;
}

$package_name = $buginfo['package_name'];

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $is_trusted_developer);

// captcha is not necessary if the user is logged in
if ($logged_in) {
	unset($_SESSION['answer']);
} else {
	require_once 'Text/CAPTCHA/Numeral.php';
	$numeralCaptcha = new Text_CAPTCHA_Numeral();
}

require_once "{$ROOT_DIR}/include/classes/bug_patchtracker.php";
$patchinfo = new Bug_Patchtracker;

if (isset($_POST['addpatch'])) {
	if (!isset($_POST['obsoleted'])) {
		$_POST['obsoleted'] = array();
	}

	$email = isset($_POST['email']) ? $_POST['email'] : '';

	if (!isset($_POST['name']) || empty($_POST['name']) || !is_string($_POST['name'])) {
		if (!is_string($_POST['name'])) {
			$_POST['name'] = '';
		}
		$name = $_POST['name'];
		$patches = $patchinfo->listPatches($bug_id);
		$errors[] = 'No patch name entered';
		include "{$ROOT_DIR}/templates/addpatch.php";
		exit;
	}

	if (!$logged_in) {
		try {
			$errors = array();

			if (!is_valid_email($_POST['email'])) {
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

			PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
			$e = $patchinfo->attach($bug_id, 'patch', $_POST['name'], $_POST['email'], $_POST['obsoleted']);
			PEAR::popErrorHandling();

			if (PEAR::isError($e)) {
				if (!is_string($_POST['name'])) {
					$_POST['name'] = '';
				}
				$name = $_POST['name'];
				$patches = $patchinfo->listPatches($bug_id);
				$errors[] = $e->getMessage();
				$errors[] = 'Could not attach patch "' . htmlspecialchars($_POST['name']) . '" to Bug #' . $bug_id;
				include "{$ROOT_DIR}/templates/addpatch.php";
				exit;
			}

			redirect("patch-display.php?bug={$bug_id}&patch=" . urlencode($_POST['name']) . "&revision={$e}");
			exit;
		} catch (Exception $e) {
			if (!is_string($_POST['name'])) {
				$_POST['name'] = '';
			}
			$name = $_POST['name'];
			$patches = $patchinfo->listPatches($bug_id);
			include "{$ROOT_DIR}/templates/addpatch.php";
			exit;
		}
	}

	PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
	$e = $patchinfo->attach($bug_id, 'patch', $_POST['name'], $auth_user->handle, $_POST['obsoleted']);
	PEAR::popErrorHandling();
	if (PEAR::isError($e)) {
		if (!is_string($_POST['name'])) {
			$_POST['name'] = '';
		}
		$name = $_POST['name'];
		$patches = $patchinfo->listPatches($bug_id);
		$errors = array($e->getMessage(),
			'Could not attach patch "' .
			htmlspecialchars($_POST['name']) .
			'" to Bug #' . $bug_id);
		include "{$ROOT_DIR}/templates/addpatch.php";
		exit;
	}

	// Add a comment to the bug report.
	$patch_name = $_POST['name'];
	$patch_name_url = urlencode($patch_name);
	$patch_url = "http://{$site_url}{$basedir}/{$url}patch-display.php?bug={$bug_id}&patch={$patch_name_url}&revision={$e}&display=1";

	$text = <<<TXT
The following patch has been added/updated:

Patch Name: {$patch_name}
Revision:   {$e}
URL:        {$patch_url}
TXT;

	$query = '
		INSERT INTO bugdb_comments (
			bug,
			email,
			ts,
			comment,
			comment_type,
			reporter_name
		) VALUES (?, ?, NOW(), ?, "patch", ?)
	';
	$res = $dbh->prepare($query)->execute(array(
		$bug_id,
		$auth_user->email,
		$text,
		$auth_user->name,
	));

	// Send emails 
	list($mailto, $mailfrom) = get_package_mail($package_name);

	$protected_email  = '"' . spam_protect($email, 'text') . '"' .  "<{$mailfrom}>";
	$extra_headers  = "From: {$protected_email}\n";
	$extra_headers .= "Message-ID: <bug-{$cid}@{$site_url}>";

	if (!DEVBOX) {
		@mail(
			$mailto,
			"[$siteBig-BUG] {$buginfo['bug_type']} #{$bug_id} [PATCH]: {$buginfo['sdesc']}",
			$text,
			$extra_headers,
			'-f bounce-no-user@php.net'
		);
	}
	$name    = $_POST['name'];
	$patches = $patchinfo->listPatches($bug_id);
	$errors  = array();
	include "{$ROOT_DIR}/templates/patchadded.php";
	exit;
	
}

$email   = isset($_GET['email']) ? $_GET['email'] : '';
$errors  = array();
$name    = isset($_GET['patch']) ? $_GET['patch'] : '';
$patches = $patchinfo->listPatches($bug);

include "{$ROOT_DIR}/templates/addpatch.php";
