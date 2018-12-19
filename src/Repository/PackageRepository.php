<?php

namespace App\Repository;

/**
 * Repository class for retrieving data from the bugdb_pseudo_packages database
 * table.
 */
class PackageRepository
{
    /**
     * Database handler.
     * @var \PDO
     */
    private $dbh;

    /**
     * Project types.
     */
    public const PROJECTS = [
        'PHP'  => 'php',
        'PECL' => 'pecl',
    ];

    /**
     * Class constructor.
     */
    public function __construct(\PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    /**
     * Find all packages by project type.
     */
    public function findAll(string $project = ''): array
    {
        $sql = 'SELECT * FROM bugdb_pseudo_packages';
        $arguments = [];

        $project = strtolower($project);
        if (in_array($project, self::PROJECTS)) {
            $sql .= " WHERE project IN ('', ?)";
            $arguments[] = $project;
        }

        $sql .= ' ORDER BY parent, disabled, id';

        $data = $this->dbh->prepare($sql)->execute($arguments)->fetchAll();

        return $this->getNested($data);
    }

    /**
     * Find all enabled packages by project type.
     */
    public function findEnabled(string $project = ''): array
    {
        $sql = 'SELECT * FROM bugdb_pseudo_packages WHERE disabled = 0';
        $arguments = [];

        $project = strtolower($project);
        if (in_array($project, self::PROJECTS)) {
            $sql .= " AND project IN ('', ?)";
            $arguments[] = $project;
        }

        $sql .= ' ORDER BY parent, id';

        $data = $this->dbh->prepare($sql)->execute($arguments)->fetchAll();

        return $this->getNested($data);
    }

    /**
     * Convert flat array to nested structure.
     */
    private function getNested(array $data): array
    {
        $packages = [];
        $nodes = [];
        $tree = [];

        foreach ($data as &$node) {
            $node['children'] = [];
            $id = $node['id'];
            $parentId = $node['parent'];
            $nodes[$id] =& $node;

            if (array_key_exists($parentId, $nodes)) {
                $nodes[$parentId]['children'][] =& $node;
            } else {
                $tree[] =& $node;
            }
        }

        foreach ($tree as $data) {
            if (isset($data['children'])) {
                $packages[$data['name']] = [$data['long_name'], $data['disabled'], []];
                $children = &$packages[$data['name']][2];
                $longNames = [];

                foreach ($data['children'] as $k => $v) {
                    $longNames[$k] = strtolower($v['long_name']);
                }

                array_multisort($longNames, SORT_ASC, SORT_STRING, $data['children']);

                foreach ($data['children'] as $child) {
                    $packages[$child['name']] = ["{$child['long_name']}", $child['disabled'], null];
                    $children[] = $child['name'];
                }
            } elseif (!isset($packages[$data['name']])) {
                $packages[$data['name']] = [$data['long_name'], $data['disabled'], null];
            }
        }

        return $packages;
    }

    /**
     * Find all package mailing lists.
     */
    public function findLists(): array
    {
        $sql = "SELECT name, list_email
                FROM bugdb_pseudo_packages
                WHERE project = 'php' AND LENGTH(list_email) > 0
                ORDER BY list_email
        ";

        $statement = $this->dbh->query($sql);

        return $statement->fetchAll();
    }
}
