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

if ($json) {
	foreach ($json as $row) {
		$lookup[] = $row['name'];		
	
		$data = array(
			'email'		=> md5($row['username'] . '@php.net'),
			'name'		=> $row['name'],
			'username'	=> $row['username'],
		);
		
		list($first_name,) = explode(' ', $row['name'], 2);
		$first_name = substr($first_name, 0, strlen($row['username']));
	
		/* Only add the user name in the array if it is not the same as the begin of the real name */
		if (!preg_match('/^'. preg_quote($first_name, '/') .'/i', $row['username'])) {
			$lookup[] = $row['username'];
			$user[$row['username']]	= $data;
		}
		$user[$row['name']] = $data;
	}
}

echo 'var users = ', json_encode($user), ";\n",
	 'var lookup = ', json_encode($lookup), ";\n";
