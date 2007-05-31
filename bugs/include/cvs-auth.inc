<?php

/**
 * Obtains users's CVS password
 *
 * This source file is subject to version 3.0 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * @category  pearweb
 * @package   Bugs
 * @copyright Copyright (c) 1997-2005 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License
 * @version   $Id$
 */

$ignore_password = false;
$passwd_file = "/repository/CVSROOT/passwd";

function find_password($user) {
	global $passwd_file, $ignore_password;
	if ($ignore_password) return " "; // can't be ""
	$fp=fopen($passwd_file,"r");
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
    global $auth_user;
    $logged = ($auth_user ? true : false);
    return $logged;
}

