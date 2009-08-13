<?php

/**
 * Contains functions to handle authentication using master.php.net
 */

function verify_password($user, $pass)
{
	global $errors;

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
		$errors[] = "Unknown authentication error\n";
		exit;
	}
	if (isset($a["errno"])) {
		$errors[] = "Authentication failed: {$a['errstr']}\n";
		return false;
	}

	return true;
}
