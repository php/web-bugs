<?php

namespace App\Utils;

/**
 * GitHub pull requests tracker client.
 */
class GitHub
{
    /**
     * Database handler.
     * @var \PDO
     */
    private $dbh;

    /**
     * API URL.
     */
    private $url = 'https://api.github.com';

    /**
     * User agent string when establishing stream context with remote GitHub URL.
     */
    private $userAgent = 'bugs.php.net Pulltracker';

    /**
     * Username or organization name on GitHub.
     */
    private $organization = 'php';

    /**
     * Class constructor
     */
    public function __construct(\PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    /**
     * Retrieve data from remote GitHub URL.
     */
    private function getDataFromGithub(string $repo, int $pullId)
    {
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => '1',
                'user_agent' => $this->userAgent,
            ]
        ]);

        $url = $this->url.'/repos/'.$this->organization.'/'.urlencode($repo).'/pulls/'.$pullId;
        $data = @json_decode(file_get_contents($url, null, $context));

        if (!is_object($data)) {
            return false;
        }

        return $data;
    }

    /**
     * Attach a pull request to bug.
     */
    public function attach($bugId, $repo, $pullId, $developer)
    {
        $data = $this->getDataFromGithub($repo, (int)$pullId);

        if (!$data) {
            throw new \Exception('Failed to retrieve pull request from GitHub');
        }

        $sql = 'INSERT INTO bugdb_pulls
                (bugdb_id, github_repo, github_pull_id, github_title, github_html_url, developer)
                VALUES (?, ?, ?, ?, ?, ?)
        ';

        $arguments = [
            $bugId,
            $repo,
            $pullId,
            $data->title,
            $data->html_url,
            $developer,
        ];

        $this->dbh->prepare($sql)->execute($arguments);

        return $data;
    }

    /**
     * Remove a pull request from given bug.
     */
    public function detach(int $bugId, string $repo, int $pullId)
    {
        $sql = 'DELETE FROM bugdb_pulls
                WHERE bugdb_id = ? AND github_repo = ? AND github_pull_id = ?
        ';

        $this->dbh->prepare($sql)->execute([$bugId, $repo, $pullId]);
    }
}
