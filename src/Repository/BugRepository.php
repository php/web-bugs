<?php

namespace App\Repository;

/**
 * Repository class for fetching data from the database table bugdb.
 */
class BugRepository
{
    /**
     * Database handler.
     * @var \PDO
     */
    private $dbh;

    /**
     * Class constructor.
     */
    public function __construct(\PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    /**
     * Fetch bug data by bug id.
     */
    public function findOneById(int $id): array
    {
        $sql = 'SELECT b.id, b.package_name, b.bug_type, b.email, b.reporter_name,
                    b.sdesc, b.ldesc, b.php_version, b.php_os,
                    b.status, b.ts1, b.ts2, b.assign, b.block_user_comment,
                    b.private, b.cve_id,
                    UNIX_TIMESTAMP(b.ts1) AS submitted,
                    UNIX_TIMESTAMP(b.ts2) AS modified,
                    COUNT(bug=b.id) AS votes,
                    IFNULL((SELECT z.project FROM bugdb_pseudo_packages z WHERE z.name = b.package_name LIMIT 1), "php") project,
                    SUM(reproduced) AS reproduced, SUM(tried) AS tried,
                    SUM(sameos) AS sameos, SUM(samever) AS samever,
                    AVG(score)+3 AS average, STD(score) AS deviation
                FROM bugdb b
                LEFT JOIN bugdb_votes ON b.id = bug
                WHERE b.id = ?
                GROUP BY bug
        ';

        $statement = $this->dbh->prepare($sql);
        $statement->execute([$id]);

        return $statement->fetch();
    }
}
