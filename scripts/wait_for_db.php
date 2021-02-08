<?php

declare(strict_types = 1);
// ugh
$ROOT_DIR =  __DIR__ . '/../';
require_once __DIR__.'/../local_config.php';

$maxTimeToWait = 120;
$startTime = microtime(true);

do {
    try {
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

        $pdo->query('SELECT 1');
        echo "DB appears to be available.\n";
        exit(0);
    } catch (\Exception $e) {
        echo "DB not available yet.\n";
    }

    sleep(1);
} while ((microtime(true) - $startTime) < $maxTimeToWait);

echo "DB failed to be available in $maxTimeToWait seconds.\n";
exit(-1);
