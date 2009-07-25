<?php

include './php-bugs-web/include/resolve.inc';

mysql_connect('localhost', 'nobody', '') or die('Unable to connect to SQL server.');
mysql_select_db('phpbugsdb') or die('Unable to select database.');
                                                
mysql_query("
	DELETE FROM bugdb_resolves
") or die (mysql_error());

foreach ($RESOLVE_REASONS as $key => $r)
{
	$key = mysql_escape_string($key);
	$status = mysql_escape_string($r['status']);
	$title = mysql_escape_string($r['desc']);
	$message = mysql_escape_string($r['message']);
	$webonly = isset($r['webonly']) ? (int) $r['webonly'] : 0;
	mysql_query("
		INSERT INTO bugdb_resolves
		SET name = '$key',
			status = '$status',
			title = '$title',
			message = '$message',
			webonly = $webonly,
			project = 'php'
	") or die (mysql_error());

	if (isset($FIX_VARIATIONS[$key]))
	{
		foreach ($FIX_VARIATIONS[$key] as $package_name => $message)
		{
			$package_name = mysql_escape_string($package_name);
			$message = mysql_escape_string($message);
			mysql_query("
				INSERT INTO bugdb_resolves
				SET name = '$key',
					status = '$status',
					title = '$title',
					message = '$message',
					webonly = $webonly,
					project = 'php',
					package_name = '$package_name'
				") or die (mysql_error());
		}
	}
}
