<?php

namespace App\Repository;

/**
 * Repository for retrieving data from the bugdb_obsoletes_patches database table.
 */
class ObsoletePatchRepository
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
     * Retrieve patches that obsoleted given patch.
     */
    public function findObsoletingPatches(int $bugId, string $patch, int $revision): array
    {
        $sql = 'SELECT bugdb_id, patch, revision
                FROM bugdb_obsoletes_patches
                WHERE bugdb_id = ? AND obsolete_patch = ? AND obsolete_revision = ?
        ';

        return $this->dbh->prepare($sql)->execute([$bugId, $patch, $revision])->fetchAll();
    }

    /**
     * Retrieve obsolete patches by bug, patch and revision.
     */
    public function findObsoletePatches(int $bugId, string $patch, int $revision): array
    {
        $sql = 'SELECT bugdb_id, obsolete_patch, obsolete_revision
                FROM bugdb_obsoletes_patches
                WHERE bugdb_id = ? AND patch = ? AND revision = ?
        ';

        return $this->dbh->prepare($sql)->execute([$bugId, $patch, $revision])->fetchAll();
    }
}
