<?php /* vim: set noet ts=4 sw=4: : */

function status_print ($status, $num) {
	$str = ucfirst($status).":";
	$str.= str_repeat(" ", 14-strlen($str));
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
	$query.= " status='$status' ";
	$query.= (empty($category)) ? "AND bug_type NOT LIKE '%Change Request%'" : "AND bug_type='$category'";

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
	echo "<h3>Bug stats for both <a href='lstats.php?phpver=3'>PHP 3</a> and <a href='lstats.php?phpver=4'>PHP 4</a>:</h3><pre>\n";	
} else {
	echo "<h3>Bug stats for PHP $phpver:</h3><pre>\n";	
}

mysql_pconnect("localhost","nobody","");
mysql_select_db("php3");

if (isset($per_category)) {
	include ('bugtypes.inc');

	foreach ($items as $category => $name) {
		$out = "$category";
		foreach($statuses as $status) {
			$count = get_status_count ($status, $category);
			$out.="|$status=$count";
		}
		echo "$out\n<br>";
	}

} else {

	foreach($statuses as $status) {
		$count = get_status_count ($status);
		status_print($status, $count);
	}
}

echo "</pre>\n";
?>
