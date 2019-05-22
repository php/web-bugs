<?php

namespace App\Repository;

/**
 * Repository class for fetching database status data presented under /admin
 */
class DatabaseStatusRepository
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

    public function getMysqlVersion(): string
    {
        return $this->dbh->query('SELECT version() mysql_version')->fetchColumn(0);
    }

    /**
     * @return string[]
     */
    public function findAllTables(): array
    {
        return $this->dbh->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return array<int,string>
     */
    public function getNumberOfRowsInTables(): array
    {
        $numberOfRowsPerTable = [];

        foreach ($this->findAllTables() as $tableName) {
            $sql = sprintf('SELECT COUNT(*) FROM `%s`', $tableName);

            $numberOfRowsPerTable[$tableName] = $this->dbh->query($sql)->fetchColumn(0);
        }

        return $numberOfRowsPerTable;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getStatusOfTables(): array
    {
        return $this->dbh->query('SHOW TABLE STATUS')->fetchAll();
    }
}
