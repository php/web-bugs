<?php

use App\Repository\BugRepository;

/**
 * This API page is used by https://svn.php.net/viewvc/SVNROOT/commit-bugs.php
 * to manage bugs automatically.
 */

session_start();

$bug_id = (isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0);

if (!$bug_id) {
	echo json_encode(['result' => ['error' => 'Missing bug id']]);
	exit;
}

// Obtain common includes
require_once '../include/prepend.php';

if (isset($_POST['MAGIC_COOKIE'])) {
	list($user, $pwd) = explode(":", base64_decode($_POST['MAGIC_COOKIE']), 2);
	$auth_user = new stdClass;
	$auth_user->handle = $user;
	$auth_user->password = $pwd;
} else {
	echo json_encode(['result' => ['error' => 'Missing credentials']]);
	exit;
}

bugs_authenticate($user, $pwd, $logged_in, $user_flags);

if (empty($auth_user->handle)) {
	echo json_encode(['result' => ['error' => 'Invalid user or password']]);
	exit;
}

// fetch info about the bug into $bug
$bugRepository = new BugRepository($dbh);
$bug = $bugRepository->findOneById($bug_id);

if (!is_array($bug)) {
	echo json_encode(['result' => ['error' => 'No such bug']]);
	exit;
}

if (!bugs_has_access($bug_id, $bug, $pwd, $user_flags)) {
	echo json_encode(['result' => ['error' => 'No access to bug']]);
	exit;
}

if (!empty($_POST['ncomment']) && !empty($_POST['user'])) {
	$user = htmlspecialchars(trim($_POST['user']));
	$ncomment = htmlspecialchars(trim($_POST['ncomment']));
	$from = "{$user}@php.net";

	try {
		/* svn log comment */
		bugs_add_comment($bug_id, $from, $user, $ncomment, 'svn');

		/* Close the bug report as requested if it is not already closed */
		if (!empty($_POST['status'])
			&& $bug['status'] !== 'Closed'
			&& $_POST['status'] === 'Closed') {
			/* Change the bug status to Closed */
			bugs_status_change($bug_id, 'Closed');

			$in = $bug;
			/* Just change the bug status */
			$in['status'] = $_POST['status'];

			$changed = bug_diff($bug, $in);
			if (!empty($changed)) {
				$log_comment = bug_diff_render_html($changed);
				if (!empty($log_comment)) {
					/* Add a log of status change */
					bugs_add_comment($bug_id, $from, '', $log_comment, 'log');
				}
			}

			/* Send a mail notification when automatically closing a bug */
			mail_bug_updates($bug, $in, $from, $ncomment, 1, $bug_id);
		}

		echo json_encode(['result' => ['status' => $bug]]);
		exit;
	} catch (Exception $e) {
		echo json_encode(['result' => ['error' => $e->getMessage()]]);
		exit;
	}
} else if (!empty($_POST['getbug'])) {
	echo json_encode(['result' => ['status' => $bug]]);
	exit;
}

echo json_encode(['result' => ['error' => 'Nothing to do']]);
