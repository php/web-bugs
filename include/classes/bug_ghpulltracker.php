<?php

class Bug_Pulltracker
{
	var $_dbh;
	private $userAgent = 'bugs.php.net Pulltracker';


	function __construct()
	{
		$this->_dbh = $GLOBALS['dbh'];
	}

	private function getDataFromGithub($repo, $pull_id)
	{
		$ctxt = stream_context_create(array(
			'http' => array(
				'ignore_errors' => '1',
				'user_agent' => $this->userAgent,
			)
		));
		$data = @json_decode(file_get_contents("https://api.github.com/repos/php/".urlencode($repo).'/pulls/'.((int)$pull_id), null, $ctxt));
		if (!is_object($data)) {
			return false;
		}
		return $data;
	}

	/**
	 * Attach a pull request to this bug
	 */
	function attach($bugid, $repo, $pull_id, $developer)
	{
		$data = $this->getDataFromGithub($repo, $pull_id);
		if (!$data) {
			return PEAR::raiseError('Failed to retrieve pull request from GitHub');
		}
		PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
		$e = $this->_dbh->prepare('INSERT INTO bugdb_pulls
			(bugdb_id, github_repo, github_pull_id, github_title, github_html_url, developer) VALUES (?, ?, ?, ?, ?, ?)')->execute(
				array($bugid, $repo, $pull_id, $data->title, $data->html_url, $developer));
		PEAR::popErrorHandling();
		if (PEAR::isError($e)) {
			return $e;
		}

		return $data;
	}

	/**
	 * Remove a pull request from this bug
	 */
	function detach($bugid, $repo, $pull_id)
	{
		$this->_dbh->prepare('DELETE FROM bugdb_pulls
			WHERE bugdb_id = ? and github_repo = ? and github_pull_id = ?')->execute(
			array($bugid, $repo, $pull_id));
	}

	/**
	 * Retrieve a listing of all pull requests
	 *
	 * @param int $bugid
	 * @return array
	 */
	function listPulls($bugid)
	{
		$query = '
			SELECT github_repo, github_pull_id, github_title, github_html_url, developer
			FROM bugdb_pulls
			WHERE bugdb_id = ?
			ORDER BY github_repo, github_pull_id DESC
		';

		return $this->_dbh->prepare($query)->execute(array($bugid))->fetchAll(MDB2_FETCHMODE_ASSOC);
	}
}
