<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Factories;

use Huanhyperf\Excel\Concerns\MapsCsvSettings;
use Huanhyperf\Excel\Concerns\WithCustomCsvSettings;
use Huanhyperf\Excel\Concerns\WithLimit;
use Huanhyperf\Excel\Concerns\WithReadFilter;
use Huanhyperf\Excel\Concerns\WithStartRow;
use Huanhyperf\Excel\Exceptions\NoTypeDetectedException;
use Huanhyperf\Excel\Files\TemporaryFile;
use Huanhyperf\Excel\Filters\LimitFilter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Reader\IReader;

class ReaderFactory
{
    use MapsCsvSettings;

    /**
     * @param object $import
     * @param string $readerType
     *
     * @throws Exception
     */
    public static function make($import, TemporaryFile $file, string $readerType = null): IReader
    {
        $reader = IOFactory::createReader(
            $readerType ?: static::identify($file)
        );

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(config('excel.imports.read_only', true));
        }

        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(! config('excel.imports.ignore_empty', false));
        }

        if ($reader instanceof Csv) {
            static::applyCsvSettings(config('excel.imports.csv', []));

            if ($import instanceof WithCustomCsvSettings) {
                static::applyCsvSettings($import->getCsvSettings());
            }

            $reader->setDelimiter(static::$delimiter);
            $reader->setEnclosure(static::$enclosure);
            $reader->setEscapeCharacter(static::$escapeCharacter);
            $reader->setContiguous(static::$contiguous);
            $reader->setInputEncoding(static::$inputEncoding);
        }

        if ($import instanceof WithReadFilter) {
            $reader->setReadFilter($import->readFilter());
        } elseif ($import instanceof WithLimit) {
            $reader->setReadFilter(new LimitFilter(
                $import instanceof WithStartRow ? $import->startRow() : 1,
                $import->limit()
            ));
        }

        return $reader;
    }

    /**
     * @throws NoTypeDetectedException
     */
    private static function identify(TemporaryFile $temporaryFile): string
    {
        try {
            return IOFactory::identify($temporaryFile->getLocalPath());
        } catch (Exception $e) {
            throw new NoTypeDetectedException(null, null, $e);
        }
    }
}
