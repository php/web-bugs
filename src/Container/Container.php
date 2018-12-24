<?php

namespace App\Container;

use App\Container\Exception\EntryNotFoundException;
use App\Container\Exception\ContainerException;

/**
 * PSR-11 compatible dependency injection container.
 */
class Container implements ContainerInterface
{
    /**
     * All defined services and parameters.
     *
     * @var array
     */
    public $entries = [];

    /**
     * Already retrieved items are stored for faster retrievals in the same run.
     *
     * @var array
     */
    private $store = [];

    /**
     * Services already created to prevent circular references.
     *
     * @var array
     */
    private $locks = [];

    /**
     * Class constructor.
     */
    public function __construct(array $configurations = [])
    {
        $this->entries = $configurations;
    }

    /**
     * Set service.
     */
    public function set(string $key, $entry): void
    {
        $this->entries[$key] = $entry;
    }

    /**
     * Get entry.
     *
     * @return mixed
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new EntryNotFoundException($id.' entry not found.');
        }

        if (!isset($this->store[$id])) {
            $this->store[$id] = $this->createEntry($id);
        }

        return $this->store[$id];
    }

    /**
     * Check if entry is available in the container.
     */
    public function has(string $id): bool
    {
        return isset($this->entries[$id]);
    }

    /**
     * Create new entry - service or configuration parameter.
     *
     * @return mixed
     */
    private function createEntry(string $id)
    {
        $entry = &$this->entries[$id];

        // Entry is a configuration parameter.
        if (!class_exists($id) && !is_callable($entry)) {
            return $entry;
        }

        // Entry is a service.
        if (class_exists($id) && !is_callable($entry)) {
            throw new ContainerException($id.' entry must be callable.');
        } elseif (class_exists($id) && isset($this->locks[$id])) {
            throw new ContainerException($id.' entry contains a circular reference.');
        }

        $this->locks[$id] = true;

        return $entry($this);
    }
}
