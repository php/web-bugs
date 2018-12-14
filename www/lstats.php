<?php

use App\Repository\PackageRepository;

require '../include/prepend.php';

function status_print ($status, $num, $width, $align = STR_PAD_LEFT)
{
	echo ucfirst($status), ':', str_pad($num, $width - strlen($status), ' ', $align), "\n\n";
}

function get_status_count ($status, $category = '')
{
	global $phpver, $dbh;

	$query = "SELECT count(id) from bugdb WHERE";

	if ($phpver > 0) {
		$query .= " php_version LIKE '{$phpver}%' AND";
	}

	/* Categories which are excluded from bug count */
	$excluded = "'Feature/Change Request', 'Systems problem', 'Website Problem', 'PEAR related', 'PECL related', 'Documentation problem', 'Translation problem', 'PHP-GTK related', 'Online Doc Editor problem'";

	if ($category != '') {
		$query.= " {$status} AND bug_type = 'Bug' AND package_name = " . $dbh->quote($category);
	} else {
		$query.= " status='{$status}' ";
	}
	$query.= "AND bug_type NOT IN({$excluded})";

	$res = $dbh->prepare($query)->execute([]);
	$row = $res->fetch(\PDO::FETCH_NUM);

	return $row[0];
}

// Input
$phpver = (isset($_GET['phpver']) ? (int) $_GET['phpver'] : false);

if (!$phpver || ($phpver !== 5 && $phpver !== 7)) {
	echo "<h3>Bug stats for both <a href='lstats.php?phpver=5'>PHP 5</a> and <a href='lstats.php?phpver=7'>PHP 7</a>:</h3>\n<pre>\n";
} else {
	echo "<h3>Bug stats for PHP $phpver:</h3>\n<pre>\n";
}

if (isset($_GET['per_category']))
{
	$packageRepository = new PackageRepository($dbh);
	$pseudo_pkgs = $packageRepository->findAll($_GET['project'] ?? '');

	$totals = [];
	foreach ($pseudo_pkgs as $category => $data) {
		$count = get_status_count ("status NOT IN('to be documented', 'closed', 'not a bug', 'duplicate', 'wont fix', 'no feedback')", $category);
		if ($count > 0) {
			$totals[$category] = $count;
		}
	}
	arsort($totals);
	foreach ($totals as $category => $total) {
		status_print($category, $total, 40);
	}

} else {

	foreach ($tla as $status => $short) {
		if (!in_array($status, ['Duplicate'])) {
			$count = get_status_count ($status);
			status_print($status, $count, 30);
		}
	}

}

echo "\n</pre>\n";
