<?php

namespace App\Fixtures;

use App\Database;
use App\Entity\Category;
use App\Entity\Package;
use Faker\Generator;

/**
 * Data fixtures for database. It uses Faker library for generating fixtures
 * data.
 */
class AppFixtures
{
    /**
     * Database handler.
     * @var \PDO
     */
    private $dbh;

    /**
     * Faker utility.
     * @var Generator
     */
    private $faker;

    /**
     * Bug statuses.
     * @var array
     */
    private $bugStatuses;

    /**
     * Number of generated users.
     */
    private const BUGS_COUNT = 1000;

    /**
     * Class constructor to set injected dependencies.
     */
    public function __construct(\PDO $dbh, Generator $faker, array $bugStatuses)
    {
        $this->dbh = $dbh;
        $this->faker = $faker;
        $this->bugStatuses = $bugStatuses;
    }

    /**
     * Insert data in the bugs categories table.
     */
    public function insertCategories()
    {
        $sql = "INSERT INTO bugdb_pseudo_packages (
                    `parent`,
                    `name`,
                    `long_name`,
                    `project`,
                    `list_email`,
                    `disabled`
                ) VALUES (
                    :parent,
                    :name,
                    :long_name,
                    :project,
                    :list_email,
                    :disabled
                )
        ";

        $catIds = [];

