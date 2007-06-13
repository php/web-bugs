<?php

include './bugtypes.inc';

mysql_connect('localhost', 'nobody', '') or die('Unable to connect to SQL server.');
mysql_select_db('phpbugsdb') or die('Unable to select database.');

$res = mysql_query('SELECT id from bugdb_pseudo_packages');

$i = 0;

if ($res)
	while ($row = mysql_fetch_row($res)) $i++;

foreach ($items as $key => $name)
{
	if ($key == 'Any') continue;

	$key = mysql_escape_string($key);
	$name = mysql_escape_string(trim(str_replace('&nbsp;', '', $name)));

	$sql = "INSERT INTO bugdb_pseudo_packages SET name = '$key', long_name = '$name', project = 'php'";

	if ($key[0] == '*')
	{
		mysql_query($sql) or die(mysql_error());
		$parent = mysql_insert_id();
	} else {
		mysql_query("$sql, parent = '$parent'");
	}
	$i++;

}
