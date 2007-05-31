<?php

include './resolve.inc';

mysql_connect('localhost', 'nobody', '') or die('Unable to connect to SQL server.');
mysql_select_db('phpbugsdb') or die('Unable to select database.');
                                                
$res = mysql_query("SELECT id from bugdb_resolves");

$i = 0;

if ($res)
	while ($row = mysql_fetch_row($res)) $i++;

if ($i == 0)
{
	foreach ($RESOLVE_REASONS as $key => $r)
	{
		$status = mysql_escape_string($r['status']);
		$title = mysql_escape_string($r['desc']);
		$message = mysql_escape_string($r['message']);
		mysql_query("
			INSERT INTO bugdb_resolves
			SET status = '$status',
				title = '$title',
				message = '$message'
		");
		echo mysql_error();
	}
}
else
{
	echo "bugdb_resolves already populated!\n";
}
