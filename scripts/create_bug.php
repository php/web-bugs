<?php

declare(strict_types = 1);

$ROOT_DIR =  __DIR__ . '/../';
require_once __DIR__.'/../local_config.php';

$pdo = new \PDO(
    'mysql:host='.$site_data["db_host"].';dbname='.$site_data["db"].';charset=utf8',
    $site_data["db_user"],
    $site_data["db_pass"],
    [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

$res = $pdo->prepare('
                INSERT INTO bugdb (
                    package_name,
                    bug_type,
                    email,
                    sdesc,
                    ldesc,
                    php_version,
                    php_os,
                    passwd,
                    reporter_name,
                    status,
                    ts1,
                    private,
                    visitor_ip
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "Open", NOW(), ?, INET6_ATON(?))
            ');

$params = [
    $package_name = "Web Site",
    $bug_type = "Bug",
    $email = 'john@example.com',
    $sdesc = 'short desc',
    $ldesc = 'This is a long description',
    $php_version = '8.0.2',
    $php_os = '',
    $passwd = 'pass12345',
    $reporter_name = 'danack',
//    $status,
//    $ts1,
    $private = 'N',
    $visitor_ip = '127.0.0.1'
];

$result = $res->execute($params);

var_dump($result);

