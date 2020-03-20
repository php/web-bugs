<?php

namespace App\Utils\Versions;

/**
 * API client for sending requests to PHP API pages.
 */
class Client
{
    /**
     * API URL for fetching development PHP versions.
     *
     * @var string
     */
    private $devVersionsUrl = 'https://qa.php.net/api.php?type=qa-releases&format=json&only=dev_versions';

    /**
     * API URL for fetching active PHP versions.
     *
     * @var string
     */
    private $stableVersionsUrl = 'https://php.net/releases/active.php';

    /**
     * Fetches data from remote URL.
     */
    public function fetchDevVersions(): array
    {
        $json = file_get_contents($this->devVersionsUrl);

        return json_decode($json, true) ?? [];
    }

    /**
     * Fetch stable versions from remote URL.
     */
    public function fetchStableVersions(): array
    {
        $json = file_get_contents($this->stableVersionsUrl);

        return json_decode($json, true) ?? [];
    }
}
