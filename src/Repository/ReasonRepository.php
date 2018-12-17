<?php

namespace App\Repository;

/**
 * Repository class for fetching data from the bugdb_resolves database table.
 */
class ReasonRepository
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
     * Fetch bug resolves.
     */
    public function findByProject(string $project = ''): array
    {
        $sql = 'SELECT * FROM bugdb_resolves';
        $arguments = [];

        if ($project !== '') {
            $sql .= " WHERE (project = ? OR project = '')";
            $arguments[] = $project;
        }

        $resolves = $variations = [];
        $statement = $this->dbh->prepare($sql);
        $exec = $statement->execute($arguments);

        if (!$exec) {
            throw new \Exception('Error when fetching resolve reasons.');
        }

        while ($row = $statement->fetch()) {
            if (!empty($row['package_name'])) {
                $variations[$row['name']][$row['package_name']] = $row['message'];
            } else {
                $resolves[$row['name']] = $row;
            }
        }

        return [$resolves, $variations];
    }
}
