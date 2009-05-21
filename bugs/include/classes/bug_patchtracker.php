<?php

class Bug_Patchtracker
{
    var $_init;
    var $_patchdir;
    var $_dbh;

    function __construct()
    {
        if (!file_exists(BUG_PATCHTRACKER_TMPDIR)) {
            if (!@mkdir(BUG_PATCHTRACKER_TMPDIR)) {
                $this->_init = false;
                $this->_dbh = &$GLOBALS['dbh'];
                return;
            }
        }
        $this->_init = true;
        $this->_dbh = $GLOBALS['dbh'];
    }

    /**
     * Return the directory in which patches should be stored
     *
     * @param int $bugid
     * @param string $name name of this patch line
     * @return string
     */
    function patchDir($bugid, $name)
    {
        return BUG_PATCHTRACKER_TMPDIR . '/p' . $bugid . '/' . $name;
    }
    /**
     * Create the directory in which patches for this bug ID will be stored
     *
     * @param int $bugid
     */
    function setupPatchDir($bugid, $name)
    {
        if (file_exists($this->patchDir($bugid, $name))) {
            if (!is_dir($this->patchDir($bugid, $name))) {
                return PEAR::raiseError('Cannot create patch storage for Bug #' . $bugid .
                    ', storage directory exists and is not a directory');
            }
            return;
        }
        if (!file_exists(dirname($this->patchDir($bugid, $name)))) {
            // setup bug directory
            if (!@mkdir(dirname($this->patchDir($bugid, $name)))) {
                require_once 'PEAR.php';
                return PEAR::raiseError('Cannot create patch storage for Bug #' . $bugid);
            }
        } elseif (!is_dir(dirname($this->patchDir($bugid, $name)))) {
            return PEAR::raiseError('Cannot create patch storage for Bug #' . $bugid .
                ', storage directory exists and is not a directory');
        }
        // setup patch directory
        if (!@mkdir($this->patchDir($bugid, $name))) {
            require_once 'PEAR.php';
            return PEAR::raiseError('Cannot create patch storage for Bug #' . $bugid);
        }
    }

    /**
     * Retrieve a unique, ordered patch filename
     *
     * @param int $bugid
     * @param string $patch
     * @return array array(revision, patch file name)
     */
    function newPatchFileName($bugid, $patch, $handle)
    {
        $id = time();
        PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
        $e = $this->_dbh->query('INSERT INTO bugdb_patchtracker
            (bugdb_id, patch, revision, developer) VALUES(?, ?, ?, ?)',
            array($bugid, $patch, $id, $handle));
        if (PEAR::isError($e)) {
            // try with another timestamp
            $id++;
            $e = $this->_dbh->query('INSERT INTO bugdb_patchtracker
                (bugdb_id, patch, revision, developer) VALUES(?, ?, ?, ?)',
                array($bugid, $patch, $id, $handle));
        }
        PEAR::popErrorHandling();
        if (PEAR::isError($e)) {
        var_dump($e);
            return PEAR::raiseError("Could not get unique patch file name for bug #{$bugid}, patch \"{$patch}\"");
        }
        return array($id, $this->getPatchFileName($id));
    }

    /**
     * Retrieve the name of the patch file on the system
     *
     * @param int $id revision ID
     * @return string
     */
    function getPatchFileName($id)
    {
        return 'p' . $id . '.patch.txt';
    }

    /**
     * Retrieve the full path to a patch file
     *
     * @param int $bugid
     * @param string $name
     * @param int $revision
     * @return string
     */
    function getPatchFullpath($bugid, $name, $revision)
    {
        return $this->patchDir($bugid, $name) .
            DIRECTORY_SEPARATOR . $this->getPatchFileName($revision);
    }

    function userNotRegistered($bugid, $name, $revision)
    {
        $user = $this->_dbh->getOne('SELECT registered from bugdb_patchtracker, users
            WHERE bugdb_id=? AND patch=? AND revision=?
                AND users.handle=bugdb_patchtracker.developer',
            array($bugid, $name, $revision));
        return !$user;
    }

