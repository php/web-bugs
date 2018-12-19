<?php
/*
	Hack note: This is for emailing the documentation team about commit/bugs emails, but could be expanded in the future.

	The API itself will probably be abandoned in the future, but here's the current URL:
	- https://bugs.php.net/api.php?type=docs&action=closed&interval=7
*/

use App\Repository\CommentRepository;

require_once '../include/prepend.php';

$type     = isset($_GET['type'])     ? $_GET['type']           : 'unknown';
$action   = isset($_GET['action'])   ? $_GET['action']         : 'unknown';
$interval = isset($_GET['interval']) ? (int) $_GET['interval'] : 7;

if ($type === 'docs' && $action === 'closed' && $interval) {
	$commentRepository = new CommentRepository($dbh);
	$rows = $commentRepository->findDocsComments($interval);

	//@todo add error handling
	if (!$rows) {
		echo 'The fail train has arrived.';
		exit;
	}

	echo serialize($rows);

} else {

	echo "Unknown action";

}
