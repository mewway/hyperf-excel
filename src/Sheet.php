<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

use Closure;
use Huanhyperf\Excel\Concerns\FromArray;
use Huanhyperf\Excel\Concerns\FromCollection;
use Huanhyperf\Excel\Concerns\FromGenerator;
use Huanhyperf\Excel\Concerns\FromIterator;
use Huanhyperf\Excel\Concerns\FromQuery;
use Huanhyperf\Excel\Concerns\FromView;
use Huanhyperf\Excel\Concerns\OnEachRow;
use Huanhyperf\Excel\Concerns\ShouldAutoSize;
use Huanhyperf\Excel\Concerns\SkipsEmptyRows;
use Huanhyperf\Excel\Concerns\ToArray;
use Huanhyperf\Excel\Concerns\ToCollection;
use Huanhyperf\Excel\Concerns\ToModel;
use Huanhyperf\Excel\Concerns\WithCalculatedFormulas;
use Huanhyperf\Excel\Concerns\WithCharts;
use Huanhyperf\Excel\Concerns\WithChunkReading;
use Huanhyperf\Excel\Concerns\WithColumnFormatting;
use Huanhyperf\Excel\Concerns\WithColumnLimit;
use Huanhyperf\Excel\Concerns\WithColumnWidths;
use Huanhyperf\Excel\Concerns\WithCustomChunkSize;
use Huanhyperf\Excel\Concerns\WithCustomStartCell;
use Huanhyperf\Excel\Concerns\WithCustomValueBinder;
use Huanhyperf\Excel\Concerns\WithDrawings;
use Huanhyperf\Excel\Concerns\WithEvents;
use Huanhyperf\Excel\Concerns\WithFormatData;
use Huanhyperf\Excel\Concerns\WithHeadings;
use Huanhyperf\Excel\Concerns\WithMappedCells;
use Huanhyperf\Excel\Concerns\WithMapping;
use Huanhyperf\Excel\Concerns\WithProgressBar;
use Huanhyperf\Excel\Concerns\WithStrictNullComparison;
use Huanhyperf\Excel\Concerns\WithStyles;
use Huanhyperf\Excel\Concerns\WithTitle;
use Huanhyperf\Excel\Concerns\WithValidation;
use Huanhyperf\Excel\Events\AfterSheet;
use Huanhyperf\Excel\Events\BeforeSheet;
use Huanhyperf\Excel\Exceptions\ConcernConflictException;
use Huanhyperf\Excel\Exceptions\RowSkippedException;
use Huanhyperf\Excel\Exceptions\SheetNotFoundException;
use Huanhyperf\Excel\Files\TemporaryFileFactory;
use Huanhyperf\Excel\Helpers\ArrayHelper;
use Huanhyperf\Excel\Helpers\CellHelper;
use Huanhyperf\Excel\Imports\EndRowFinder;
use Huanhyperf\Excel\Imports\HeadingRowExtractor;
use Huanhyperf\Excel\Imports\ModelImporter;
use Huanhyperf\Excel\Validators\RowValidator;
use Hyperf\Utils\Collection;
use Hyperf\Utils\Contracts\Arrayable;
use PhpOffice\PhpSpreadsheet\Cell\Cell as SpreadsheetCell;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** @mixin Worksheet */
class Sheet
{
    use DelegatedMacroable;
    use HasEventBus;

    /**
     * @var int
     */
    protected $chunkSize;

    /**
     * @var TemporaryFileFactory
     */
    protected $temporaryFileFactory;

    /**
     * @var object
     */
    protected $exportable;

    /**
     * @var Worksheet
     */
    private $worksheet;

    public function __construct(Worksheet $worksheet)
    {
        $this->worksheet = $worksheet;
        $this->chunkSize = config('excel.exports.chunk_size', 100);
        $this->temporaryFileFactory = app(TemporaryFileFactory::class);
    }

