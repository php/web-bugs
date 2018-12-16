<?php

namespace App\Utils\Versions;

use App\Utils\Cache;

/**
 * Service for retrieving a list of valid PHP versions when reporting bugs. PHP
 * versions have format MAJOR.MINOR.MICRO{TYPE} where TYPE is one of alpha,
 * beta, RC, or dev.
 *
 * Stable releases are pulled from the https://php.net. The RC and dev versions
 * are pulled from the https://qa.php.net.
 *
 * To add a new PHP version add it to:
 * https://git.php.net/?p=web/qa.git;a=blob;f=include/release-qa.php
 *
 * The versions are weighted by the following criteria:
 * - major+minor version desc (7>5.4>5.3>master)
 * - Between minor versions ordering is done by the micro version if available.
 *   First the QA releases: alpha/beta/rc, then stable, then nightly versions
 *   (Git, snaps). Snaps are more or less Windows snapshot builds.
 *
 * The result is cached for 1 hour into a temporary file.
 */
class Generator
{
    /**
     * PHP API pages client.
     *
     * @var Client
     */
    private $client;

    /**
     * Cache service for storing fetched versions.
     *
     * @var Cache
     */
    private $cache;

    /**
     * Time after cache file is considered expired in seconds.
     */
    private const TTL = 3600;

    /**
     * Additional versions appended to the list of generated versions.
     */
    private const APPENDICES = [
        'Next Major Version',
        'Next Minor Version',
        'Irrelevant',
    ];

    /**
     * Class constructor.
     */
    public function __construct(Client $client, Cache $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }

    /**
     * Get a list of valid PHP versions. Versions are cached for efficiency.
     */
    public function getVersions(): array
    {
        if (!$this->cache->has('versions')) {
            $this->cache->set('versions', $this->generateVersions(), self::TTL);
        }

        return $this->cache->get('versions');
    }

    /**
     * Return fetched and processed versions.
     */
    private function generateVersions(): array
    {
        $versions = array_merge($this->getDevVersions(), $this->getStableVersions());
        rsort($versions);

        // Get minor branches (PHP 7.2, PHP 7.3, etc)
        $branches = [];
        foreach ($versions as $version) {
            $parts = $this->parseVersion($version);
            $branch = $parts['major'].'.'.$parts['minor'];
            $branches[$branch] = $branch;
        }

        $sorted = [];

        // Add versions grouped by branches
        foreach ($branches as $branch) {
            foreach ($versions as $version) {
                $parts = $this->parseVersion($version);
                if ($parts['major'].'.'.$parts['minor'] === $branch) {
                    $sorted[] = $version;
                }
            }

            // Append Git and snaps for each branch
            foreach ($this->getAffixes() as $item) {
                $sorted[] = $branch.$item;
            }
        }

        // Append master branch to the versions list
        foreach ($this->getAffixes() as $item) {
            $sorted[] = 'master-'.$item;
        }

        // Append human readable versions
        $sorted = array_merge($sorted, self::APPENDICES);

        return $sorted;
    }

    /**
     * Get version affixes such as Git or snapshots.
     */
    protected function getAffixes(): array
    {
        $date = date('Y-m-d');

        return [
            'Git-'.$date.' (Git)',
            'Git-'.$date.' (snap)',
        ];
    }

    /**
     * Get alpha, beta and RC versions.
     */
    private function getDevVersions(): array
    {
        $versions = [];

        foreach ($this->client->fetchDevVersions() as $version) {
            $parts = $this->parseVersion($version);
            if ('dev' !== $parts['type']) {
                $versions[] = $version;
            }
        }

        return $versions;
    }

    /**
     * Get stable versions.
     */
    private function getStableVersions(): array
    {
        $versions = [];

        foreach ($this->client->fetchStableVersions() as $releases) {
            foreach ($releases as $release) {
                $versions[] = $release['version'];
            }
        }

        return $versions;
    }

    /**
     * Parse the versions data string and convert it to array.
     */
    private function parseVersion(string $version): array
    {
        $matches = [];
        preg_match('#(?P<major>\d+)\.(?P<minor>\d+).(?P<micro>\d+)[-]?(?P<type>RC|alpha|beta|dev)?(?P<number>[\d]?).*#ui', $version, $matches);
        $parts = [
            'major'    => $matches['major'],
            'minor'    => $matches['minor'],
            'micro'    => $matches['micro'],
            'type'     => strtolower($matches['type'] ? $matches['type'] : 'stable'),
            'number'   => $matches['number'],
            'original' => $version,
        ];

        return $parts;
    }
}
