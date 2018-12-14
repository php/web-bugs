<?php

namespace App\Repository;

/**
 * Repository class for retrieving data from the bugdb_pulls database table.
 */
class PullRequestRepository
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
     * Retrieve all pull requests by bug id.
     *
     * @param int $bugId
     * @return array
     */
    public function findAllByBugId(int $bugId)
    {
        $sql = 'SELECT github_repo, github_pull_id, github_title, github_html_url, developer
                FROM bugdb_pulls
                WHERE bugdb_id = ?
                ORDER BY github_repo, github_pull_id DESC
        ';

        return $this->dbh->prepare($sql)->execute([$bugId])->fetchAll();
    }
}
