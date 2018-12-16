<?php

namespace App\Repository;

/**
 * Repository class for fetching data from the bugdb_comments table.
 */
class CommentRepository
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
     * Fetch bug comments
     */
    public function findByBugId(int $id): array
    {
        $sql = 'SELECT c.id, c.email, c.comment, c.comment_type,
                    UNIX_TIMESTAMP(c.ts) AS added,
                    c.reporter_name AS comment_name
                FROM bugdb_comments c
                WHERE c.bug = ?
                GROUP BY c.id ORDER BY c.ts
        ';

        $statement = $this->dbh->prepare($sql);
        $statement->execute([$id]);

        return $statement->fetchAll();
    }
}
