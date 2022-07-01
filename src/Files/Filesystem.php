<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Files;

use Hyperf\Filesystem\FilesystemFactory as Factory;

class Filesystem
{
    /**
     * @var FilesystemAdapter
     */
    private $filesystem;

    /**
     * @var Factory
     */
    private $driver;

    public function __construct(Factory $driver)
    {
        $this->driver = $driver;
    }

    public function disk(string $disk = null, array $diskOptions = []): Disk
    {
        return new Disk(
            $this->getAdapter($disk),
            $disk,
            $diskOptions
        );
    }

    private function getAdapter(string $disk = null)
    {
        $driver = $this->driver->get($disk ?? 'local');

        return $this->filesystem = new FilesystemAdapter($driver);
    }
}
