<?php

use App\Repository\BugRepository;
use App\Repository\PullRequestRepository;
use App\Utils\Captcha;
use App\Utils\GitHub;

// Obtain common includes
require_once '../include/prepend.php';

session_start();
$canpatch = true;

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

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

$pullinfo = new GitHub($dbh);
$pullRequestRepository = new PullRequestRepository($dbh);

if (isset($_POST['addpull'])) {
	$errors = [];
	if (empty($_POST['repository'])) {
		$errors[] = 'No repository selected';
	}
	if (empty($_POST['pull_id'])) {
		$errors[] = 'No Pull request selected';
	}

	if (!$logged_in) {
		try {
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

		} catch (Exception $e) {
			$pulls = $pullRequestRepository->findAllByBugId($bug_id);
			include "{$ROOT_DIR}/templates/addghpull.php";
			exit;
		}
	} else {
		$email = $auth_user->email;
	}

	if (!count($errors)) {
		try {
			$newpr = $pullinfo->attach($bug_id, $_POST['repository'], $_POST['pull_id'], $email);
		} catch(\Exception $e) {
			$errors = ['Could not attach pull request to Bug #'.$bug_id];

			if ($e->errorInfo[1] === 1062) {
				$errors[] = 'This pull request is already added.';
			}

			if (DEVBOX) {
				$errors[] = $e->getMessage();
			}
		}
	}

	if (count($errors)) {
		$pulls = $pullRequestRepository->findAllByBugId($bug_id);
		include "{$ROOT_DIR}/templates/addghpull.php";
		exit;
	}

	// Add a comment to the bug report.
	$text = <<<TXT
The following pull request has been associated:

Patch Name: {$newpr->title}
On GitHub:  {$newpr->html_url}
Patch:      {$newpr->patch_url}
TXT;

	$res = bugs_add_comment($bug_id, $auth_user->email, $auth_user->name, $text, 'patch');

	// Send emails
	// TODO: enable also mailing
	//mail_bug_updates($buginfo, $buginfo, $auth_user->email, $text, 4, $bug_id);

	$pulls = $pullRequestRepository->findAllByBugId($bug_id);
	$errors = [];
	include "{$ROOT_DIR}/templates/addghpull.php";
	exit;
}

$email = isset($_GET['email']) ? $_GET['email'] : '';
$pulls = $pullRequestRepository->findAllByBugId($bug_id);

include "{$ROOT_DIR}/templates/addghpull.php";
