<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Cache;

use Psr\SimpleCache\CacheInterface;

class MemoryCache implements CacheInterface
{
    /**
     * @var null|int
     */
    protected $memoryLimit;

    /**
     * @var array
     */
    protected $cache = [];

    public function __construct(int $memoryLimit = null)
    {
        $this->memoryLimit = $memoryLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->cache = [];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        unset($this->cache[$key]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->cache[$key];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return isset($this->cache[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->cache[$key] = $value;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }

        return true;
    }

    public function reachedMemoryLimit(): bool
    {
        // When no limit is given, we'll never reach any limit.
        if (null === $this->memoryLimit) {
            return false;
        }

        return count($this->cache) >= $this->memoryLimit;
    }

    public function flush(): array
    {
        $memory = $this->cache;

        $this->clear();

        return $memory;
    }
}
