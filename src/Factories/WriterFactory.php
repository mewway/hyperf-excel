<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Factories;

use Huanhyperf\Excel\Cache\CacheManager;
use Huanhyperf\Excel\Concerns\MapsCsvSettings;
use Huanhyperf\Excel\Concerns\WithCharts;
use Huanhyperf\Excel\Concerns\WithCustomCsvSettings;
use Huanhyperf\Excel\Concerns\WithMultipleSheets;
use Huanhyperf\Excel\Concerns\WithPreCalculateFormulas;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;

class WriterFactory
{
    use MapsCsvSettings;

    /**
     * @param object $export
     *
     * @throws Exception
     */
    public static function make(string $writerType, Spreadsheet $spreadsheet, $export): IWriter
    {
        $writer = IOFactory::createWriter($spreadsheet, $writerType);

        $writer->setUseDiskCaching(
            CacheManager::DRIVER_MEMORY !== config('excel.cache.driver', CacheManager::DRIVER_MEMORY)
        );

        if (static::includesCharts($export)) {
            $writer->setIncludeCharts(true);
        }

        if ($writer instanceof Html && $export instanceof WithMultipleSheets) {
            $writer->writeAllSheets();
        }

        if ($writer instanceof Csv) {
            static::applyCsvSettings(config('excel.exports.csv', []));

            if ($export instanceof WithCustomCsvSettings) {
                static::applyCsvSettings($export->getCsvSettings());
            }

            $writer->setDelimiter(static::$delimiter);
            $writer->setEnclosure(static::$enclosure);
            $writer->setEnclosureRequired((bool) static::$enclosure);
            $writer->setLineEnding(static::$lineEnding);
            $writer->setUseBOM(static::$useBom);
            $writer->setIncludeSeparatorLine(static::$includeSeparatorLine);
            $writer->setExcelCompatibility(static::$excelCompatibility);
            $writer->setOutputEncoding(static::$outputEncoding);
        }

        // Calculation settings
        $writer->setPreCalculateFormulas(
            $export instanceof WithPreCalculateFormulas
                ? true
                : config('excel.exports.pre_calculate_formulas', false)
        );

        return $writer;
    }

    /**
     * @param $export
     */
    private static function includesCharts($export): bool
    {
        if ($export instanceof WithCharts) {
            return true;
        }

        if ($export instanceof WithMultipleSheets) {
            foreach ($export->sheets() as $sheet) {
                if ($sheet instanceof WithCharts) {
                    return true;
                }
            }
        }

        return false;
    }
}
