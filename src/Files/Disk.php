<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Files;

use League\Flysystem\Filesystem as HyperfFilesystem;

/**
 * @method bool     get(string $filename)
 * @method resource readStream(string $filename)
 * @method bool     delete(string $filename)
 * @method bool     exists(string $filename)
 */
class Disk
{
    /**
     * @var HyperfFilesystem
     */
    protected $disk;

    /**
     * @var null|string
     */
    protected $name;

    /**
     * @var array
     */
    protected $diskOptions;

    public function __construct(HyperfFilesystem $disk, string $name = null, array $diskOptions = [])
    {
        $this->disk = $disk;
        $this->name = $name;
        $this->diskOptions = $diskOptions;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->disk->{$name}(...$arguments);
    }

    /**
     * @param resource|string $contents
     */
    public function put(string $destination, $contents): bool
    {
        return $this->disk->put($destination, $contents, $this->diskOptions);
    }

    public function copy(TemporaryFile $source, string $destination): bool
    {
        $readStream = $source->readStream();

        if (realpath($destination)) {
            $tempStream = fopen($destination, 'rb+');
            $success = false !== stream_copy_to_stream($readStream, $tempStream);

            if (is_resource($tempStream)) {
                fclose($tempStream);
            }
        } else {
            $success = $this->put($destination, $readStream);
        }

        if (is_resource($readStream)) {
            fclose($readStream);
        }

        return $success;
    }

    public function touch(string $filename)
    {
        $this->disk->put($filename, '', $this->diskOptions);
    }
}
