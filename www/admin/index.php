<?php
require_once '../../include/prepend.php';
session_start();

bugs_authenticate($user, $pw, $logged_in, $user_flags);

$is_trusted_developer = ($user_flags & BUGS_TRUSTED_DEV);

if (!$logged_in) {
	response_header("Bugs admin suite");
	response_footer("Please login");
	exit;
}

$actions = array('list_lists', 'list_responses', 'phpinfo');
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

if ($action === 'list_responses') {

	$res = $dbh->query("
		SELECT id, name, status, title, message, project, package_name, webonly
		FROM bug_resolves
		ORDER BY name
	");

	echo "<h3>List Responses</h3>";
	echo "<dl>\n";
	while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		echo "<dt>", $row['name'], " (", $row['id'], "): </dt>\n";
		echo "<dd>", $row['title'], "</dd>\n";
		echo "<dd>", $row['status'], "</dd>\n";
		echo "<dd>", $row['project'], "</dd>\n";
		echo "<dd>", $row['package_name'], "</dd>\n";
		echo "<dd>", $row['webonly'], "</dd>\n";
		echo "<dd>", $row['message'], "</dd>\n";
	}
	echo "</dl>\n";

}

response_footer();
