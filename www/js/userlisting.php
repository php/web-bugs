<?php

// Enable output compression
ini_set('zlib.output_compression', 1);

function getAllUsers()
{
	$opts = ['ignore_errors' => true];
	$ctx = stream_context_create(['http' => $opts]);
	$token = getenv('USER_TOKEN');

	$retval = @file_get_contents('https://master.php.net/fetch/allusers.php?&token=' . rawurlencode($token), false, $ctx);

	if (!$retval) {
		return;
	}

	$json = json_decode($retval, true);

	if (!is_array($json)) {
		return;
	}

	if (isset($json['error'])) {
		return;
	}
	return $json;
}

if (!file_exists("/tmp/svnusers.json") || filemtime("/tmp/svnusers.json") < $_SERVER["REQUEST_TIME"] - 3600) {
	$json = getAllUsers();
	$json_data = var_export($json, true);
	file_put_contents("/tmp/svnusers.php", '<?php $json = '.$json_data.';');
	$modified = time();
} else {
	include "/tmp/svnusers.php";
	$modified = filemtime("/tmp/svnusers.php");
}

$tsstring = gmdate('D, d M Y H:i:s ', $modified);
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $tsstring) {
	header('HTTP/1.1 304 Not Modified');
	exit;
} else {
	$expires = gmdate('D, d M Y H:i:s ', strtotime('+2 months', $_SERVER['REQUEST_TIME'])) . 'GMT';
	header("Last-Modified: {$tsstring}");
	header("Expires: {$expires}");
}

$lookup = $user = [];

if ($json) {
	foreach ($json as $row) {
		$lookup[] = $row['name'];
		$lookup[] = $row["username"];

		$data = [
			'email'		=> md5($row['username'] . '@php.net'),
			'name'		=> $row['name'],
			'username'	=> $row['username'],
		];
		$user[$row["username"]] = $data;
		$user[$row["name"]]     = $data;
	}
}

echo 'var users = ', json_encode($user), ";\n",
	 'var lookup = ', json_encode($lookup), ";\n";
