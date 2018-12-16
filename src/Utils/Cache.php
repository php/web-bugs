<?php

namespace App\Utils;

/**
 * A simple cache service for storing data in file(s).
 */
class Cache
{
    /**
     * Pool of items.
     *
     * @var array
     */
    private $pool = [];

    /**
     * Temporary cache directory where data is stored.
     *
     * @var string
     */
    private $dir;

    /**
     * Default time after cache file is considered expired in seconds.
     */
    public const TTL = 3600;

    /**
     * Class constructor.
     */
    public function __construct(string $dir)
    {
        $this->dir = $dir;

        // Create cache directory if it doesn't exist.
        if (!file_exists($this->dir)) {
            mkdir($this->dir, 0777, true);
            chmod($this->dir, 0777);
        }

        // Validate cache directory
        if (!is_dir($this->dir)) {
            throw new \Exception($this->dir.' is not a valid directory.');
        }
    }

    /**
     * Write data to cache file.
     */
    public function set(string $key, $data, int $ttl = self::TTL): void
    {
        if (!$this->validateKey($key)) {
            throw new Exception('Key name '.$key.' is invalid.');
        }

        $item = [time() + $ttl, serialize($data)];
        $this->pool[$key] = $data;

        $string = '<?php return '.var_export($item, true).";\n";

        file_put_contents($this->dir.'/'.$key.'.php', $string);
    }

    /**
     * Check if item has been cached and is available.
     */
    public function has(string $key): bool
    {
        if (isset($this->pool[$key])) {
            return true;
        }

        $file = $this->dir.'/'.$key.'.php';

        if (!is_file($file)) {
            return false;
        }

        $data = require $file;

        if (is_array($data) && isset($data[0]) && is_int($data[0]) && time() < $data[0]) {
            return true;
        }

        return false;
    }

    /**
     * Get data from the cache pool.
     */
    public function get(string $key): ?array
    {
        if (isset($this->pool[$key])) {
            return $this->pool[$key];
        }

        $file = $this->dir.'/'.$key.'.php';

        if (is_file($file)) {
            $data = require $file;
            $this->pool[$key] = unserialize($data[1]);

            return $this->pool[$key];
        }

        return null;
    }

    /**
     * Wipes entire cache.
     */
    public function clear(): bool
    {
        $success = true;

        $this->pool = [];

        foreach (new \DirectoryIterator($this->dir) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            if (!unlink($fileInfo->getRealPath())) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Delete item from the cache.
     */
    public function delete(string $key): bool
    {
        $success = true;

        unset($this->pool[$key]);

        $file = $this->dir.'/'.$key.'.php';

        if (is_file($file) && !unlink($file)) {
            $success = false;
        }

        return $success;
    }

    /**
     * Validate key.
     */
    private function validateKey(string $key): bool
    {
        return (bool) preg_match('/[a-z\_\-0-9]/i', $key);
    }
}
