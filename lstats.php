<?php /* vim: set noet ts=4 sw=4: : */

function status_print ($status, $num, $width, $align='left') {
	$str = ucfirst($status).":";
	$str.= str_repeat(" ", $width - strlen($str) + (($align == 'right') ? (4 - strlen($num)) : 0));
	$str.= "<!-- NUM -->$num<!-- /NUM -->\n<br>";
	echo $str;
}

function get_status_count ($status, $category='')
{
	global $phpver;
	
	$query = "SELECT count(id) from bugdb WHERE";

	if ($phpver > 0) {
		$query .= " php_version LIKE '" . $phpver . "%' AND";
	}

	/* Categories which are excluded from bug count */
	$excluded = "'Feature/Change Request', 'Systems problem', 'Website Problem', 'PEAR related', 'PECL related', 'Documentation problem', 'PHP-GTK related'";

	if ($category != '') {
		$query.= " $status AND bug_type='$category' ";
	} else {
		$query.= " status='$status' ";
	}
	$query.= "AND bug_type NOT IN($excluded)";

	$result=mysql_unbuffered_query($query);
	$row=mysql_fetch_row($result);
	mysql_freeresult($result);
	return $row[0];
}

$statuses = array (	
					"open", 
					"critical", 
					"closed", 
					"analyzed",
					"verified", 
					"suspended", 
					"duplicate", 
					"assigned", 
					"feedback", 
					"bogus"
			);

if(!isset($phpver)) {
	echo "<h3>Bug stats for both <a href='lstats.php?phpver=3'>PHP 3</a> and <a href='lstats.php?phpver=4'>PHP 4</a>:</h3>\n<pre>\n";	
} else {
	echo "<h3>Bug stats for PHP $phpver:</h3>\n<pre>\n";	
}

mysql_connect("localhost","nobody","");
mysql_select_db("phpbugdb");

if (isset($per_category)) {
	include ('bugtypes.inc');

	$totals = array();
	foreach ($items as $category => $name) {
		$count = get_status_count ("status NOT IN('closed', 'bogus', 'duplicate', 'wont fix', 'no feedback')", $category);
		if ($count > 0) {
			$totals[$category] = $count;
		}
	}
	arsort($totals);
	foreach ($totals as $category => $total) {
		status_print($category, $total, 35, 'right');
	}
	
} else {

	foreach($statuses as $status) {
		$count = get_status_count ($status);
		status_print($status, $count, 14);
	}
}

echo "\n</pre>\n";
?>
