<?php

// Enable output compression
ini_set('zlib.output_compression', 1);

// FIXME: Do not display errors, they are logged
ini_set('display_errors', 0);

$site = 'php';
$siteBig = 'PHP';
$ROOT_DIR = realpath(dirname(__FILE__) . '/../');

$local_cfg = "{$ROOT_DIR}/local_config.php";
if (file_exists($local_cfg)) {
	require $local_cfg;
} else {
	$site_data = array (
		'method' => 'https',
		'url' => 'bugs.php.net',
		'basedir' => '',
		'email' => 'php-bugs@lists.php.net',
		'doc_email' => 'doc-bugs@lists.php.net',
		'security_email' => 'security@php.net',
		'db_extension' => 'mysqli',
		'db' => 'phpbugsdb',
		'db_user' => 'nobody',
		'db_pass' => '',
		'db_host' => 'localhost',
		'patch_tmp' => "{$ROOT_DIR}/uploads/patches/",
	);
	define('DEVBOX', false);
}
// CONFIG END

if (!isset($site_data['security_email'])) {
	$site_data['security_email'] = 'security@php.net';
}

// DO NOT EDIT ANYTHING BELOW THIS LINE, edit $site_data above instead!
$logged_in = false;
$site_method = $site_data['method'];
$site_url = $site_data['url'];
$bugEmail = $site_data['email'];
$docBugEmail = $site_data['doc_email'];
$secBugEmail = $site_data['security_email'];
$basedir = $site_data['basedir'];
define('BUG_PATCHTRACKER_TMPDIR', $site_data['patch_tmp']);
define('DATABASE_DSN', "mysql:host={$site_data['db_host']};dbname={$site_data['db']};charset=utf8");

/**
 * Obtain the functions and variables used throughout the bug system
 */
require_once "{$ROOT_DIR}/include/functions.php";
require 'classes/bug_pdo.php';

// Database connection (required always?)
$dbh = new Bug_PDO(DATABASE_DSN, $site_data['db_user'], $site_data['db_pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// Last Updated..
$tmp = filectime($_SERVER['SCRIPT_FILENAME']);
$LAST_UPDATED = date('D M d H:i:s Y', $tmp - date('Z', $tmp)) . ' UTC';
