<?php

namespace Huanhyperf\Excel\Files;

use Hyperf\Filesystem\FilesystemFactory as Factory;

class Filesystem
{
    /**
     * @var Factory
     */
    private $filesystem;

    /**
     * @param  Factory  $filesystem
     */
    public function __construct(Factory $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param  string|null  $disk
     * @param  array  $diskOptions
     * @return Disk
     */
    public function disk(string $disk = null, array $diskOptions = []): Disk
    {
        return new Disk(
            $this->filesystem->disk($disk),
            $disk,
            $diskOptions
        );
    }
}