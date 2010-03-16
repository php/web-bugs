<?php

// Enable output compression
ini_set('zlib.output_compression', 1);

function getAllUsers()
{
	$opts = array('ignore_errors' => true);
	$ctx = stream_context_create(array('http' => $opts));
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

if (!$json = apc_fetch('svnusers')) {
	$json = getAllUsers();
	if ($json) {
		apc_store('svnusers', $json, 3600);
		apc_store('svnusers_update', $_SERVER['REQUEST_TIME'], 3600);
	}
}
$modified = apc_fetch('svnusers_update');

if (!$json) {
	return;
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

$lookup = $user = array();

foreach ($json as $row) {
	$lookup[] = $row['name'];
	$lookup[] = $row['username'];

	$data = array(
		'email'		=> md5($row['username'] . '@php.net'),
		'name'		=> $row['name'],
		'username'	=> $row['username'],
	);

	$user[$row['username']]	= $data;
	$user[$row['name']]		= $data;
}

echo 'var users = ', json_encode($user), ";\n",
	 'var lookup = ', json_encode($lookup), ";\n";
