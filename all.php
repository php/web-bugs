<?php

mysql_pconnect("localhost","nobody","");
mysql_select_db("php3");

$qs = array (
	array ('bugs', 'SELECT * FROM bugdb', 'id'),
	array ('comments', 'SELECT * FROM bugdb_comments', 'bug')
);

$d = array();

foreach ($qs as $q) {
	$r = mysql_query ($q[1]);
	while ($rec = mysql_fetch_assoc($r)) {
		$d[$rec[$q[2]][$q[0]][] = $rec;
	}
}
var_export ($d);
?>
