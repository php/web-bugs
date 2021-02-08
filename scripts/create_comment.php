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

$res = $pdo->prepare("
        INSERT INTO bugdb_comments (bug, email, reporter_name, comment, comment_type, ts, visitor_ip)
        VALUES (?, ?, ?, ?, ?, NOW(), INET6_ATON(?))
    ");

$params = [
    $bug = 2,
    $email = 'dan_test@example.com',
    $reporter_name = 'John',
    $comment = 'The day shall not save them',
    $comment_type = 'unknown',
//    $ts,
    $visitor_ip = '127.0.0.1'
];

$result = $res->execute($params);

var_dump($result);

