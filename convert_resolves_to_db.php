<?php

include './resolve.inc';

mysql_connect('localhost', 'nobody', '') or die('Unable to connect to SQL server.');
mysql_select_db('phpbugsdb') or die('Unable to select database.');
                                                
$res = mysql_query("SELECT id from bugdb_resolves");

$i = 0;

if ($res)
	while ($row = mysql_fetch_row($res)) $i++;

foreach ($RESOLVE_REASONS as $key => $r)
{
	$key = mysql_escape_string($key);
	$status = mysql_escape_string($r['status']);
	$title = mysql_escape_string($r['desc']);
	$message = mysql_escape_string($r['message']);
	mysql_query("
		INSERT INTO bugdb_resolves
		SET name = '$key',
			status = '$status',
			title = '$title',
			message = '$message',
			project = 'php'
	") or die (mysql_error());
}
