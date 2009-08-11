<?php

include './php-bugs-web/bugtypes.inc';

mysql_connect('localhost', 'nobody', '') or die('Unable to connect to SQL server.');
mysql_select_db('phpbugsdb') or die('Unable to select database.');

foreach ($items as $key => $orig_name)
{
	if ($key == 'Any') continue;

	$key = mysql_escape_string($key);
	$name = mysql_escape_string(trim(str_replace('&nbsp;', '', $orig_name)));
	$list = '';
	
	switch ($key)
	{
		case 'Doc Build problem':
		case 'Documentation problem':
		case 'Livedocs problem':
		case 'Translation problem':
			$list = 'doc-bugs@lists.php.net';
			break;

		case 'Systems problem':
			$list = 'systems@php.net';
			break;
			
		case 'Website problem':
			$list = 'php-webmaster@lists.php.net';
			break;
	}

	$sql = "INSERT INTO bugdb_pseudo_packages SET name = '$key', long_name = '$name', project = 'php', list_email = '$list'";

	if ($key[0] == '*' || substr($orig_name, 0, 6) != '&nbsp;')
	{
		mysql_query($sql) or die(mysql_error());
		$parent = mysql_insert_id();
	} else {
		mysql_query("$sql, parent = '$parent'");
	}
}

/* Insert all possible old categories as disabled entries */
mysql_query("INSERT IGNORE INTO bugdb_pseudo_packages (name, long_name, parent, project, disabled)
	SELECT package_name as name, package_name as long_name, 0 AS parent, 'php' AS project, 1 AS disabled FROM bugdb GROUP BY package_name;
");
