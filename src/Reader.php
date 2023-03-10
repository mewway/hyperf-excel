<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

use Huanhyperf\Excel\Concerns\HasReferencesToOtherSheets;
use Huanhyperf\Excel\Concerns\ShouldQueue;
use Huanhyperf\Excel\Concerns\SkipsUnknownSheets;
use Huanhyperf\Excel\Concerns\WithCalculatedFormulas;
use Huanhyperf\Excel\Concerns\WithChunkReading;
use Huanhyperf\Excel\Concerns\WithCustomValueBinder;
use Huanhyperf\Excel\Concerns\WithEvents;
use Huanhyperf\Excel\Concerns\WithFormatData;
use Huanhyperf\Excel\Concerns\WithMultipleSheets;
use Huanhyperf\Excel\Events\AfterImport;
use Huanhyperf\Excel\Events\BeforeImport;
use Huanhyperf\Excel\Events\ImportFailed;
use Huanhyperf\Excel\Exceptions\NoTypeDetectedException;
use Huanhyperf\Excel\Exceptions\SheetNotFoundException;
use Huanhyperf\Excel\Factories\ReaderFactory;
use Huanhyperf\Excel\Files\TemporaryFile;
use Huanhyperf\Excel\Files\TemporaryFileFactory;
use Huanhyperf\Excel\Transactions\TransactionHandler;
use Hyperf\Utils\Collection;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

/** @mixin Spreadsheet */
class Reader
{
    use DelegatedMacroable;
    use HasEventBus;

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * @var object[]
     */
    protected $sheetImports = [];

    /**
     * @var TemporaryFile
     */
    protected $currentFile;

    /**
     * @var TemporaryFileFactory
     */
    protected $temporaryFileFactory;

    /**
     * @var TransactionHandler
     */
    protected $transaction;

    /**
     * @var IReader
     */
    protected $reader;

    public function __construct(TemporaryFileFactory $temporaryFileFactory, TransactionHandler $transaction)
    {
        $this->setDefaultValueBinder();

        $this->transaction = $transaction;
        $this->temporaryFileFactory = $temporaryFileFactory;
    }

    public function __sleep()
    {
        return ['spreadsheet', 'sheetImports', 'currentFile', 'temporaryFileFactory', 'reader'];
    }

    public function __wakeup()
    {
        $this->transaction = make(TransactionHandler::class);
    }

    /**
     * @param $import
     * @param $filePath
     *
     * @throws Exception
     * @throws NoTypeDetectedException
     * @throws SheetNotFoundException
     * @throws Throwable
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     *
     * @return null|$this|PendingDispatch
     */
    public function read($import, $filePath, string $readerType = null, string $disk = null)
    {
        $this->reader = $this->getReader($import, $filePath, $readerType, $disk);

        if ($import instanceof WithChunkReading) {
            return (new ChunkReader())->read($import, $this, $this->currentFile);
        }

        try {
            $this->loadSpreadsheet($import, $this->reader);

            ($this->transaction)(function () use ($import) {
                $sheetsToDisconnect = [];

                foreach ($this->sheetImports as $index => $sheetImport) {
                    if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                        $sheet->import($sheetImport, $sheet->getStartRow($sheetImport));

                        // when using WithCalculatedFormulas we need to keep the sheet until all sheets are imported
                        if (! ($sheetImport instanceof HasReferencesToOtherSheets)) {
                            $sheet->disconnect();
                        } else {
                            $sheetsToDisconnect[] = $sheet;
                        }
                    }
                }

                foreach ($sheetsToDisconnect as $sheet) {
                    $sheet->disconnect();
                }
            });

            $this->afterImport($import);
        } catch (Throwable $e) {
            $this->raise(new ImportFailed($e));
            $this->garbageCollect();

            throw $e;
        }

