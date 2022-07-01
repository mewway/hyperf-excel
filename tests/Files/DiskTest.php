<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace HyperfTest\Files;

use Huanhyperf\Excel\Files\Disk;
use Huanhyperf\Excel\Files\Filesystem;
use HyperfTest\AbstractTestCase;

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
        $this->getDisk()->touch('composer.json');
        $this->assertTrue(true);
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
        /**
         * @var Filesystem $fileSystem
         */
        $fileSystem = make(Filesystem::class);

        return $fileSystem->disk();
    }
}
