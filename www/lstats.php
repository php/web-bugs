<?php

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
		$query.= " {$status} AND bug_type = 'Bug' AND package_name = '" . $dbh->escape($category). "' ";
	} else {
		$query.= " status='{$status}' ";
	}
	$query.= "AND bug_type NOT IN({$excluded})";

	$res = $dbh->prepare($query)->execute(array());
	$row = $res->fetchRow(MDB2_FETCHMODE_ORDERED);

	return $row[0];
}

// Input 
if (!isset($_GET['phpver'])) {
	echo "<h3>Bug stats for both <a href='lstats.php?phpver=5'>PHP 5</a> and <a href='lstats.php?phpver=6'>PHP 6</a>:</h3>\n<pre>\n";	
} else {
	$phpver = (int) $_GET['phpver'];
	echo "<h3>Bug stats for PHP $phpver:</h3>\n<pre>\n";	
}

if (isset($_GET['per_category']))
{
	$project = !empty($_GET['project']) ? $_GET['project'] : false;
	$pseudo_pkgs = get_pseudo_packages($project);
	
	$totals = array();
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
		if (!in_array($status, array('Duplicate'))) {
			$count = get_status_count ($status);
			status_print($status, $count, 30);
		}
	}

}

echo "\n</pre>\n";
