<?php

namespace HyperfTest\Cache;

use Huanhyperf\Excel\Cache\BatchCache;
use Huanhyperf\Excel\Cache\CacheManager;
use HyperfTest\AbstractTestCase;
use Psr\SimpleCache\CacheInterface;

class CacheManagerTest extends AbstractTestCase
{

    public function testGetDefaultDriver()
    {
        $driver = $this->getManager()->getDefaultDriver();
        $this->assertIsString($driver);
        $this->assertEquals($driver, 'memory');
    }

    public function testCreateBatchDriver()
    {
        $driver = $this->getManager()->createBatchDriver();
        $this->assertInstanceOf(BatchCache::class, $driver);
    }

    public function testIsInMemory()
    {
        $this->assertTrue($this->getManager()->isInMemory());
    }

    public function testCreateHyperfCacheDriver()
    {
        $hyperfCacheDriver = $this->getManager()->createHyperfCacheDriver();
        $this->assertInstanceOf(CacheInterface::class, $hyperfCacheDriver);
    }

    public function testCreateMemoryDriver()
    {
        $memoryDriver = $this->getManager()->createMemoryDriver();
        $this->assertInstanceOf(CacheInterface::class, $memoryDriver);
    }

    public function testFlush()
    {
        $this->getManager()->flush();
        $this->assertTrue(true);
    }

    private function getManager(): CacheManager
    {
        return make(CacheManager::class);
    }
}
