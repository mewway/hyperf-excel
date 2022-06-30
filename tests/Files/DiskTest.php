<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace HyperfTest\Files;

use Huanhyperf\Excel\Files\Disk;
use Huanhyperf\Excel\Files\Filesystem;
use Hyperf\Filesystem\FilesystemFactory;
use HyperfTest\AbstractTestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @coversNothing
 */
class DiskTest extends AbstractTestCase
{
    public function testExists()
    {
        $check = $this->getDisk()->exists('/none/notExistFile.not');
        $this->assertFalse($check);
        $check = $this->getDisk()->exists('composer.json');
        $this->assertTrue($check);
    }

    public function testTouch()
    {
        $resp = $this->getDisk()->touch('composer.json');
        $this->assertNull($resp);
    }

    public function testPut()
    {
    }

    public function testCopy()
    {
    }

    public function testGet()
    {
    }

    public function testDelete()
    {
    }

    public function testReadStream()
    {
    }

    public function getDisk(): Disk
    {
        return make(Disk::class);
    }
}
