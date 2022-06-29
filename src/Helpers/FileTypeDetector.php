<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Helpers;

use Huanhyperf\Excel\Exceptions\NoTypeDetectedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileTypeDetector
{
    /**
     * @param $filePath
     *
     * @throws NoTypeDetectedException
     *
     * @return null|string
     */
    public static function detect($filePath, string $type = null)
    {
        if (null !== $type) {
            return $type;
        }

        if (! $filePath instanceof UploadedFile) {
            $pathInfo = pathinfo($filePath);
            $extension = $pathInfo['extension'] ?? '';
        } else {
            $extension = $filePath->getClientOriginalExtension();
        }

        if (null === $type && '' === trim($extension)) {
            throw new NoTypeDetectedException();
        }

        return config('excel.extension_detector.' . strtolower($extension));
    }

    /**
     * @throws NoTypeDetectedException
     */
    public static function detectStrict(string $filePath, string $type = null): string
    {
        $type = static::detect($filePath, $type);

        if (! $type) {
            throw new NoTypeDetectedException();
        }

        return $type;
    }
}
