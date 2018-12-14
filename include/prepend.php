<?php

use App\Autoloader;
use App\Database\Statement;

// Dual PSR-4 compatible class autoloader. When Composer is not available, an
// application specific replacement class is used. Once Composer can be added
// to the deployment step with rsync this can be simplified and only Composer's
// autoload.php will be used.
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
} else {
    require_once __DIR__.'/../src/Autoloader.php';

    $loader = new Autoloader();
    $loader->addNamespace('App\\', __DIR__.'/../src/');
}

$site = 'php';
$siteBig = 'PHP';
$ROOT_DIR = realpath(__DIR__ . '/../');

$local_cfg = "{$ROOT_DIR}/local_config.php";
if (file_exists($local_cfg)) {
	require $local_cfg;
} else {
	$site_data = [
		'method' => 'https',
		'url' => 'bugs.php.net',
		'basedir' => '',
		'email' => 'php-bugs@lists.php.net',
		'doc_email' => 'doc-bugs@lists.php.net',
		'security_email' => 'security@php.net',
		'db' => 'phpbugsdb',
		'db_user' => 'nobody',
		'db_pass' => '',
		'db_host' => 'localhost',
		'patch_tmp' => "{$ROOT_DIR}/uploads/patches/",
	];
	define('DEVBOX', false);
}
// CONFIG END

// Configure errors based on the environment.
if (defined('DEVBOX') && true === DEVBOX) {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}

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

/**
 * Obtain the functions and variables used throughout the bug system
 */
require_once "{$ROOT_DIR}/include/functions.php";

// Database connection with vanilla PDO to understand app architecture in no time
$dbh = new \PDO(
    'mysql:host='.$site_data['db_host'].';dbname='.$site_data['db'].';charset=utf8',
    $site_data['db_user'],
    $site_data['db_pass'],
    [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
        \PDO::ATTR_STATEMENT_CLASS    => [Statement::class],
    ]
);

// Last Updated..
$tmp = filectime($_SERVER['SCRIPT_FILENAME']);
$LAST_UPDATED = date('D M d H:i:s Y', $tmp - date('Z', $tmp)) . ' UTC';
