<?php

namespace App\Repository;

/**
 * Repository class for fetching data from the bugdb_votes database table.
 */
class VoteRepository
{
    /**
     * Database handle.
     * @var \PDO
     */
    private $dbh;

    /**
     * Class constructor
     */
    public function __construct(\PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    /**
     * Find vote row by bug id and IP.
     */
    public function findOneByIdAndIp(int $id, string $ip): array
    {
        $sql = 'SELECT bug, ip FROM bugdb_votes WHERE bug = ? AND ip = ? LIMIT 1';

        $statement = $this->dbh->prepare($sql);
        $statement->execute([$id, $ip]);

        $result = $statement->fetch();

        return $result === false ? [] : $result;
    }
}
