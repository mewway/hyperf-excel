<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Files;

class LocalTemporaryFile extends TemporaryFile
{
    /**
     * @var string
     */
    private $filePath;

    public function __construct(string $filePath)
    {
        touch($filePath);

        $this->filePath = realpath($filePath);
    }

    public function getLocalPath(): string
    {
        return $this->filePath;
    }

    public function exists(): bool
    {
        return file_exists($this->filePath);
    }

    public function delete(): bool
    {
        if (@unlink($this->filePath) || ! $this->exists()) {
            return true;
        }

        return unlink($this->filePath);
    }

    /**
     * @return resource
     */
    public function readStream()
    {
        return fopen($this->getLocalPath(), 'rb+');
    }

    public function contents(): string
    {
        return file_get_contents($this->filePath);
    }

    /**
     * @param @param string|resource $contents
     */
    public function put($contents)
    {
        file_put_contents($this->filePath, $contents);
    }
}
