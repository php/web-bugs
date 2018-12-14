<?php

namespace App\Utils;

use App\Utils\Uploader;

/**
 * Service for handling uploaded patches.
 */
class PatchTracker
{
    /**
     * Database handler.
     * @var \PDO
     */
    private $dbh;

    /**
     * File upload service.
     * @var Uploader
     */
    private $uploader;

    /**
     * Parent directory where patches are uploaded.
     * @var string
     */
    private $uploadsDir;

    /**
     * Maximum allowed patch file size.
     */
    const MAX_FILE_SIZE = 100 * 1024;

    /**
     * Valid media types (former MIME types) for the uploaded patch files.
     */
    const VALID_MEDIA_TYPES = [
        'application/x-txt',
        'text/plain',
        'text/x-diff',
        'text/x-patch',
        'text/x-c++',
        'text/x-c',
        'text/x-m4',
    ];

    /**
     * Class constructor.
     */
    public function __construct(\PDO $dbh, Uploader $uploader)
    {
        $this->dbh = $dbh;
        $this->uploadsDir = BUG_PATCHTRACKER_TMPDIR;

        $this->uploader = $uploader;
        $this->uploader->setMaxFileSize(self::MAX_FILE_SIZE);
        $this->uploader->setValidMediaTypes(self::VALID_MEDIA_TYPES);
    }

    /**
     * Create a parent uploads directory for patches if it is missing.
     */
    private function createUploadsDir(): void
    {
        if (!file_exists($this->uploadsDir) && !@mkdir($this->uploadsDir)) {
            throw new \Exception('Patches upload directory could not be created.');
        }
    }

    /**
     * Get the directory in which patches for given bug id should be stored.
     */
    private function getPatchDir(int $bugId, string $name): string
    {
        return $this->uploadsDir.'/p'.$bugId.'/'.$name;
    }

    /**
     * Create the directory in which patches for the given bug id will be stored.
     */
    private function createPatchDir(int $bugId, string $name): void
    {
        $patchDir = $this->getPatchDir($bugId, $name);
        $parentDir = dirname($patchDir);

        // Check if patch directory already exists.
        if (is_dir($patchDir)) {
            return;
        }

        // Check if files with same names as directories already exist.
        if (is_file($parentDir) || is_file($patchDir)) {
            throw new \Exception('Cannot create patch storage for Bug #'.$bugId.', storage directory exists and is not a directory');
        }

        // Create parent directory
        if (!file_exists($parentDir) && !@mkdir($parentDir)) {
            throw new \Exception('Cannot create patch storage for Bug #'.$bugId);
        }

        // Create patch directory
        if (!@mkdir($patchDir)) {
            throw new \Exception('Cannot create patch storage for Bug #'.$bugId);
        }
    }

    /**
     * Retrieve a unique, ordered patch filename.
     */
    private function newPatchFileName(int $bugId, string $patch, string $developer): int
    {
        $revision = time();

        $sql = 'INSERT INTO bugdb_patchtracker
                (bugdb_id, patch, revision, developer) VALUES (?, ?, ?, ?)
        ';

        try {
            $this->dbh->prepare($sql)->execute([$bugId, $patch, $revision, $developer]);
        } catch (\Exception $e) {
            // Try with another timestamp
            try {
                $revision++;
                $this->dbh->prepare($sql)->execute([$bugId, $patch, $revision, $developer]);
            } catch (\Exception $e) {
                throw new \Exception('Could not get unique patch file name for bug #'.$bugId.', patch "'.$patch.'"');
            }
        }

        return $revision;
    }

    /**
     * Retrieve the name of the patch file on the system.
     */
    private function getPatchFileName(int $revision): string
    {
        return 'p'.$revision.'.patch.txt';
    }

    /**
     * Retrieve the full path to a patch file.
     */
    public function getPatchFullpath(int $bugId, string $name, int $revision): string
    {
        return $this->getPatchDir($bugId, $name).'/'.$this->getPatchFileName($revision);
    }

    /**
     * Attach a patch to this bug.
     */
    public function attach(int $bugId, string $patch, string $name, string $developer, array $obsoletes = []): int
    {
        $this->uploader->setDir($this->getPatchDir($bugId, $name));

        if (!is_array($obsoletes)) {
            throw new \Exception('Invalid obsoleted patches');
        }

        try {
            $revision = $this->newPatchFileName($bugId, $name, $developer);
            $this->uploader->setDestinationFileName($this->getPatchFileName($revision));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        try {
            $this->createUploadsDir();

            $this->validatePatchName($name);

            $this->createPatchDir($bugId, $name);

            $this->uploader->upload($patch);
        } catch (\Exception $e) {
            $this->detach($bugId, $name, $revision);

            throw new \Exception($e->getMessage());
        }

        $newObsoletes = [];
        foreach ($obsoletes as $obsoletePatch) {
            // The none option in form.
            if (!$obsoletePatch) {
                continue;
            }

            $obsoletePatch = explode('#', $obsoletePatch);

            if (count($obsoletePatch) != 2) {
                continue;
            }

            if (file_exists($this->getPatchFullpath($bugId, $obsoletePatch[0], $obsoletePatch[1]))) {
                $newObsoletes[] = $obsoletePatch;
            }
        }

        foreach ($newObsoletes as $obsolete) {
            $this->obsoletePatch($bugId, $name, $revision, $obsolete[0], $obsolete[1]);
        }

        return $revision;
    }

    /**
     * Validate patch name.
     */
    private function validatePatchName(string $name): void
    {
        if (!preg_match('/^[\w\-\.]+\z/', $name) || strlen($name) > 80) {
            throw new \Exception('Invalid patch name "'.htmlspecialchars($name, ENT_QUOTES).'"');
        }
    }

    /**
     * Remove a patch revision from this bug.
     */
    private function detach(int $bugId, string $name, int $revision): void
    {
        $sql = 'DELETE FROM bugdb_patchtracker
                WHERE bugdb_id = ? AND patch = ? AND revision = ?
        ';

        $this->dbh->prepare($sql)->execute([$bugId, $name, $revision]);

        @unlink($this->getPatchFullpath($bugId, $name, $revision));
    }

    /**
     * Make patch obsolete by new patch. This create a link to an obsolete patch
     * from the new one.
     */
    private function obsoletePatch(int $bugId, string $name, int $revision, string $obsoleteName, int $obsoleteRevision): void
    {
        $sql = 'INSERT INTO bugdb_obsoletes_patches VALUES (?, ?, ?, ?, ?)';

        $this->dbh->prepare($sql)->execute([$bugId, $name, $revision, $obsoleteName, $obsoleteRevision]);
    }
}
