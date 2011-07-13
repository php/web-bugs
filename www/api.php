<?php
/*
	Hack note: This is for emailing the documentation team about commit/bugs emails, but could be expanded in the future.

	The API itself will probably be abandoned in the future, but here's the current URL:
	- https://bugs.php.net/api.php?type=docs&action=closed&interval=7
*/
require_once '../include/prepend.php';

$type     = isset($_GET['type'])     ? $_GET['type']           : 'unknown';
$action   = isset($_GET['action'])   ? $_GET['action']         : 'unknown';
$interval = isset($_GET['interval']) ? (int) $_GET['interval'] : 7;

if ($type === 'docs' && $action === 'closed' && $interval) {

	$query = 
	"
		SELECT bugdb_comments.reporter_name, COUNT(*) as count
		FROM bugdb_comments, bugdb 
		WHERE comment_type =  'log' 
		AND package_name IN ('Doc Build problem', 'Documentation problem', 'Translation problem', 'Online Doc Editor problem') 
		AND comment LIKE  '%+Status:      Closed</span>%'
		AND date_sub(curdate(), INTERVAL {$interval} DAY) <= ts
		AND bugdb.id = bugdb_comments.bug
		GROUP BY bugdb_comments.reporter_name
		ORDER BY count DESC
	";
	
	//@todo add error handling
	$rows = $dbh->prepare($query)->execute(array())->fetchAll(MDB2_FETCHMODE_ASSOC);
	if (!$rows) {
		echo 'The fail train has arrived.';
		exit;
	}
	
	echo serialize($rows);

} else {
	
	echo "Unknown action";
	
}
