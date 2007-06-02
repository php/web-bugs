<?php

include './bugtypes.inc';

mysql_connect('localhost', 'nobody', '') or die('Unable to connect to SQL server.');
mysql_select_db('phpbugsdb') or die('Unable to select database.');

$res = mysql_query("SELECT id from bugdb_packages");

$i = 0;

if ($res)
	while ($row = mysql_fetch_row($res)) $i++;

if ($i == 0)
{
	foreach (array_keys($items) as $id => $name)
	{
		if ($name == 'Any') continue;
	
		$name = mysql_escape_string($name);

		if ($name[0] == '*')
		{
			$sql = "INSERT INTO bugdb_packages SET id = '$id', name = '$name', parent = '0', project = 'php'";
			$parent = $id;
		} else {
			$sql = "INSERT INTO bugdb_packages SET id = '$id', name = '$name', parent = '$parent', project = 'php'";
		}
		mysql_query($sql);
	}
}
else
{
	echo "packages already populated!\n";
}
