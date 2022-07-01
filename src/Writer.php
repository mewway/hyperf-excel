<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

use Huanhyperf\Excel\Concerns\WithBackgroundColor;
use Huanhyperf\Excel\Concerns\WithCustomValueBinder;
use Huanhyperf\Excel\Concerns\WithDefaultStyles;
use Huanhyperf\Excel\Concerns\WithEvents;
use Huanhyperf\Excel\Concerns\WithMultipleSheets;
use Huanhyperf\Excel\Concerns\WithProperties;
use Huanhyperf\Excel\Concerns\WithTitle;
use Huanhyperf\Excel\Events\BeforeExport;
use Huanhyperf\Excel\Events\BeforeWriting;
use Huanhyperf\Excel\Factories\WriterFactory;
use Huanhyperf\Excel\Files\RemoteTemporaryFile;
use Huanhyperf\Excel\Files\TemporaryFile;
use Huanhyperf\Excel\Files\TemporaryFileFactory;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/** @mixin Spreadsheet */
class Writer
{
    use DelegatedMacroable;
    use HasEventBus;

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * @var object
     */
    protected $exportable;

    /**
     * @var TemporaryFileFactory
     */
    protected $temporaryFileFactory;

    public function __construct(TemporaryFileFactory $temporaryFileFactory)
    {
        $this->temporaryFileFactory = $temporaryFileFactory;

        $this->setDefaultValueBinder();
    }

    /**
     * @param object $export
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function export($export, string $writerType): TemporaryFile
    {
        $this->open($export);

        $sheetExports = [$export];
        if ($export instanceof WithMultipleSheets) {
            $sheetExports = $export->sheets();
        }

        foreach ($sheetExports as $sheetExport) {
            $this->addNewSheet()->export($sheetExport);
        }

        return $this->write($export, $this->temporaryFileFactory->makeLocal(null, strtolower($writerType)), $writerType);
    }

    /**
     * @param object $export
     *
     * @return $this
     */
    public function open($export)
    {
        $this->exportable = $export;

        if ($export instanceof WithEvents) {
            $this->registerListeners($export->registerEvents());
        }

        $this->exportable = $export;
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->disconnectWorksheets();

        if ($export instanceof WithCustomValueBinder) {
            Cell::setValueBinder($export);
        }

        $this->handleDocumentProperties($export);

        if ($export instanceof WithBackgroundColor) {
            $defaultStyle = $this->spreadsheet->getDefaultStyle();
            $backgroundColor = $export->backgroundColor();

            if (is_string($backgroundColor)) {
                $defaultStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($backgroundColor);
            }

            if (is_array($backgroundColor)) {
                $defaultStyle->applyFromArray(['fill' => $backgroundColor]);
            }

            if ($backgroundColor instanceof Color) {
                $defaultStyle->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($backgroundColor);
            }
        }

        if ($export instanceof WithDefaultStyles) {
            $defaultStyle = $this->spreadsheet->getDefaultStyle();
            $styles = $export->defaultStyles($defaultStyle);

            if (is_array($styles)) {
                $defaultStyle->applyFromArray($styles);
            }
        }

        $this->raise(new BeforeExport($this, $this->exportable));

        return $this;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     *
     * @return Writer
     */
    public function reopen(TemporaryFile $tempFile, string $writerType)
    {
        $reader = IOFactory::createReader($writerType);
        $this->spreadsheet = $reader->load($tempFile->sync()->getLocalPath());

        return $this;
    }

    /**
     * @param object $export
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function write($export, TemporaryFile $temporaryFile, string $writerType): TemporaryFile
    {
        $this->exportable = $export;

        $this->spreadsheet->setActiveSheetIndex(0);

        $this->raise(new BeforeWriting($this, $this->exportable));

        $writer = WriterFactory::make(
            $writerType,
            $this->spreadsheet,
            $export
        );

        $writer->save(
            $path = $temporaryFile->getLocalPath()
        );

        if ($temporaryFile instanceof RemoteTemporaryFile) {
            $temporaryFile->updateRemote();
        }

        $this->clearListeners();
        $this->spreadsheet->disconnectWorksheets();
        unset($this->spreadsheet);

        return $temporaryFile;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     *
     * @return Sheet
     */
    public function addNewSheet(int $sheetIndex = null)
    {
        return new Sheet($this->spreadsheet->createSheet($sheetIndex));
    }

    /**
     * @return Spreadsheet
     */
    public function getDelegate()
    {
        return $this->spreadsheet;
    }

    /**
     * @return $this
     */
    public function setDefaultValueBinder()
    {
        Cell::setValueBinder(
            make(config('excel.value_binder.default', DefaultValueBinder::class))
        );

        return $this;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     *
     * @return Sheet
     */
    public function getSheetByIndex(int $sheetIndex)
    {
        return new Sheet($this->getDelegate()->getSheet($sheetIndex));
    }

    /**
     * @param string $concern
     */
    public function hasConcern($concern): bool
    {
        return $this->exportable instanceof $concern;
    }

    /**
     * @param object $export
     */
    protected function handleDocumentProperties($export)
    {
        $properties = config('excel.exports.properties', []);

        if ($export instanceof WithProperties) {
            $properties = array_merge($properties, $export->properties());
        }

        if ($export instanceof WithTitle) {
            $properties = array_merge($properties, ['title' => $export->title()]);
        }

        $props = $this->spreadsheet->getProperties();

        foreach (array_filter($properties) as $property => $value) {
            switch ($property) {
                case 'title':
                    $props->setTitle($value);

                    break;

                case 'description':
                    $props->setDescription($value);

                    break;

                case 'creator':
                    $props->setCreator($value);

                    break;

                case 'lastModifiedBy':
                    $props->setLastModifiedBy($value);

                    break;

                case 'subject':
                    $props->setSubject($value);

                    break;

                case 'keywords':
                    $props->setKeywords($value);

                    break;

                case 'category':
                    $props->setCategory($value);

                    break;

                case 'manager':
                    $props->setManager($value);

                    break;

                case 'company':
                    $props->setCompany($value);

                    break;
            }
        }
    }
}
