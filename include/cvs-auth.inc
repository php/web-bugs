<?php
/* $Id$ */

$ignore_password = false;
$passwd_file = "/repository/CVSROOT/passwd";

function find_password($user) {
	global $passwd_file, $ignore_password;
	if ($ignore_password) return " "; // can't be ""
	$fp=fopen($passwd_file,"r");
	if (!$fp) {
		return ("");
	}
	while(!feof($fp)) {
		$line=fgets($fp,120);
		list($luser,$passwd,$junk) = explode(":",$line);
		if($user==$luser) {
			fclose($fp);
			return($passwd);
		}
	}
	fclose($fp);
	return("");
}

function verify_password($user, $pass) {
	global $ignore_password;
	$psw = find_password($user);
	if (strlen($psw) > 0) {
		if ($ignore_password || crypt($pass,substr($psw,0,2)) == $psw) {
			return true;
		}
	}
	return false;
}

function is_cvs_user () {

	if (empty($_COOKIE['MAGIC_COOKIE'])) {
		return false;
	}
	
	// 0 = username, 1 = password
	$c = explode(':', base64_decode($_COOKIE['MAGIC_COOKIE']), 2);
	
	if (!verify_password($c[0], $c[1])) {
		return false;
	}

	return true;
}
