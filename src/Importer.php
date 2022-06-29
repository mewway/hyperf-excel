<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

use Huanhyperf\Excel\Concerns\ShouldQueue;
use Hyperf\Utils\Collection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface Importer
{
    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     *
     * @return Reader
     */
    public function import($import, $filePath, string $disk = null, string $readerType = null);

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     */
    public function toArray($import, $filePath, string $disk = null, string $readerType = null): array;

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     */
    public function toCollection($import, $filePath, string $disk = null, string $readerType = null): Collection;

    /**
     * @param string|UploadedFile $filePath
     *
     * @return
     */
    public function queueImport(ShouldQueue $import, $filePath, string $disk = null, string $readerType = null);
}