    /**
     * Attach a patch to this bug
     *
     * @param int $bugid
     * @param string $patch uploaded patch filename form variable
     * @param string $name patch name (allows several patches to be versioned)
     * @param string $handle developer handle
     * @param array  $obsoletes obsoleted patches
     * @return int patch revision
     */
    function attach($bugid, $patch, $name, $handle, $obsoletes)
    {
        if (!$this->_init) {
            return PEAR::raiseError('Upload directory for patches could not be initialized');
        }
        if (!preg_match('/^[\w\-\.]+\z/', $name) || strlen($name) > 40) {
            return PEAR::raiseError('Invalid patch name "' . $name . '"');
        }
        if (!is_array($obsoletes)) {
            return PEAR::raiseError('Invalid obsoleted patches');
        }
        $newobsoletes = array();
        foreach ($obsoletes as $who) {
            if (!$who) {
                continue; // remove (none)
            }
            $who = explode('#', $who);
            if (count($who) != 2) {
                continue;
            }
            if (file_exists($this->getPatchFullpath($bugid, $who[0], $who[1]))) {
                $newobsoletes[] = $who;
            }
        }
        if (PEAR::isError($e = $this->setupPatchDir($bugid, $name))) {
            return $e;
        }

        if (isset($_FILES[$patch]) && !$_FILES[$patch]['error']) {
	        $file = $_FILES[$patch];

            $res = $this->newPatchFileName($bugid, $name, $handle);
            if (PEAR::isError($res)) {
                return $res;
            }
            list($id, $fname) = $res;

            $allowed_mime_types = array('application/x-txt',
                                        'text/plain',
                                        'text/x-diff',
                                        'text/x-patch',
                                        'text/x-c++',
                                       );


/* FIXME: This does not work. Some browsers do not send the file type at all! Should use fileinfo instead to check the type!!
            if (!in_array($file['type'], $allowed_mime_types)) {
                $this->_dbh->query('DELETE FROM bugdb_patchtracker
                    WHERE bugdb_id = ? and patch = ? and revision = ?',
                    array($bugid, $name, $id));
                return PEAR::raiseError('Error: uploaded patch file must be text file (save as e.g. "patch.txt" or "package.diff").');
            }
*/

            if (!move_uploaded_file($file['tmp_name'], $this->patchDir($bugid, $name) . "/{$fname}")) {
                $this->_dbh->query('DELETE FROM bugdb_patchtracker
                    WHERE bugdb_id = ? and patch = ? and revision = ?',
                    array($bugid, $name, $id));
                return PEAR::raiseError('could not move uploaded file to: ' . $this->patchDir($bugid, $name));
            }
            if (!$file['size']) {
                $this->detach($bugid, $name, $id);
                return PEAR::raiseError('zero-length patches not allowed');
            }
            if ($file['size'] > 20480) {
                $this->detach($bugid, $name, $id);
                return PEAR::raiseError('Patch files cannot be larger than 20k');
            }
            foreach ($newobsoletes as $obsolete) {
                $this->obsoletePatch($bugid, $name, $id, $obsolete[0], $obsolete[1]);
            }
            return $id;
        } elseif ($file['error']) {
        	switch ($file['error'])
        	{
               case UPLOAD_ERR_OK:
                 $msg = 'Upload ok';
                 break;
               case UPLOAD_ERR_INI_SIZE:
                 $msg = 'Uploaded file size exceeds the php.ini upload_max_filesize of ' . ini_get('upload_max_filesize') . ' bytes';
                 break;
               case UPLOAD_ERR_FORM_SIZE:
                 $msg = 'Uploaded file size exceeds the MAX_FILE_SIZE!';
                 break;
               case UPLOAD_ERR_PARTIAL:
                 $msg = 'Partial upload!';
                 break;
               case UPLOAD_ERR_NO_FILE:
                 $msg = 'No file uploaded!';
                 break;
               case UPLOAD_ERR_NO_TMP_DIR:
                 $msg = 'Temporary directory not found!';
                 break;
               case UPLOAD_ERR_CANT_WRITE:
                 $msg = 'Could not write file!';
                 break;
            }
            return PEAR::raiseError($msg);
        }
        return PEAR::raiseError('Unable to attach patch (try renaming the file with .txt extension)');
    }

    /**
     * Remove a patch revision from this bug
     *
     * @param int $bugid
     * @param string $name
     * @param int $revision
     */
    function detach($bugid, $name, $revision)
    {
        $this->_dbh->query('DELETE FROM bugdb_patchtracker
            WHERE bugdb_id = ? and patch = ? and revision = ?',
            array($bugid, $name, $revision));
        @unlink($this->patchDir($bugid, $name) . DIRECTORY_SEPARATOR .
            $this->getPatchFileName($revision));
    }

