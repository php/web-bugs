<?php

use App\Autoloader;
use App\Template\Engine;

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

    $loader->addClassmap('Horde_Text_Diff', __DIR__.'/../src/Horde/Text/Diff.php');
    $loader->addClassmap('Horde_Text_Diff_Engine_Native', __DIR__.'/../src/Horde/Text/Diff/Engine/Native.php');
    $loader->addClassmap('Horde_Text_Diff_Op_Add', __DIR__.'/../src/Horde/Text/Diff/Op/Add.php');
    $loader->addClassmap('Horde_Text_Diff_Op_Base', __DIR__.'/../src/Horde/Text/Diff/Op/Base.php');
    $loader->addClassmap('Horde_Text_Diff_Op_Change', __DIR__.'/../src/Horde/Text/Diff/Op/Change.php');
    $loader->addClassmap('Horde_Text_Diff_Op_Copy', __DIR__.'/../src/Horde/Text/Diff/Op/Copy.php');
    $loader->addClassmap('Horde_Text_Diff_Op_Delete', __DIR__.'/../src/Horde/Text/Diff/Op/Delete.php');
    $loader->addClassmap('Horde_Text_Diff_Renderer', __DIR__.'/../src/Horde/Text/Diff/Renderer.php');
}

// Configuration
$site = 'php';
$siteBig = 'PHP';
$ROOT_DIR = realpath(__DIR__ . '/../');

$localConfigFile = __DIR__.'/../local_config.php';
if (file_exists($localConfigFile)) {
    require $localConfigFile;
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
        'patch_tmp' => __DIR__.'/../uploads/patches/',
    ];
    define('DEVBOX', false);
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

// Container initialization
$container = require_once __DIR__.'/../config/container.php';

// Configure errors based on the environment.
if ('dev' === $container->get('env')) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

// Obtain the functions and variables used throughout the bug system.
require_once __DIR__.'/functions.php';

// Database connection with vanilla PDO to understand app architecture in no time.
$dbh = $container->get(\PDO::class);

// Last updated.
$tmp = filectime($_SERVER['SCRIPT_FILENAME']);
$LAST_UPDATED = date('D M d H:i:s Y', $tmp - date('Z', $tmp)) . ' UTC';

// Initialize template engine.
$template = $container->get(Engine::class);
$template->assign([
    'lastUpdated' => $LAST_UPDATED,
    'siteScheme'  => $container->get('site_scheme'),
    'siteUrl'     => $container->get('site_url'),
]);
