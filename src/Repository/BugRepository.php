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
     * Days when bugs with no feedback get closed.
     */
    private const FEEDBACK_PERIOD = 7;

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

        $result = $statement->fetch();

        return $result === false ? [] : $result;
    }

    /**
     * Find random bug to resolve for a contributor.
     */
    public function findRandom(): array
    {
        $sql = "SELECT id
                FROM bugdb
                WHERE status NOT IN('Closed', 'Not a bug', 'Duplicate', 'Spam', 'Wont fix', 'No Feedback')
                    AND private = 'N'
                ORDER BY RAND() LIMIT 1
        ";

        $statement = $this->dbh->prepare($sql);
        $statement->execute();

        return $statement->fetch(\PDO::FETCH_NUM);
    }

    /**
     * Find all bugs that have someone assigned to them.
     */
    public function findAllAssigned(): array
    {
        $sql = "SELECT id, package_name, bug_type, sdesc, status, assign, UNIX_TIMESTAMP(ts1) AS ts_opened, UNIX_TIMESTAMP(ts2) AS ts_changed
                FROM `bugdb`
                WHERE length(assign) > 1
                    AND status IN ('Assigned', 'Open', 'Re-Opened', 'Feedback', 'Analyzed', 'Verified', 'Critical', 'Suspended')
                ORDER BY id
        ";

        $statement = $this->dbh->query($sql);

        $data = [];

        // Populate data with assign field as array key
        while ($row = $statement->fetch()) {
            $data[$row['assign']][] = $row;
        }

        return $data;
    }

    /**
     * Find all bugs without feedback by given period time.
     */
    public function findAllWithoutFeedback(int $feedbackPeriod = self::FEEDBACK_PERIOD): array
    {
        $sql = "SELECT id, package_name, bug_type, email, passwd, sdesc, ldesc,
                    php_version, php_os, status, ts1, ts2, assign,
                    UNIX_TIMESTAMP(ts1) AS submitted, private, reporter_name,
                    UNIX_TIMESTAMP(ts2) AS modified
                FROM bugdb
                WHERE status = 'Feedback' AND ts2 < DATE_SUB(NOW(), INTERVAL ? DAY)
        ";

        $statement = $this->dbh->prepare($sql);
        $statement->execute([$feedbackPeriod]);

        return $statement->fetchAll();
    }

    /**
     * Find all bugs by given bug type.
     */
    public function findAllByBugType(string $type = 'All'): array
    {
        $sql = 'SELECT b.package_name, b.status, COUNT(*) AS quant FROM bugdb AS b';

        $arguments = [];

        if ($type !== 'All') {
            $sql .= ' WHERE bug_type = ? ';
            $arguments[] = $type;
        }

        $sql .= ' GROUP BY b.package_name, b.status ORDER BY b.package_name, b.status';

        $statement = $this->dbh->prepare($sql);
        $statement->execute($arguments);

        return $statement->fetchAll();
    }

    /**
     * Find bugs for grouping into PHP versions by given bug type.
     */
    public function findPhpVersions(string $type = 'All'): array
    {
        $sql = "SELECT DATE_FORMAT(ts1, '%Y-%m') as d,
                    IF(b.php_version LIKE '%Git%', LEFT(b.php_version, LOCATE('Git', b.php_version)+2), b.php_version) AS formatted_version,
                    COUNT(*) AS quant
                FROM bugdb AS b
                WHERE ts1 >= CONCAT(YEAR(NOW())-1, '-', MONTH(NOW()), '-01 00:00:00')
        ";

        $arguments = [];

        if ($type !== 'All') {
            $sql .= ' AND bug_type = ? ';
            $arguments[] = $type;
        }

        $sql .= ' GROUP BY d, formatted_version ORDER BY d, quant';

        $statement = $this->dbh->prepare($sql);
        $statement->execute($arguments);

        return $statement->fetchAll();
    }

    /**
     * Check if bug with given id exists.
     */
    public function exists(int $id): bool
    {
        $statement = $this->dbh->prepare('SELECT 1 FROM bugdb WHERE id = ?');
        $statement->execute([$id]);

        return (bool)$statement->fetchColumn();
    }
}
