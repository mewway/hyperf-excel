<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Files;

use Hyperf\Utils\Str;

class TemporaryFileFactory
{
    /**
     * @var null|string
     */
    private $temporaryPath;

    /**
     * @var null|string
     */
    private $temporaryDisk;

    public function __construct(string $temporaryPath = null, string $temporaryDisk = null)
    {
        $this->temporaryPath = $temporaryPath;
        $this->temporaryDisk = $temporaryDisk;
    }

    public function make(string $fileExtension = null): TemporaryFile
    {
        if (null !== $this->temporaryDisk) {
            return $this->makeRemote($fileExtension);
        }

        return $this->makeLocal(null, $fileExtension);
    }

    public function makeLocal(string $fileName = null, string $fileExtension = null): LocalTemporaryFile
    {
        if (! file_exists($this->temporaryPath) && ! mkdir($concurrentDirectory = $this->temporaryPath) && ! is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        return new LocalTemporaryFile(
            $this->temporaryPath . DIRECTORY_SEPARATOR . ($fileName ?: $this->generateFilename($fileExtension))
        );
    }

    private function makeRemote(string $fileExtension = null): RemoteTemporaryFile
    {
        $filename = $this->generateFilename($fileExtension);

        return new RemoteTemporaryFile(
            $this->temporaryDisk,
            config('excel.temporary_files.remote_prefix') . $filename,
            $this->makeLocal($filename)
        );
    }

    private function generateFilename(string $fileExtension = null): string
    {
        return 'export-excel-' . Str::random(32) . ($fileExtension ? '.' . $fileExtension : '');
    }
}
