<?php

use App\Repository\BugRepository;
use App\Repository\PatchRepository;
use App\Utils\Captcha;
use App\Utils\PatchTracker;
use App\Utils\Uploader;

// Obtain common includes
require_once '../include/prepend.php';

$uploader = new Uploader();
$patchTracker = new PatchTracker($dbh, $uploader);
$patchRepository = new PatchRepository($dbh);

session_start();

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

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

$bugRepository = new BugRepository($dbh);

if (!($buginfo = $bugRepository->findOneById($bug_id))) {
	response_header('Error :: invalid bug selected');
	display_bug_error("Invalid bug #{$bug_id} selected");
	response_footer();
	exit;
}

$package_name = $buginfo['package_name'];

// captcha is not necessary if the user is logged in
if (!$logged_in) {
	$captcha = new Captcha();
}

$show_bug_info = bugs_has_access($bug_id, $buginfo, $pw, $user_flags);

if (!$show_bug_info) {
	response_header('Private report');
	display_bug_error("The bug #{$bug_id} is not available to public");
	response_footer();
	exit;
}

$patch_name = (!empty($_GET['patchname']) && is_string($_GET['patchname'])) ? $_GET['patchname'] : '';
$patch_name = (!empty($_POST['name']) && is_string($_POST['name'])) ? $_POST['name'] : $patch_name;
$patch_name_url = urlencode($patch_name);

if (isset($_POST['addpatch'])) {
	if (!isset($_POST['obsoleted'])) {
		$_POST['obsoleted'] = [];
	}

	// Check that patch name is given (required always)
	if (empty($patch_name)) {
		$patches = $patchRepository->findAllByBugId($bug_id);
		$errors[] = 'No patch name entered';
		include "{$ROOT_DIR}/templates/addpatch.php";
		exit;
	}

	if (!$logged_in) {
		try {
			$errors = [];

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
				throw new \Exception('');
			}

			try {
				$revision = $patchTracker->attach($bug_id, 'patch', $patch_name, $email, $_POST['obsoleted']);
			} catch (\Exception $e) {
				$patches = $patchRepository->findAllByBugId($bug_id);
				$errors[] = $e->getMessage();
				$errors[] = 'Could not attach patch "'.htmlspecialchars($patch_name).'" to Bug #'.$bug_id;
				include "{$ROOT_DIR}/templates/addpatch.php";

				exit;
			}

			redirect("patch-display.php?bug={$bug_id}&patch={$patch_name_url}&revision={$revision}");
		} catch (\Exception $e) {
			$patches = $patchRepository->findAllByBugId($bug_id);
			include "{$ROOT_DIR}/templates/addpatch.php";
			exit;
		}
	} else {
		$email = $auth_user->email;
	}

	try {
		$revision = $patchTracker->attach($bug_id, 'patch', $patch_name, $auth_user->email, $_POST['obsoleted']);
	} catch (\Exception $e) {
		$patches = $patchRepository->findAllByBugId($bug_id);
		$errors = [
			$e->getMessage(),
			'Could not attach patch "'.htmlspecialchars($patch_name, ENT_QUOTES).'" to Bug #'.$bug_id
		];
		include "{$ROOT_DIR}/templates/addpatch.php";

		exit;
	}

	// Add a comment to the bug report.
	$patch_url = "{$site_method}://{$site_url}{$basedir}/patch-display.php?bug={$bug_id}&patch={$patch_name_url}&revision={$revision}";

	$text = <<<TXT
The following patch has been added/updated:

Patch Name: {$patch_name}
Revision:   {$revision}
URL:        {$patch_url}
TXT;

	$res = bugs_add_comment($bug_id, $auth_user->email, $auth_user->name, $text, 'patch');

	// Send emails
	mail_bug_updates($buginfo, $buginfo, $auth_user->email, $text, 4, $bug_id);

	$patches = $patchRepository->findAllByBugId($bug_id);
	$errors = [];
	include "{$ROOT_DIR}/templates/patchadded.php";
	exit;
}

$email = isset($_GET['email']) ? $_GET['email'] : '';
$patches = $patchRepository->findAllByBugId($bug_id);

include "{$ROOT_DIR}/templates/addpatch.php";
