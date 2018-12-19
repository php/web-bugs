<?php

use App\Repository\PackageRepository;

require_once '../../include/prepend.php';
session_start();

bugs_authenticate($user, $pw, $logged_in, $user_flags);

if (!$logged_in) {
	response_header("Bugs admin suite");
	response_footer("Please login");
	exit;
}

$actions = [
	'phpinfo' 		=> 'phpinfo()',
	'list_lists'		=> 'Package mailing lists',
	'list_responses'	=> 'Quick fix responses',
	'mysql'			=> 'Database status',
];

$action  = !empty($_GET['action']) && isset($actions[$_GET['action']]) ? $_GET['action'] : 'list_lists';

response_header("Bugs admin suite");
inline_content_menu('/admin/', $action, $actions);

if ($action === 'phpinfo') {
	ob_start();
	phpinfo();

	$phpinfo = ob_get_clean();

	// Attempt to hide certain ENV vars
	$vars = [
			getenv('AUTH_TOKEN'),
			getenv('USER_TOKEN'),
			getenv('USER_PWD_SALT')
			];

	$phpinfo = str_replace($vars, '&lt;hidden&gt;', $phpinfo);

	// Semi stolen from php-web
	$m = [];

	preg_match('!<body.*?>(.*)</body>!ims', $phpinfo, $m);

	$m[1] = preg_replace('!<a href="http://www.php.net/"><img.*?></a>!ims', '', $m[1]);
	$m[1] = preg_replace('!<a href="http://www.zend.com/"><img.*?></a>!ims', '', $m[1]);
	$m[1] = str_replace(' width="600"', ' width="80%"', $m[1]);

	echo $m[1];

} elseif ($action === 'list_lists') {
	echo "<dl>\n";
	foreach ((new PackageRepository($dbh))->findLists() as $row) {
		echo "<dt>", $row['name'], ": </dt>\n<dd>", mailto_list(explode(',', $row['list_email'])), "</dd>\n";
	}
	echo "</dl>\n";
} elseif ($action === 'list_responses') {

	$res = $dbh->query("
		SELECT *
		FROM bugdb_resolves
	");

	echo "<h3>List Responses</h3>\n";

	$rows = [];
	while ($row = $res->fetch()) {
		// This is ugly but works (tm)
		$row['message'] = nl2br($row['message']);

		$rows[] = $row;
	}

	admin_table_dynamic($rows);
} elseif ($action === 'mysql') {
	$res = $dbh->query("SHOW TABLES");

	$sql = "SELECT version() mysql_version\n";

	while ($row = $res->fetch(\PDO::FETCH_NUM)) {
		$table = $row[0];
		$sql .= "\t, (SELECT COUNT(*) FROM `$table`) `cnt_$table`\n";
	}

	$res = $dbh->query($sql);
	$row = $res->fetch();

	echo "<p>Running MySQL <b>".$row['mysql_version']."</b></p>";
	unset($row['mysql_version']);

	echo "<h3>Number of rows:</h3>\n";

	$rows = [];

	foreach($row as $key => $value) {
		$rows[] = [str_replace('cnt_', '', $key), $value];
	}

	admin_table_static(['Table', 'Rows'], $rows);

	$rows = [];
	$res = $dbh->query("SHOW TABLE STATUS");
	echo "<h3>Table status:</h3>\n";
	while ($row = $res->fetch()) {
		$rows[] = $row;
	}

	admin_table_dynamic($rows);
}

response_footer();
