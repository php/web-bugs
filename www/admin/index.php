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
	ob_start();
	phpinfo();

	$phpinfo = ob_get_clean();

	// Attempt to hide certain ENV vars
	$vars = array(
			$_ENV['AUTH_TOKEN'], 
			$_ENV['USER_TOKEN'], 
			$_ENV['USER_PWD_SALT']
			);

	echo str_replace($vars, '&lt;hidden&gt;', $phpinfo);

	exit;
}

response_header("Bugs admin suite");

inline_content_menu('/admin/', $action, array(
						'phpinfo' 		=> 'phpinfo()', 
						'list_lists'		=> 'Package mailing lists', 
						'list_responses'	=> 'Quick fix responses',
						'mysql'			=> 'Database status',
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
		FROM bugdb_resolves
	");

	echo "<h3>List Responses</h3>";
	echo "<pre>\n";
	while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		print_r($row);
	}
	echo "</pre>\n";

}

if ($action === 'mysql') {
	$res = $dbh->query("SHOW TABLES");

	$sql = "SELECT version() mysql_version\n";

	while ($row = $res->fetchRow(MDB2_FETCHMODE_ORDERED)) {
		$table = $row[0];
		$sql .= "\t, (SELECT COUNT(*) FROM `$table`) `cnt_$table`\n";
	}

	$res = $dbh->query($sql);
	$row = $res->fetchRow(MDB2_FETCHMODE_ASSOC);

	echo "<p>Running MySQL <b>".$row['mysql_version']."</b></p>";
	unset($row['mysql_version']);

	echo "<p>Number of rows:</p><table><tr><th>Table</th><th>#</th></tr>\n";
	foreach ($row as $k => $v) {
		echo "<tr><td>".str_replace("cnt_", "", $k)."</td>"
			."<td>$v</td></tr>\n";
	}
	echo "</table>";

	$res = $dbh->query("SHOW TABLE STATUS");
	echo "<p>Table status:</p><pre>";
	while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		var_dump($row);
	}
	echo "</pre>";
}

response_footer();
