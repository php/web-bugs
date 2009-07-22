<?php /* vim: set noet ts=4 sw=4: : */

$bug_id = (int) $_REQUEST['id'];

if (!$bug_id) {
	echo json_encode(array('result' => array('error' => 'Missing bug id')));
	exit;
}

/**
 * Obtain common includes
 */
require_once './include/prepend.inc';

// Authenticate
if ($_POST['token'] != md5(getenv('TOKEN'))) {
	echo json_encode(array('result' => array('error' => 'Invalid token')));
	exit;
}

# fetch info about the bug into $bug
$bug = bugs_get_bug($bug_id);

if (!is_array($bug)) {
	echo json_encode(array('result' => array('error' => 'No such bug')));
	exit;
}

if (!empty($_POST['ncomment']) && !empty($_POST['user'])) {
	$user = htmlspecialchars(trim($_POST['user']));
	$ncomment = htmlspecialchars(trim($_POST['ncomment']));
	$res = $dbh->prepare('
		INSERT INTO bugdb_comments (bug, email, ts, comment, reporter_name, handle)
		VALUES (?, ?, NOW(), ?, ?, ?)
	')->execute(array ($bug_id, "{$user}@php.net", $ncomment, $user, $user));

	if ($res) {
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
