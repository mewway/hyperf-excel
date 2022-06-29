<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Cache;

use Hyperf\Cache\Cache;
use Hyperf\Cache\CacheManager as Manager;
use Psr\SimpleCache\CacheInterface;

class CacheManager extends Manager
{
    /**
     * @const string
     */
    public const DRIVER_BATCH = 'batch';

    /**
     * @const string
     */
    public const DRIVER_MEMORY = 'memory';

    /**
     * @const string
     */
    public const DRIVER_ILLUMINATE = 'illuminate';

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return config('excel.cache.driver', 'memory');
    }

    /**
     * @return MemoryCache
     */
    public function createMemoryDriver(): CacheInterface
    {
        return new MemoryCache(
            config('excel.cache.batch.memory_limit', 60000)
        );
    }

    /**
     * @return BatchCache
     */
    public function createBatchDriver(): CacheInterface
    {
        return new BatchCache(
            $this->createIlluminateDriver(),
            $this->createMemoryDriver()
        );
    }

    public function createIlluminateDriver(): CacheInterface
    {
        return Cache::driver(
            config('excel.cache.illuminate.store')
        );
    }

    public function flush()
    {
        $this->driver()->clear();
    }

    public function isInMemory(): bool
    {
        return self::DRIVER_MEMORY === $this->getDefaultDriver();
    }
}
