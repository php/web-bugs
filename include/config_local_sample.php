<?php
// This is a sample configuration file, copy to config_local.php and customize.

// Define which site settings to use.
$site = 'php';

// Specifics
$site_data = array (
    'php' => array (
        'url'            => 'bugs.php.net',
        'basedir'        => '/bugs',
        'email'          => 'php-bugs@lists.php.net',
        'security_email' => 'security@php.net',
        'db'             => 'phpbugsdb',
        'db_driver'      => 'mysqli',
        'db_user'        => 'nobody',
        'db_pass'        => '',
        'db_host'        => 'localhost',
        'patch_tmp'      => '/tmp/patches/', 
    ),
    'pear' => array (
        'url'            => 'pear.php.net',
        'basedir'        => '/bugs',
        'email'          => 'pear-bugs@lists.php.net',
        'security_email' => 'pear-group@php.net',
        'db'             => 'pear',
        'db_driver'      => 'mysqli',
        'db_user'        => 'nobody',
        'db_pass'        => '',
        'db_host'        => 'localhost',
        'patch_tmp'      => '/tmp/patches/', 
    ),
    'pecl' => array (
        'url'            => 'pecl.php.net',
        'basedir'        => '/bugs',
        'email'          => 'pecl-bugs@lists.php.net',
        'security_email' => 'security@php.net',
        'db'             => 'pear',
        'db_driver'      => 'mysqli',
        'db_user'        => 'nobody',
        'db_pass'        => '',
        'db_host'        => 'localhost',
        'patch_tmp'      => '/tmp/patches/', 
    ),
);

define('DEVBOX', true);