        foreach ($this->getCategories() as $category => $data) {
            $statement = $this->dbh->prepare($sql);
            $parent = isset($data['parent']) ? $catIds[$data['parent']] : 0;

            $statement->execute([
                ':parent' => $parent,
                ':name' => $category,
                ':long_name' => $data['long_name'] ?? $category,
                ':project' => $data['project'] ?? '',
                ':list_email' => $data['list_email'] ?? '',
                ':disabled' => $data['disabled'] ?? 0,
            ]);

            $catIds[$category] = $this->dbh->lastInsertId();
        }
    }

    /**
     * Insert fixtures in users table.
     */
    public function insertBugs()
    {
        $sql = "INSERT INTO bugdb (
                    `package_name`,
                    `bug_type`,
                    `email`,
                    `reporter_name`,
                    `sdesc`,
                    `ldesc`,
                    `php_version`,
                    `php_os`,
                    `status`,
                    `ts1`,
                    `ts2`,
                    `assign`,
                    `passwd`,
                    `registered`,
                    `block_user_comment`,
                    `cve_id`,
                    `private`,
                    `visitor_ip`
                ) VALUES (
                    :package_name,
                    :bug_type,
                    :email,
                    :reporter_name,
                    :sdesc,
                    :ldesc,
                    :php_version,
                    :php_os,
                    :status,
                    :ts1,
                    :ts2,
                    :assign,
                    :passwd,
                    :registered,
                    :block_user_comment,
                    :cve_id,
                    :private,
                    INET6_ATON(:visitor_ip)
                )
        ";

        $bugs = $this->getBugs();

        foreach ($bugs as $data) {
            $statement = $this->dbh->prepare($sql);

            $statement->execute([
                ':package_name' => $data['package_name'],
                ':bug_type' => $data['bug_type'],
                ':email' => $data['email'],
                ':reporter_name' => $data['reporter_name'],
                ':sdesc' => $data['sdesc'],
                ':ldesc' => $data['ldesc'],
                ':php_version' => $data['php_version'],
                ':php_os' => $data['php_os'],
                ':status' => $data['status'],
                ':ts1' => date('Y-m-d H:i:s'),
                ':ts2' => null,
                ':assign' => $data['assign'],
                ':passwd' => $data['passwd'],
                ':registered' => $data['registered'],
                ':block_user_comment' => $data['block_user_comment'],
                ':cve_id' => $data['cve_id'],
                ':private' => $data['private'],
                ':visitor_ip' => $data['visitor_ip'],
            ]);
        }
    }

    /**
     * Insert data in the bugs reasons table.
     */
    public function insertReasons()
    {
        $sql = "INSERT INTO bugdb_resolves (
                    `name`,
                    `status`,
                    `title`,
                    `message`,
                    `project`,
                    `package_name`,
                    `webonly`
                ) VALUES (
                    :name,
                    :status,
                    :title,
                    :message,
                    :project,
                    :package_name,
                    :webonly
                )
        ";

        foreach ($this->getReasons() as $data) {
            $statement = $this->dbh->prepare($sql);

            $statement->execute([
                ':name' => $data['name'],
                ':status' => $data['status'],
                ':title' => $data['title'],
                ':message' => $data['message'],
                ':project' => $data['project'],
                ':package_name' => $data['package_name'],
                ':webonly' => $data['webonly'],
            ]);
        }
    }

    /**
     * Get generated demo bug reports data with each bug having password set to
     * "password".
     */
    private function getBugs()
    {
        $bugs = [];

        // Demo secret password is always password for all bug reports
        $password = bugs_get_hash('password');

        $categories = $this->dbh->query("SELECT id, name FROM bugdb_pseudo_packages")->fetchAll(\PDO::FETCH_KEY_PAIR);

        // More random bug reports
        for ($i = 0; $i < self::BUGS_COUNT; $i++) {
            $username = $this->faker->unique()->userName;
            $username = substr($username, 0, 16);
            $username = str_replace('.', '', $username);

            $bugs[] = [
                'package_name' => $categories[array_rand($categories, 1)],
                'bug_type' => '',
                'email' => $username.'@example.com',
                'reporter_name' => $this->faker->firstName.' '.$this->faker->lastName,
                'sdesc' => $this->faker->text(80),
                'ldesc' => $this->faker->paragraph,
                'php_version' => '7.'.rand(0, 4).'.'.rand(0, 30),
                'php_os' => $this->faker->text(32),
                'status' => array_rand($this->bugStatuses),
                'ts1' => '',
                'ts2' => '',
                'assign' => '',
                'passwd' => $password,
                'registered' => 0,
                'block_user_comment' => 'N',
                'cve_id' => '',
                'private' => 'N',
                'visitor_ip' => $this->faker->ipv4,
            ];
        }

        return $bugs;
    }

    /**
     * Categories.
     */
    private function getCategories()
    {
        return [
            'General Issues' => [],
            'PDO related' => [],
            'Compile Issues' => [],
            'Configuration Issues' => [],
            'Web Server problem' => [],
            'Calendar problems' => [],
            'Compression related' => [],
            'Directory/Filesystem functions' => [],
            'Database Functions' => [],
            'Data Exchange functions' => [],
            'Extensibility Functions' => [],
            'Graphics related' => [],
            'Languages/Translation' => [],
            'Mail related' => [],
            'Encryption and hash functions' => [],
            'Network functions' => [],
            'PDF functions' => [],
            'Programming Data Structures' => [],
            'Regular expressions' => [],
            'Spelling functions' => [],
            'XML functions' => [],
            'Unicode Issues' => [],
            'Unknown/Other functions' => [],
            'PECL' => [],
            'phpdbg' => [],
            'PHP Language Specification' => [],

            // General Issues
            'Doc Build (PhD) problem' => [
                'parent' => 'General Issues',
            ],
            'Documentation problem' => [
                'parent' => 'General Issues',
            ],
            'Documentation translation problem' => [
                'parent' => 'General Issues',
            ],
            'Filter relate' => [
                'parent' => 'General Issues',
            ],
            'Online Documentation Editor problem' => [
                'parent' => 'General Issues',
            ],
            'Opcache' => [
                'parent' => 'General Issues',
            ],
            'Output Control' => [
                'parent' => 'General Issues',
            ],
            'Performance problem' => [
                'parent' => 'General Issues',
            ],
            'PHAR related' => [
                'parent' => 'General Issues',
            ],
            'PHP-GTK related' => [
                'parent' => 'General Issues',
            ],
            'Systems problem' => [
                'parent' => 'General Issues',
            ],
            'Website problem' => [
                'parent' => 'General Issues',
            ],
            'Reflection related' => [
                'parent' => 'General Issues',
            ],
            'Reproducible crash' => [
                'parent' => 'General Issues',
            ],
            'Scripting Engine problem' => [
                'parent' => 'General Issues',
            ],
            'Session related' => [
                'parent' => 'General Issues',
            ],
            'SPL related' => [
                'parent' => 'General Issues',
            ],
            'Streams related' => [
                'parent' => 'General Issues',
            ],
            'Testing related' => [
                'parent' => 'General Issues',
            ],

            // PDO related
            'PDO Core' => [
                'parent' => 'PDO related',
            ],
            'PDO DBlib' => [
                'parent' => 'PDO related',
            ],
            'PDO Firebird' => [
                'parent' => 'PDO related',
            ],
            'PDO MySQL' => [
                'parent' => 'PDO related',
            ],
            'PDO OCI' => [
                'parent' => 'PDO related',
            ],
            'PDO ODBC' => [
                'parent' => 'PDO related',
            ],
            'PDO PgSQL' => [
                'parent' => 'PDO related',
            ],
            'PDO SQLite' => [
                'parent' => 'PDO related',
            ],

            // Compile Issues
            'Compile Failure' => [
                'parent' => 'Compile Issues',
            ],
            'Compile Warning' => [
                'parent' => 'Compile Issues',
            ],

            // Configuration Issues
            'Dynamic loading' => [
                'parent' => 'Configuration Issues',
            ],
            'PHP options/info functions' => [
                'parent' => 'Configuration Issues',
            ],
            'Safe Mode/open_basedir related' => [
                'parent' => 'Configuration Issues',
            ],
            'Windows Installer related' => [
                'parent' => 'Configuration Issues',
            ],

            // TODO add more
        ];
    }

    private function getReasons(): array
    {
        return [
            [
                'name' => 'trysnapshot54',
                'status' => 'Feedback',
                'title' => 'Try a snapshot (PHP 5.4)',
                'message' => 'Please try using this snapshot:

                http://snaps.php.net/php5.4-latest.tar.gz

                For Windows:

                http://windows.php.net/snapshots/',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'trysnapshot55',
                'status' => 'Feedback',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'trysnapshottrunk',
                'status' => 'Feedback',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'fixed',
                'status' => 'Closed',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'fixed',
                'status' => 'Closed',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'fixed',
                'status' => 'Closed',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'fixed',
                'status' => 'Closed',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'fixed',
                'status' => 'Closed',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'fixed',
                'status' => 'Closed',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'fixed',
                'status' => 'Closed',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'alreadyfixed',
                'status' => 'Closed',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'needtrace',
                'status' => 'Feedback',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'needscript',
                'status' => 'Feedback',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'oldversion',
                'status' => 'Not a bug',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'support',
                'status' => 'Not a bug',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'nofeedback',
                'status' => 'No Feedback',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 1,
            ],
            [
                'name' => 'notwrong',
                'status' => 'Not a bug',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'notenoughinfo',
                'status' => 'Feedback',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'submittedtwice',
                'status' => 'Not a bug',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'globals',
                'status' => 'Not a bug',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'php4',
                'status' => 'Wont fix',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'dst',
                'status' => 'Not a bug',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'isapi',
                'status' => 'Not a bug',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'gnused',
                'status' => 'Not a bug',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'float',
                'status' => 'Not a bug',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'nozend',
                'status' => 'Not a bug',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
            [
                'name' => 'mysqlcfg',
                'status' => 'Not a bug',
                'title' => '',
                'message' => '',
                'project' => 'php',
                'package_name' => '',
                'webonly' => 0,
            ],
        ];
    }
}
