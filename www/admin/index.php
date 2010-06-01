<?php
require_once '../../include/prepend.php';
session_start();

bugs_authenticate($user, $pw, $logged_in, $is_trusted_developer);

if (!$logged_in) {
	response_footer("Please login");
	exit;
}

$actions = array('list_lists', 'phpinfo');
$action  = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : 'list_lists';

if ($action === 'phpinfo') {
	phpinfo();
	exit;
}

response_header("Bugs admin suite");

if ($action === 'list_lists') {

	$res = $dbh->query("
		SELECT name, list_email 
		FROM bugdb_pseudo_packages 
		WHERE project = 'php'
		AND LENGTH(list_email) > 0
		ORDER BY list_email
	");

	echo "<dl>\n";
	while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		echo "<dt>", $row['name'], ": </dt>\n<dd>", $row['list_email'], "</dd>\n";
	}
	echo "</dl>\n";
}

response_footer();
