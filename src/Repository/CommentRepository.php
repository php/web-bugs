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

    /**
     * Find all log comments for documentation.
     * TODO: Check if this method is still used in the api.php endpoint.
     */
    public function findDocsComments(int $interval): array
    {
        $sql = "SELECT bugdb_comments.reporter_name, COUNT(*) as count
                FROM bugdb_comments, bugdb
                WHERE comment_type =  'log'
                    AND (package_name IN ('Doc Build problem', 'Documentation problem', 'Translation problem', 'Online Doc Editor problem') OR bug_type = 'Documentation Problem')
                    AND comment LIKE  '%+Status:      Closed</span>%'
                    AND date_sub(curdate(), INTERVAL ? DAY) <= ts
                    AND bugdb.id = bugdb_comments.bug
                GROUP BY bugdb_comments.reporter_name
                ORDER BY count DESC
        ";

        $statement = $this->dbh->prepare($sql);
        $statement->execute([$interval]);

        return $statement->fetchAll();
    }
}
