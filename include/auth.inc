<?php
/* $Id$ */

function verify_password($user, $pass)
{
	$post = http_build_query(
		array(
			"token" => getenv("TOKEN"),
			"username" => $user,
			"password" => $pass,
		)
	);

	$opts = array(
		"method"  => "POST",
		"header"  => "Content-type: application/x-www-form-urlencoded",
		"content" => $post,
	);

	$ctx = stream_context_create(array("http" => $opts));

	$s = file_get_contents("https://master.php.net/fetch/cvsauth.php", false, $ctx);

	$a = @unserialize($s);
	if (!is_array($a)) {
		echo "Unknown authentication error<br />\nMaybe master is down?";
		exit;
	}
	if (isset($a["errno"])) {
		return false;
	}

	return true;
}

function is_developer()
{
	if (empty($_COOKIE['MAGIC_COOKIE'])) {
		return false;
	}
	
	// 0 = username, 1 = password
	$c = explode(':', base64_decode($_COOKIE['MAGIC_COOKIE']), 2);

	if (!$c[0] || !$c[1]) {
		// The "remember me" for bug reports (non-devs) feature uses MAGIC_COOKIE without username
		return false;
	}
	
	return verify_password($c[0], $c[1]);
}