    /**
     * @param int|string $index
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws SheetNotFoundException
     *
     * @return Sheet
     */
    public static function make(Spreadsheet $spreadsheet, $index)
    {
        if (is_numeric($index)) {
            return self::byIndex($spreadsheet, $index);
        }

        return self::byName($spreadsheet, $index);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws SheetNotFoundException
     */
    public static function byIndex(Spreadsheet $spreadsheet, int $index): Sheet
    {
        if (! isset($spreadsheet->getAllSheets()[$index])) {
            throw SheetNotFoundException::byIndex($index, $spreadsheet->getSheetCount());
        }

        return new static($spreadsheet->getSheet($index));
    }

    /**
     * @throws SheetNotFoundException
     */
    public static function byName(Spreadsheet $spreadsheet, string $name): Sheet
    {
        if (! $spreadsheet->sheetNameExists($name)) {
            throw SheetNotFoundException::byName($name);
        }

        return new static($spreadsheet->getSheetByName($name));
    }

    /**
     * @param object $sheetExport
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function open($sheetExport)
    {
        $this->exportable = $sheetExport;

        if ($sheetExport instanceof WithCustomValueBinder) {
            SpreadsheetCell::setValueBinder($sheetExport);
        }

        if ($sheetExport instanceof WithEvents) {
            $this->registerListeners($sheetExport->registerEvents());
        }

        $this->raise(new BeforeSheet($this, $this->exportable));

        if ($sheetExport instanceof WithTitle) {
            $title = $sheetExport->title();

            $title = str_replace(['*', ':', '/', '\\', '?', '[', ']'], '', $title);
            if (StringHelper::countCharacters($title) > Worksheet::SHEET_TITLE_MAXIMUM_LENGTH) {
                $title = StringHelper::substring($title, 0, Worksheet::SHEET_TITLE_MAXIMUM_LENGTH);
            }

            $this->worksheet->setTitle($title);
        }

        if (($sheetExport instanceof FromQuery || $sheetExport instanceof FromCollection || $sheetExport instanceof FromArray) && $sheetExport instanceof FromView) {
            throw ConcernConflictException::queryOrCollectionAndView();
        }

        if (! $sheetExport instanceof FromView && $sheetExport instanceof WithHeadings) {
            if ($sheetExport instanceof WithCustomStartCell) {
                $startCell = $sheetExport->startCell();
            }

            $this->append(
                ArrayHelper::ensureMultipleRows($sheetExport->headings()),
                $startCell ?? null,
                $this->hasStrictNullComparison($sheetExport)
            );
        }

        if ($sheetExport instanceof WithCharts) {
            $this->addCharts($sheetExport->charts());
        }

        if ($sheetExport instanceof WithDrawings) {
            $this->addDrawings($sheetExport->drawings());
        }
    }

    /**
     * @param object $sheetExport
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function export($sheetExport)
    {
        $this->open($sheetExport);

        if ($sheetExport instanceof FromView) {
            $this->fromView($sheetExport);
        } else {
            if ($sheetExport instanceof FromQuery) {
                $this->fromQuery($sheetExport, $this->worksheet);
            }

            if ($sheetExport instanceof FromCollection) {
                $this->fromCollection($sheetExport);
            }

            if ($sheetExport instanceof FromArray) {
                $this->fromArray($sheetExport);
            }

            if ($sheetExport instanceof FromIterator) {
                $this->fromIterator($sheetExport);
            }

            if ($sheetExport instanceof FromGenerator) {
                $this->fromGenerator($sheetExport);
            }
        }

        $this->close($sheetExport);
    }

    /**
     * @param object $import
     */
    public function import($import, int $startRow = 1)
    {
        if ($import instanceof WithEvents) {
            $this->registerListeners($import->registerEvents());
        }

        $this->raise(new BeforeSheet($this, $import));

        if ($import instanceof WithProgressBar && ! $import instanceof WithChunkReading) {
            $import->getConsoleOutput()->progressStart($this->worksheet->getHighestRow());
        }

        $calculatesFormulas = $import instanceof WithCalculatedFormulas;
        $formatData = $import instanceof WithFormatData;
        $endColumn = $import instanceof WithColumnLimit ? $import->endColumn() : null;

        if ($import instanceof WithMappedCells) {
            app(MappedReader::class)->map($import, $this->worksheet);
        } else {
            if ($import instanceof ToModel) {
                app(ModelImporter::class)->import($this->worksheet, $import, $startRow);
            }

            if ($import instanceof ToCollection) {
                $rows = $this->toCollection($import, $startRow, null, $calculatesFormulas, $formatData);

                if ($import instanceof WithValidation) {
                    $rows = $this->validated($import, $startRow, $rows);
                }

                $import->collection($rows);
            }

            if ($import instanceof ToArray) {
                $rows = $this->toArray($import, $startRow, null, $calculatesFormulas, $formatData);

                if ($import instanceof WithValidation) {
                    $rows = $this->validated($import, $startRow, $rows);
                }

                $import->array($rows);
            }
        }

        if ($import instanceof OnEachRow) {
            $headingRow = HeadingRowExtractor::extract($this->worksheet, $import);
            $headerIsGrouped = HeadingRowExtractor::extractGrouping($headingRow, $import);
            $endColumn = $import instanceof WithColumnLimit ? $import->endColumn() : null;
            $preparationCallback = $this->getPreparationCallback($import);

            foreach ($this->worksheet->getRowIterator()->resetStart($startRow ?? 1) as $row) {
                $sheetRow = new Row($row, $headingRow, $headerIsGrouped);

                if ($import instanceof WithValidation) {
                    $sheetRow->setPreparationCallback($preparationCallback);
                }

                if (! $import instanceof SkipsEmptyRows || ($import instanceof SkipsEmptyRows && ! $sheetRow->isEmpty($calculatesFormulas))) {
                    if ($import instanceof WithValidation) {
                        $toValidate = [$sheetRow->getIndex() => $sheetRow->toArray(null, $import instanceof WithCalculatedFormulas, $import instanceof WithFormatData, $endColumn)];

                        try {
                            app(RowValidator::class)->validate($toValidate, $import);
                            $import->onRow($sheetRow);
                        } catch (RowSkippedException $e) {
                        }
                    } else {
                        $import->onRow($sheetRow);
                    }
                }

                if ($import instanceof WithProgressBar) {
                    $import->getConsoleOutput()->progressAdvance();
                }
            }
        }

        $this->raise(new AfterSheet($this, $import));

        if ($import instanceof WithProgressBar && ! $import instanceof WithChunkReading) {
            $import->getConsoleOutput()->progressFinish();
        }
    }

    /**
     * @param object $import
     * @param null   $nullValue
     * @param bool   $calculateFormulas
     * @param bool   $formatData
     *
     * @return array
     */
    public function toArray($import, int $startRow = null, $nullValue = null, $calculateFormulas = false, $formatData = false)
    {
        if ($startRow > $this->worksheet->getHighestRow()) {
            return [];
        }

        $endRow = EndRowFinder::find($import, $startRow, $this->worksheet->getHighestRow());
        $headingRow = HeadingRowExtractor::extract($this->worksheet, $import);
        $headerIsGrouped = HeadingRowExtractor::extractGrouping($headingRow, $import);
        $endColumn = $import instanceof WithColumnLimit ? $import->endColumn() : null;

        $rows = [];
        foreach ($this->worksheet->getRowIterator($startRow, $endRow) as $index => $row) {
            $row = new Row($row, $headingRow, $headerIsGrouped);

            if ($import instanceof SkipsEmptyRows && $row->isEmpty($calculateFormulas, $endColumn)) {
                continue;
            }

            $row = $row->toArray($nullValue, $calculateFormulas, $formatData, $endColumn);

            if ($import instanceof WithMapping) {
                $row = $import->map($row);
            }

            if ($import instanceof WithValidation && method_exists($import, 'prepareForValidation')) {
                $row = $import->prepareForValidation($row, $index);
            }

            $rows[] = $row;

            if ($import instanceof WithProgressBar) {
                $import->getConsoleOutput()->progressAdvance();
            }
        }

        return $rows;
    }

    /**
     * @param object $import
     * @param null   $nullValue
     * @param bool   $calculateFormulas
     * @param bool   $formatData
     */
    public function toCollection($import, int $startRow = null, $nullValue = null, $calculateFormulas = false, $formatData = false): Collection
    {
        $rows = $this->toArray($import, $startRow, $nullValue, $calculateFormulas, $formatData);

        return new Collection(array_map(function (array $row) {
            return new Collection($row);
        }, $rows));
    }

    /**
     * @param object $sheetExport
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function close($sheetExport)
    {
        $this->exportable = $sheetExport;

        if ($sheetExport instanceof WithColumnFormatting) {
            foreach ($sheetExport->columnFormats() as $column => $format) {
                $this->formatColumn($column, $format);
            }
        }

        if ($sheetExport instanceof ShouldAutoSize) {
            $this->autoSize();
        }

        if ($sheetExport instanceof WithColumnWidths) {
            foreach ($sheetExport->columnWidths() as $column => $width) {
                $this->worksheet->getColumnDimension($column)->setAutoSize(false)->setWidth($width);
            }
        }

        if ($sheetExport instanceof WithStyles) {
            $styles = $sheetExport->styles($this->worksheet);
            if (is_array($styles)) {
                foreach ($styles as $coordinate => $coordinateStyles) {
                    if (is_numeric($coordinate)) {
                        $coordinate = 'A' . $coordinate . ':' . $this->worksheet->getHighestColumn($coordinate) . $coordinate;
                    }

                    $this->worksheet->getStyle($coordinate)->applyFromArray($coordinateStyles);
                }
            }
        }

        $this->raise(new AfterSheet($this, $this->exportable));

        $this->clearListeners();
    }

    /**
     * @param null|int $sheetIndex
     *
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function fromView(FromView $sheetExport, $sheetIndex = null)
    {
        $temporaryFile = $this->temporaryFileFactory->makeLocal(null, 'html');
        $temporaryFile->put($sheetExport->view()->render());

        $spreadsheet = $this->worksheet->getParent();

        /** @var Html $reader */
        $reader = IOFactory::createReader('Html');

        // If no sheetIndex given, insert content into the last sheet
        $reader->setSheetIndex($sheetIndex ?? $spreadsheet->getSheetCount() - 1);
        $reader->loadIntoExisting($temporaryFile->getLocalPath(), $spreadsheet);

        $temporaryFile->delete();
    }

