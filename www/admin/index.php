<?php
require_once '../../include/prepend.php';
session_start();

bugs_authenticate($user, $pw, $logged_in, $user_flags);

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

inline_content_menu('/admin/', $action, array(
						'phpinfo' 		=> 'phpinfo()', 
						'list_lists'		=> 'Package mailing lists', 
						'list_responses'	=> 'Quick fix responses'
						));

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
		echo "<dt>", $row['name'], ": </dt>\n<dd>", mailto_list(explode(',', $row['list_email'])), "</dd>\n";
	}
	echo "</dl>\n";
}

if ($action === 'list_responses') {

	$res = $dbh->query("
		SELECT *
		FROM bug_resolves
	");

	echo "<h3>List Responses</h3>";
	echo "<pre>\n";
	while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		print_r($row);
	}
	echo "</pre>\n";

}

response_footer();
