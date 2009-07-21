<?php /* vim: set noet ts=4 sw=4: : */

$id = (int)$_REQUEST['id'];

if (!$id) {
  echo json_encode(array('result' => array('error' => 'Missing bug id')));
  exit;
}
      
/**
 * Obtain common includes
 */
require_once './include/prepend.inc';
  
// Authenticate
bugs_authenticate($user, $pw, $logged_in, $is_trusted_developer);

if (isset($_POST['MAGIC_COOKIE'])) {
  list($user,$pw) = explode(":", base64_decode($_POST['MAGIC_COOKIE']), 2);
} else {
  echo json_encode(array('result' => array('error' => 'Missing credentials')));
  exit;
}

if ($logged_in != 'developer') {
  echo json_encode(array('result' => array('error' => 'Invalid user or password')));
  exit;
}

# fetch info about the bug into $bug
$bug = bugs_get_bug($bug_id);

if (!is_array($bug)) {
  echo json_encode(array('result' => array('error' => 'No such bug')));
  exit;
}

if (!empty($_POST['ncomment'])) {
    $ncomment = trim($_POST['ncomment']);
    $res = $dbh->prepare('
    	INSERT INTO bugdb_comments (bug, email, ts, comment, reporter_name, handle)
		VALUES (?, ?, NOW(), ?, ?, ?)
	')->execute(array ($bug_id, $auth_user->email, $ncomment, $auth_user->name, $auth_user->handle));

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
