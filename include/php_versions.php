<?php
	$date = date('Y-m-d');
	$versions = array(
		'5.3.0',
		"5.3SVN-{$date} (snap)",
		"5.3SVN-{$date} (SVN)",
		'5.2.10',
		"5.2SVN-{$date} (snap)",
		"5.2SVN-{$date} (SVN)",
		"6SVN-{$date} (snap)",
		"6SVN-{$date} (SVN)",
	);

/*
	This needs a bit tuning to get latest releases of each active branch.

	$foo = unserialize(file_get_contents('http://www.php.net/releases/index.php?serialize=1&max=-1'));
	
	foreach ($foo as $f)
		echo $f['version'], "\n";

*/
