<?php

/**
 * Performs the requires/includes needed for the entire bug system
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

// magic_quotes_gpc is no longer supported!
if (get_magic_quotes_gpc()) {
	die('Turn off "magic_quotes_gpc" in php.ini!');
}

// Copy config_local_sample.php to config_local.php and override settings.

require_once(dirname(__FILE__).'/config_local.php');

if (!defined('DEVBOX')) {
    define('DEVBOX', true);
}

if (!defined('PEAR_CHANNELNAME')) {
    define ('PEAR_CHANNELNAME', 'pear.php.net');
}

$logged_in = false;
$siteBig = strtoupper($site);
$site_url = $site_data[$site]['url'];
$bugEmail = $site_data[$site]['email'];
$basedir  = $site_data[$site]['basedir'];
$ROOT_DIR = realpath(dirname(__FILE__) . '/../');
define('BUG_PATCHTRACKER_TMPDIR', $site_data[$site]['patch_tmp']);
define('PEAR_DATABASE_DSN', "{$site_data[$site]['db_driver']}://{$site_data[$site]['db_user']}:{$site_data[$site]['db_pass']}@{$site_data[$site]['db_host']}/{$site_data[$site]['db']}");

// Database connection (required always?)
include_once 'MDB2.php';

if (empty($dbh))
{
    $dbh = MDB2::factory(PEAR_DATABASE_DSN);
    $dbh->loadModule('Extended');
}

// Last Updated..
$tmp = filectime($_SERVER['SCRIPT_FILENAME']);
$LAST_UPDATED = date('D M d H:i:s Y', $tmp - date('Z', $tmp)) . ' UTC';

/// FIXME: These should not be needed!
require "{$ROOT_DIR}/include/format-html.php"; // aka pear-format-html.php
require "{$ROOT_DIR}/include/classes/bug_dataobject.php";
$self = htmlspecialchars($_SERVER['PHP_SELF']);

extra_styles('css/bugs.css');

// Auth stuff only for PEAR/PECL sites for now
$SIDEBAR_DATA = '-';
if ($site != 'php') {
	include_once "{$ROOT_DIR}/include/auth.php";

	if (!empty($_GET['logout']) && $_GET['logout'] === '1') {
	    auth_logout($self);
	}

	if (!empty($_COOKIE['PEAR_USER']) && !auth_verify($_COOKIE['PEAR_USER'], $_COOKIE['PEAR_PW'])) {
	    $__user = $_COOKIE['PEAR_USER'];
	    setcookie('PEAR_USER', '', 0, '/');
	    unset($_COOKIE['PEAR_USER']);
	    setcookie('PEAR_PW', '', 0, '/');
	    unset($_COOKIE['PEAR_PW']);
	    $msg = "Invalid username ($__user) or password";
	    if ($format == 'html') {
	        $msg .= " <a href=\"/?logout=1\">[logout]</a>";
	    }
	    auth_reject(null, $msg);
	}
	$SIDEBAR_DATA = '';
}

/**
 * Obtain the functions and variables used throughout the bug system
 */
require_once "{$ROOT_DIR}/include/functions.inc";