    public function fromQuery(FromQuery $sheetExport, Worksheet $worksheet)
    {
        $sheetExport->query()->chunk($this->getChunkSize($sheetExport), function ($chunk) use ($sheetExport) {
            $this->appendRows($chunk, $sheetExport);
        });
    }

    public function fromCollection(FromCollection $sheetExport)
    {
        $this->appendRows($sheetExport->collection()->all(), $sheetExport);
    }

    public function fromArray(FromArray $sheetExport)
    {
        $this->appendRows($sheetExport->array(), $sheetExport);
    }

    public function fromIterator(FromIterator $sheetExport)
    {
        $this->appendRows($sheetExport->iterator(), $sheetExport);
    }

    public function fromGenerator(FromGenerator $sheetExport)
    {
        $this->appendRows($sheetExport->generator(), $sheetExport);
    }

    public function append(array $rows, string $startCell = null, bool $strictNullComparison = false)
    {
        if (! $startCell) {
            $startCell = 'A1';
        }

        if ($this->hasRows()) {
            $startCell = CellHelper::getColumnFromCoordinate($startCell) . ($this->worksheet->getHighestRow() + 1);
        }

        $this->worksheet->fromArray($rows, null, $startCell, $strictNullComparison);
    }

    public function autoSize()
    {
        foreach ($this->buildColumnRange('A', $this->worksheet->getHighestDataColumn()) as $col) {
            $dimension = $this->worksheet->getColumnDimension($col);

            // Only auto-size columns that have not have an explicit width.
            if (-1 == $dimension->getWidth()) {
                $dimension->setAutoSize(true);
            }
        }
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function formatColumn(string $column, string $format)
    {
        // If the column is a range, we wouldn't need to calculate the range.
        if (false !== stripos($column, ':')) {
            $this->worksheet
                ->getStyle($column)
                ->getNumberFormat()
                ->setFormatCode($format);
        } else {
            $this->worksheet
                ->getStyle($column . '1:' . $column . $this->worksheet->getHighestRow())
                ->getNumberFormat()
                ->setFormatCode($format);
        }
    }

    /**
     * @return Sheet
     */
    public function chunkSize(int $chunkSize)
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    /**
     * @return Worksheet
     */
    public function getDelegate()
    {
        return $this->worksheet;
    }

    /**
     * @param Chart|Chart[] $charts
     */
    public function addCharts($charts)
    {
        $charts = \is_array($charts) ? $charts : [$charts];

        foreach ($charts as $chart) {
            $this->worksheet->addChart($chart);
        }
    }

    /**
     * @param BaseDrawing|BaseDrawing[] $drawings
     */
    public function addDrawings($drawings)
    {
        $drawings = \is_array($drawings) ? $drawings : [$drawings];

        foreach ($drawings as $drawing) {
            $drawing->setWorksheet($this->worksheet);
        }
    }

    public function hasConcern(string $concern): string
    {
        return $this->exportable instanceof $concern;
    }

    /**
     * @param iterable $rows
     * @param object   $sheetExport
     */
    public function appendRows($rows, $sheetExport)
    {
        if (method_exists($sheetExport, 'prepareRows')) {
            $rows = $sheetExport->prepareRows($rows);
        }

        $rows = (new Collection($rows))->flatMap(function ($row) use ($sheetExport) {
            if ($sheetExport instanceof WithMapping) {
                $row = $sheetExport->map($row);
            }

            if ($sheetExport instanceof WithCustomValueBinder) {
                SpreadsheetCell::setValueBinder($sheetExport);
            }

            return ArrayHelper::ensureMultipleRows(
                static::mapArraybleRow($row)
            );
        })->toArray();

        $this->append(
            $rows,
            $sheetExport instanceof WithCustomStartCell ? $sheetExport->startCell() : null,
            $this->hasStrictNullComparison($sheetExport)
        );
    }

    /**
     * @param mixed $row
     */
    public static function mapArraybleRow($row): array
    {
        // When dealing with eloquent models, we'll skip the relations
        // as we won't be able to display them anyway.
        if (is_object($row) && method_exists($row, 'attributesToArray')) {
            return $row->attributesToArray();
        }

        if ($row instanceof Arrayable) {
            return $row->toArray();
        }

        // Convert StdObjects to arrays
        if (is_object($row)) {
            return json_decode(json_encode($row), true);
        }

        return $row;
    }

    /**
     * @param $sheetImport
     */
    public function getStartRow($sheetImport): int
    {
        return HeadingRowExtractor::determineStartRow($sheetImport);
    }

    /**
     * Disconnect the sheet.
     */
    public function disconnect()
    {
        $this->worksheet->disconnectCells();
        unset($this->worksheet);
    }

    /**
     * @param mixed $rows
     *
     * @return array|Collection
     */
    protected function validated(WithValidation $import, int $startRow, $rows)
    {
        $toValidate = (new Collection($rows))->mapWithKeys(function ($row, $index) use ($startRow) {
            return [($startRow + $index) => $row];
        });

        try {
            app(RowValidator::class)->validate($toValidate->toArray(), $import);
        } catch (RowSkippedException $e) {
            foreach ($e->skippedRows() as $row) {
                unset($rows[$row - $startRow]);
            }
        }

        return $rows;
    }

    /**
     * @return \Generator
     */
    protected function buildColumnRange(string $lower, string $upper)
    {
        ++$upper;
        for ($i = $lower; $i !== $upper; ++$i) {
            yield $i;
        }
    }

    private function hasRows(): bool
    {
        $startCell = 'A1';
        if ($this->exportable instanceof WithCustomStartCell) {
            $startCell = $this->exportable->startCell();
        }

        return $this->worksheet->cellExists($startCell);
    }

    /**
     * @param object $sheetExport
     */
    private function hasStrictNullComparison($sheetExport): bool
    {
        if ($sheetExport instanceof WithStrictNullComparison) {
            return true;
        }

        return config('excel.exports.strict_null_comparison', false);
    }

    /**
     * @param object|WithCustomChunkSize $export
     */
    private function getChunkSize($export): int
    {
        if ($export instanceof WithCustomChunkSize) {
            return $export->chunkSize();
        }

        return $this->chunkSize;
    }

    /**
     * @param object|WithValidation $import
     *
     * @return null|Closure
     */
    private function getPreparationCallback($import)
    {
        if (! $import instanceof WithValidation || ! method_exists($import, 'prepareForValidation')) {
            return null;
        }

        return function (array $data, int $index) use ($import) {
            return $import->prepareForValidation($data, $index);
        };
    }
}
