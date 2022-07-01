<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Files;

use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class TemporaryFile
{
    abstract public function getLocalPath(): string;

    abstract public function exists(): bool;

    /**
     * @param @param string|resource $contents
     */
    abstract public function put($contents);

    abstract public function delete(): bool;

    /**
     * @return resource
     */
    abstract public function readStream();

    abstract public function contents(): string;

    public function sync(): TemporaryFile
    {
        return $this;
    }

    /**
     * @param string|UploadedFile $filePath
     */
    public function copyFrom($filePath, string $disk = null): TemporaryFile
    {
        if ($filePath instanceof UploadedFile) {
            $readStream = fopen($filePath->getRealPath(), 'rb');
        } elseif (null === $disk && false !== realpath($filePath)) {
            $readStream = fopen($filePath, 'rb');
        } else {
            $readStream = make(Filesystem::class)->disk($disk)->readStream($filePath);
        }

        $this->put($readStream);

        if (is_resource($readStream)) {
            fclose($readStream);
        }

        return $this->sync();
    }
}
