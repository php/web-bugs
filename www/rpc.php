<?php

$bug_id = (int) $_REQUEST['id'];

if (!$bug_id) {
	echo json_encode(array('result' => array('error' => 'Missing bug id')));
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
	echo json_encode(array('result' => array('error' => 'Missing credentials')));
	exit;
}

bugs_authenticate($user, $pwd, $logged_in, $user_flags);

$is_trusted_developer = ($user_flags & BUGS_TRUSTED_DEV);

if (empty($auth_user->handle)) {
	echo json_encode(array('result' => array('error' => 'Invalid user or password')));
	exit;
}

// fetch info about the bug into $bug
$bug = bugs_get_bug($bug_id);

if (!is_array($bug)) {
	echo json_encode(array('result' => array('error' => 'No such bug')));
	exit;
}

if (!bugs_has_access($bug_id, $bug, $pwd, $user_flags)) {
	echo json_encode(array('result' => array('error' => 'No access to bug')));
	exit;
}	

if (!empty($_POST['ncomment']) && !empty($_POST['user'])) {
	$user = htmlspecialchars(trim($_POST['user']));
	$ncomment = htmlspecialchars(trim($_POST['ncomment']));
	$prep = $dbh->prepare("
		INSERT INTO bugdb_comments (bug, email, ts, comment, reporter_name, comment_type)
		VALUES (?, ?, NOW(), ?, ?, 'svn')
	");
	$res = $prep->execute(array ($bug_id, "{$user}@php.net", $ncomment, $user));

	if ($res) {
		/* Close the bug report as requested if it is not already closed */
		if (!empty($_POST['status'])
			&& $bug['status'] !== 'Closed' 
			&& $_POST['status'] === 'Closed') {
			$prep = $dbh->prepare("
				UPDATE bugdb
				  SET status = 'Closed'
				  WHERE id = ?
				  LIMIT 1
			");
			$prep->execute(array($bug_id));
		}
		
		echo json_encode(array('result' => array('status' => $bug)));
		exit;
	} else {
		echo json_encode(array('result' => array('error' => MDB2::errorMessage($res))));
		exit;
	}
} else if (!empty($_POST['getbug'])) {
	echo json_encode(array('result' => array('status' => $bug)));
	exit;
}

echo json_encode(array('result' => array('error' => 'Nothing to do')));
