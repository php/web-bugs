<?php

class Bug_Pulltracker
{
	private $dbh;
	private $userAgent = 'bugs.php.net Pulltracker';

	public function __construct()
	{
		$this->dbh = $GLOBALS['dbh'];
	}

	private function getDataFromGithub($repo, $pull_id)
	{
		$ctxt = stream_context_create([
			'http' => [
				'ignore_errors' => '1',
				'user_agent' => $this->userAgent,
			]
		]);
		$data = @json_decode(file_get_contents("https://api.github.com/repos/php/".urlencode($repo).'/pulls/'.((int)$pull_id), null, $ctxt));
		if (!is_object($data)) {
			return false;
		}
		return $data;
	}

	/**
	 * Attach a pull request to this bug
	 */
	public function attach($bugid, $repo, $pull_id, $developer)
	{
		$data = $this->getDataFromGithub($repo, $pull_id);

		if (!$data) {
			throw new \Exception('Failed to retrieve pull request from GitHub');
		}

		$sql = 'INSERT INTO bugdb_pulls
				(bugdb_id, github_repo, github_pull_id, github_title, github_html_url, developer)
				VALUES (?, ?, ?, ?, ?, ?)
		';

		$arguments = [
			$bugid,
			$repo,
			$pull_id,
			$data->title,
			$data->html_url,
			$developer,
		];

		$this->dbh->prepare($sql)->execute($arguments);

		return $data;
	}

	/**
	 * Remove a pull request from this bug
	 */
	public function detach($bugid, $repo, $pull_id)
	{
		$this->dbh->prepare('DELETE FROM bugdb_pulls
			WHERE bugdb_id = ? and github_repo = ? and github_pull_id = ?')->execute(
			[$bugid, $repo, $pull_id]);
	}

	/**
	 * Retrieve a listing of all pull requests
	 *
	 * @param int $bugid
	 * @return array
	 */
	public function listPulls($bugid)
	{
		$query = '
			SELECT github_repo, github_pull_id, github_title, github_html_url, developer
			FROM bugdb_pulls
			WHERE bugdb_id = ?
			ORDER BY github_repo, github_pull_id DESC
		';

		return $this->dbh->prepare($query)->execute([$bugid])->fetchAll(PDO::FETCH_ASSOC);
	}
}