    /**
     * Retrieve the actual contents of the patch
     *
     * @param int $bugid
     * @param string $name
     * @param int $revision
     * @return string
     */
    function getPatch($bugid, $name, $revision)
    {
    	global $site;
    	
        if ($this->_dbh->getOne('SELECT bugdb_id FROM bugdb_patchtracker
              WHERE bugdb_id = ? AND patch = ? AND revision = ?',
              array($bugid, $name, $revision))) {
            if ($site != 'php' && !$this->_dbh->getOne('SELECT registered FROM users, bugdb_patchtracker
                WHERE bugdb_id=? AND patch=? AND revision=? AND
                users.handle=bugdb_patchtracker.developer', array($bugid, $name, $revision))) {
                // user is not registered
                throw new Exception('User who submitted this patch has not registered');
            }
            $contents = @file_get_contents($this->getPatchFullpath($bugid, $name, $revision));
            if (!$contents) {
                return PEAR::raiseError('Cannot retrieve patch revision "'. $revision . '" for patch "' . $name . '"');
            }
            return $contents;
        }
        return PEAR::raiseError('No such patch revision "' . $revision . '", or no such patch "' . $name . '"');
    }

    /**
     * Retrieve a listing of all patches and their revisions
     *
     * @param int $bugid
     * @return array
     */
    function listPatches($bugid)
    {
        return $this->_dbh->getAssoc(
            'SELECT patch, revision, developer
		     FROM bugdb_patchtracker
             WHERE bugdb_id = ? 
             ORDER BY revision DESC',
            false, array($bugid),
            DB_FETCHMODE_ORDERED, true
        );
    }

    /**
     * Retrieve a listing of all patches and their revisions
     *
     * @param int $bugid
     * @param string $patch
     * @return array
     */
    function listRevisions($bugid, $patch)
    {
        return $this->_dbh->getAll(
            'SELECT revision FROM bugdb_patchtracker, users
                WHERE bugdb_id = ? AND
             patch = ? AND users.handle=bugdb_patchtracker.developer AND
             users.registered=1
             ORDER BY revision DESC', array($bugid, $patch),
            DB_FETCHMODE_ORDERED
        );
    }

    /**
     * Retrieve the developer who uploaded this patch
     *
     * @param int $bugid
     * @param string $patch
     * @param int $revision
     * @return string|array array if no revision is selected
     */
    function getDeveloper($bugid, $patch, $revision = false)
    {
        if ($revision) {
            return $this->_dbh->getOne(
                'SELECT developer FROM bugdb_patchtracker
                 WHERE bugdb_id=? AND patch=? AND revision=?
                ', array($bugid, $patch, $revision));
        }
        return $this->_dbh->getAll(
            'SELECT developer,revision FROM bugdb_patchtracker
             WHERE bugdb_id=? AND patch=? ORDER BY revision DESC',
            array($bugid, $patch), DB_FETCHMODE_ASSOC
        );
    }

    function getObsoletingPatches($bugid, $patch, $revision)
    {
        return $this->_dbh->getAll('SELECT bugdb_id, patch, revision
            FROM bugdb_obsoletes_patches
                WHERE bugdb_id=? AND
                      obsolete_patch=? AND
                      obsolete_revision=?', array($bugid, $patch, $revision),
         DB_FETCHMODE_ASSOC);
    }

    function getObsoletePatches($bugid, $patch, $revision)
    {
        return $this->_dbh->getAll('SELECT bugdb_id, obsolete_patch, obsolete_revision
            FROM bugdb_obsoletes_patches
                WHERE bugdb_id=? AND
                      patch=? AND
                      revision=?', array($bugid, $patch, $revision),
         DB_FETCHMODE_ASSOC);
    }

    /**
     * link to an obsolete patch from the new one
     *
     * @param int $bugid
     * @param string $name better patch name
     * @param int $revision better patch revision
     * @param string $obsoletename
     * @param int $obsoleterevision
     */
    function obsoletePatch($bugid, $name, $revision, $obsoletename, $obsoleterevision)
    {
        $this->_dbh->query('INSERT INTO bugdb_obsoletes_patches
            VALUES(?,?,?,?,?)', array($bugid, $name, $revision, $obsoletename,
                                      $obsoleterevision));
    }

    /**
     * Retrieve information on a bug
     *
     * @param int $bugid
     * @return array
     */
    function getBugInfo($bugid)
    {
        $bugid = (int) $bugid;
        $info = $this->_dbh->getAll('SELECT * FROM bugdb WHERE id=?', array($bugid),
            DB_FETCHMODE_ASSOC);
        if (!is_array($info) || !count($info)) {
            return PEAR::raiseError('No such bug "' . $bugid . '"');
        }
        return $info[0];
    }
}