        return $this;
    }

    /**
     * @param $import
     * @param $filePath
     *
     * @throws Exception
     * @throws NoTypeDetectedException
     * @throws SheetNotFoundException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function toArray($import, $filePath, string $readerType = null, string $disk = null): array
    {
        $this->reader = $this->getReader($import, $filePath, $readerType, $disk);

        $this->loadSpreadsheet($import);

        $sheets = [];
        $sheetsToDisconnect = [];
        foreach ($this->sheetImports as $index => $sheetImport) {
            $calculatesFormulas = $sheetImport instanceof WithCalculatedFormulas;
            $formatData = $sheetImport instanceof WithFormatData;
            if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                $sheets[$index] = $sheet->toArray($sheetImport, $sheet->getStartRow($sheetImport), null, $calculatesFormulas, $formatData);
                // when using WithCalculatedFormulas we need to keep the sheet until all sheets are imported
                if (! ($sheetImport instanceof HasReferencesToOtherSheets)) {
                    $sheet->disconnect();
                } else {
                    $sheetsToDisconnect[] = $sheet;
                }
            }
        }
        foreach ($sheetsToDisconnect as $sheet) {
            $sheet->disconnect();
        }

        $this->afterImport($import);

        return $sheets;
    }

    /**
     * @param $import
     * @param $filePath
     *
     * @throws Exception
     * @throws NoTypeDetectedException
     * @throws SheetNotFoundException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function toCollection($import, $filePath, string $readerType = null, string $disk = null): Collection
    {
        $this->reader = $this->getReader($import, $filePath, $readerType, $disk);
        $this->loadSpreadsheet($import);

        $sheets = new Collection();
        $sheetsToDisconnect = [];
        foreach ($this->sheetImports as $index => $sheetImport) {
            $calculatesFormulas = $sheetImport instanceof WithCalculatedFormulas;
            $formatData = $sheetImport instanceof WithFormatData;
            if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                $sheets->put($index, $sheet->toCollection($sheetImport, $sheet->getStartRow($sheetImport), null, $calculatesFormulas, $formatData));

                // when using WithCalculatedFormulas we need to keep the sheet until all sheets are imported
                if (! ($sheetImport instanceof HasReferencesToOtherSheets)) {
                    $sheet->disconnect();
                } else {
                    $sheetsToDisconnect[] = $sheet;
                }
            }
        }

        foreach ($sheetsToDisconnect as $sheet) {
            $sheet->disconnect();
        }

        $this->afterImport($import);

        return $sheets;
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
    public function setDefaultValueBinder(): self
    {
        Cell::setValueBinder(
            make(config('excel.value_binder.default', DefaultValueBinder::class))
        );

        return $this;
    }

    /**
     * @param object $import
     */
    public function loadSpreadsheet($import)
    {
        $this->sheetImports = $this->buildSheetImports($import);

        $this->readSpreadsheet();

        // When no multiple sheets, use the main import object
        // for each loaded sheet in the spreadsheet
        if (! $import instanceof WithMultipleSheets) {
            $this->sheetImports = array_fill(0, $this->spreadsheet->getSheetCount(), $import);
        }

        $this->beforeImport($import);
    }

    public function readSpreadsheet()
    {
        $this->spreadsheet = $this->reader->load(
            $this->currentFile->getLocalPath()
        );
    }

    /**
     * @param object $import
     */
    public function beforeImport($import)
    {
        $this->raise(new BeforeImport($this, $import));
    }

    /**
     * @param object $import
     */
    public function afterImport($import)
    {
        $this->raise(new AfterImport($this, $import));

        $this->garbageCollect();
    }

    public function getPhpSpreadsheetReader(): IReader
    {
        return $this->reader;
    }

    /**
     * @param object $import
     */
    public function getWorksheets($import): array
    {
        // Csv doesn't have worksheets.
        if (! method_exists($this->reader, 'listWorksheetNames')) {
            return ['Worksheet' => $import];
        }

        $worksheets = [];
        $worksheetNames = $this->reader->listWorksheetNames($this->currentFile->getLocalPath());
        if ($import instanceof WithMultipleSheets) {
            $sheetImports = $import->sheets();

            foreach ($sheetImports as $index => $sheetImport) {
                // Translate index to name.
                if (is_numeric($index)) {
                    $index = $worksheetNames[$index] ?? $index;
                }

                // Specify with worksheet name should have which import.
                $worksheets[$index] = $sheetImport;
            }

            // Load specific sheets.
            if (method_exists($this->reader, 'setLoadSheetsOnly')) {
                $this->reader->setLoadSheetsOnly(
                    collect($worksheetNames)->intersect(array_keys($worksheets))->values()->all()
                );
            }
        } else {
            // Each worksheet the same import class.
            foreach ($worksheetNames as $name) {
                $worksheets[$name] = $import;
            }
        }

        return $worksheets;
    }

    public function getTotalRows(): array
    {
        $info = $this->reader->listWorksheetInfo($this->currentFile->getLocalPath());

        $totalRows = [];
        foreach ($info as $sheet) {
            $totalRows[$sheet['worksheetName']] = $sheet['totalRows'];
        }

        return $totalRows;
    }

    /**
     * @param $import
     * @param $sheetImport
     * @param $index
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws SheetNotFoundException
     *
     * @return null|Sheet
     */
    protected function getSheet($import, $sheetImport, $index)
    {
        try {
            return Sheet::make($this->spreadsheet, $index);
        } catch (SheetNotFoundException $e) {
            if ($import instanceof SkipsUnknownSheets) {
                $import->onUnknownSheet($index);

                return null;
            }

            if ($sheetImport instanceof SkipsUnknownSheets) {
                $sheetImport->onUnknownSheet($index);

                return null;
            }

            throw $e;
        }
    }

    /**
     * @param object $import
     */
    private function buildSheetImports($import): array
    {
        $sheetImports = [];
        if ($import instanceof WithMultipleSheets) {
            $sheetImports = $import->sheets();

            // When only sheet names are given and the reader has
            // an option to load only the selected sheets.
            if (
                method_exists($this->reader, 'setLoadSheetsOnly')
                && 0 === count(array_filter(array_keys($sheetImports), 'is_numeric'))
            ) {
                $this->reader->setLoadSheetsOnly(array_keys($sheetImports));
            }
        }

        return $sheetImports;
    }

    /**
     * @param $import
     * @param $filePath
     *
     * @throws Exception
     */
    private function getReader($import, $filePath, string $readerType = null, string $disk = null): IReader
    {
        $shouldQueue = $import instanceof ShouldQueue;
        if ($shouldQueue && ! $import instanceof WithChunkReading) {
            throw new InvalidArgumentException('ShouldQueue is only supported in combination with WithChunkReading.');
        }

        if ($import instanceof WithEvents) {
            $this->registerListeners($import->registerEvents());
        }

        if ($import instanceof WithCustomValueBinder) {
            Cell::setValueBinder($import);
        }

        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $temporaryFile = $shouldQueue ? $this->temporaryFileFactory->make($fileExtension) : $this->temporaryFileFactory->makeLocal(null, $fileExtension);
        $this->currentFile = $temporaryFile->copyFrom(
            $filePath,
            $disk
        );

        return ReaderFactory::make(
            $import,
            $this->currentFile,
            $readerType
        );
    }

    /**
     * Garbage collect.
     */
    private function garbageCollect()
    {
        $this->clearListeners();
        $this->setDefaultValueBinder();

        // Force garbage collecting
        unset($this->sheetImports, $this->spreadsheet);

        $this->currentFile->delete();
    }
}
