<?php

require_once 'HTTP/Upload.php';

class Bug_Patchtracker
{
	var $_upload;
	var $_patchdir;
	var $_dbh;

	function __construct()
	{
		if (!file_exists(BUG_PATCHTRACKER_TMPDIR)) {
			if (!@mkdir(BUG_PATCHTRACKER_TMPDIR)) {
				$this->_upload = false;
				$this->_dbh = $GLOBALS['dbh'];
				return;
			}
		}
		$this->_upload = new HTTP_Upload('en');
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
		$e = $this->_dbh->prepare('INSERT INTO bugdb_patchtracker
			(bugdb_id, patch, revision, developer) VALUES(?, ?, ?, ?)')->execute(
			array($bugid, $patch, $id, $handle));
		if (PEAR::isError($e)) {
			// try with another timestamp
			$id++;
			$e = $this->_dbh->prepare('INSERT INTO bugdb_patchtracker
				(bugdb_id, patch, revision, developer) VALUES(?, ?, ?, ?)')->execute(
				array($bugid, $patch, $id, $handle));
		}
		PEAR::popErrorHandling();
		if (PEAR::isError($e)) {
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

	/**
	 * Attach a patch to this bug
	 *
	 * @param int $bugid
	 * @param string $patch		uploaded patch filename form variable
	 * @param string $name		patch name (allows several patches to be versioned)
	 * @param string $handle	developer handle
	 * @param array	$obsoletes	obsoleted patches
	 * @return int patch revision
	 */
	function attach($bugid, $patch, $name, $handle, $obsoletes)
	{
		if (!$this->_upload) {
			return PEAR::raiseError('Upload directory for patches could not be initialized');
		}
		if (!preg_match('/^[\w\-\.]+\z/', $name) || strlen($name) > 80) {
			return PEAR::raiseError("Invalid patch name \"".htmlspecichars($name)."\"");
		}
		if (!is_array($obsoletes)) {
			return PEAR::raiseError('Invalid obsoleted patches');
		}

		$file = $this->_upload->getFiles($patch);
		if (PEAR::isError($file)) {
			return $file;
		}
	
		if ($file->isValid()) {
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

			$res = $this->newPatchFileName($bugid, $name, $handle);
			if (PEAR::isError($res)) {
				return $res;
			}
			list($id, $fname) = $res;
			$file->setName($fname);
			$allowed_mime_types = array(
				'application/x-txt',
				'text/plain',
				'text/x-diff',
				'text/x-patch',
				'text/x-c++',
				'text/x-c',
				'text/x-m4',
			);

			// return mime type ala mimetype extension
			if (class_exists('finfo')) {
				$finfo = new finfo(FILEINFO_MIME);
				if (!$finfo) {
					return PEAR::raiseError('Error: Opening fileinfo database failed');
				}

				// get mime-type for a specific file
				$mime = $finfo->file($file->getProp('tmp_name'));
				// get rid of the charset part
				$t	= explode(';', $mime);
				$mime = $t[0];
			}
			else // NOTE: I didn't have PHP 5.3 around with fileinfo enabled :)
			{ 
				$mime = 'text/plain';
			}
			if (!in_array($mime, $allowed_mime_types)) {
				$this->_dbh->prepare('DELETE FROM bugdb_patchtracker
					WHERE bugdb_id = ? and patch = ? and revision = ?')->execute(
					array($bugid, $name, $id));
				return PEAR::raiseError('Error: uploaded patch file must be text'
					. ' file (save as e.g. "patch.txt" or "package.diff")'
					. ' (detected "' . htmlspecialchars($mime) . '")'
				);
			}
			$tmpfile = $file->moveTo($this->patchDir($bugid, $name));
			if (PEAR::isError($tmpfile)) {
				$this->_dbh->prepare('DELETE FROM bugdb_patchtracker
					WHERE bugdb_id = ? and patch = ? and revision = ?')->execute(
					array($bugid, $name, $id));
				return $tmpfile;
			}
			if (!$file->getProp('size')) {
				$this->detach($bugid, $name, $id);
				return PEAR::raiseError('zero-length patches not allowed');
			}
			if ($file->getProp('size') > 102400) {
				$this->detach($bugid, $name, $id);
				return PEAR::raiseError('Patch files cannot be larger than 100k');
			}
			foreach ($newobsoletes as $obsolete) {
				$this->obsoletePatch($bugid, $name, $id, $obsolete[0], $obsolete[1]);
			}
			return $id;
		} elseif ($file->isMissing()) {
			return PEAR::raiseError('Uploaded file is empty or nothing was uploaded.');
		} elseif ($file->isError()) {
			return PEAR::raiseError($file->errorMsg());
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
		$this->_dbh->prepare('DELETE FROM bugdb_patchtracker
			WHERE bugdb_id = ? and patch = ? and revision = ?')->execute(
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
		if ($this->_dbh->prepare('
			SELECT bugdb_id
			FROM bugdb_patchtracker
			WHERE bugdb_id = ? AND patch = ? AND revision = ?')->execute(array($bugid, $name, $revision))->fetchOne()
		) {
			$contents = @file_get_contents($this->getPatchFullpath($bugid, $name, $revision));
			if (!$contents) {
				return PEAR::raiseError('Cannot retrieve patch revision "' . $revision . '" for patch "' . $name . '"');
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
		$query = '
			SELECT patch, revision, developer
			FROM bugdb_patchtracker
			WHERE bugdb_id = ?
			ORDER BY revision DESC
		';

		return $this->_dbh->prepare($query)->execute(array($bugid))->fetchAll(MDB2_FETCHMODE_ORDERED, true, false, true);
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
		$query = '
			SELECT revision FROM bugdb_patchtracker
			WHERE bugdb_id = ? AND patch = ?
			ORDER BY revision DESC
		';
		return $this->_dbh->prepare($query)->execute(array($bugid, $patch))->fetchAll(MDB2_FETCHMODE_ORDERED);
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
			return $this->_dbh->prepare('
				SELECT developer
				FROM bugdb_patchtracker
				WHERE bugdb_id = ? AND patch = ? AND revision = ?
			')->execute(array($bugid, $patch, $revision))->fetchOne();
		}
		return $this->_dbh->prepare('
			SELECT developer, revision
			FROM bugdb_patchtracker
			WHERE bugdb_id = ? AND patch = ? ORDER BY revision DESC
		')->execute(array($bugid, $patch))->fetchAll(MDB2_FETCHMODE_ASSOC);
	}

	function getObsoletingPatches($bugid, $patch, $revision)
	{
		return $this->_dbh->prepare('
			SELECT bugdb_id, patch, revision
			FROM bugdb_obsoletes_patches
			WHERE	bugdb_id = ? AND obsolete_patch = ? AND obsolete_revision = ?
		')->execute(array($bugid, $patch, $revision))->fetchAll(MDB2_FETCHMODE_ASSOC);
	}

	function getObsoletePatches($bugid, $patch, $revision)
	{
		return $this->_dbh->prepare('
			SELECT bugdb_id, obsolete_patch, obsolete_revision
			FROM bugdb_obsoletes_patches
			WHERE bugdb_id = ? AND patch = ? AND revision = ?
		')->execute(array($bugid, $patch, $revision))->fetchAll(MDB2_FETCHMODE_ASSOC);
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
		$this->_dbh->prepare('
			INSERT INTO bugdb_obsoletes_patches
			VALUES(?, ?, ?, ?, ?)
		')->execute(array($bugid, $name, $revision, $obsoletename, $obsoleterevision));
	}
}
