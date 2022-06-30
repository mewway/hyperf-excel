<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

use Huanhyperf\Excel\Concerns\FromCollection;
use Huanhyperf\Excel\Concerns\FromQuery;
use Huanhyperf\Excel\Concerns\WithCustomChunkSize;
use Huanhyperf\Excel\Concerns\WithCustomQuerySize;
use Huanhyperf\Excel\Concerns\WithMultipleSheets;
use Huanhyperf\Excel\Files\TemporaryFile;
use Huanhyperf\Excel\Files\TemporaryFileFactory;
use Huanhyperf\Excel\Jobs\AppendDataToSheet;
use Huanhyperf\Excel\Jobs\AppendQueryToSheet;
use Huanhyperf\Excel\Jobs\AppendViewToSheet;
use Huanhyperf\Excel\Jobs\CloseSheet;
use Huanhyperf\Excel\Jobs\QueueExport;
use Huanhyperf\Excel\Jobs\StoreQueuedExport;
use Huanhyperf\Excel\PendingDispatch;
use Hyperf\Utils\Collection;
use Traversable;

class QueuedWriter
{
    /**
     * @var Writer
     */
    protected $writer;

    /**
     * @var int
     */
    protected $chunkSize;

    /**
     * @var TemporaryFileFactory
     */
    protected $temporaryFileFactory;

    public function __construct(Writer $writer, TemporaryFileFactory $temporaryFileFactory)
    {
        $this->writer = $writer;
        $this->chunkSize = config('excel.exports.chunk_size', 1000);
        $this->temporaryFileFactory = $temporaryFileFactory;
    }

    /**
     * @param object       $export
     * @param string       $disk
     * @param array|string $diskOptions
     *
     * @return Huanhyperf\Excel\PendingDispatch
     */
    public function store($export, string $filePath, string $disk = null, string $writerType = null, $diskOptions = [])
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $temporaryFile = $this->temporaryFileFactory->make($extension);

        $jobs = $this->buildExportJobs($export, $temporaryFile, $writerType);

        $jobs->push(new StoreQueuedExport(
            $temporaryFile,
            $filePath,
            $disk,
            $diskOptions
        ));

        return new PendingDispatch(
            (new QueueExport($export, $temporaryFile, $writerType))->chain($jobs->toArray())
        );
    }

    /**
     * @param object $export
     */
    private function buildExportJobs($export, TemporaryFile $temporaryFile, string $writerType): Collection
    {
        $sheetExports = [$export];
        if ($export instanceof WithMultipleSheets) {
            $sheetExports = $export->sheets();
        }

        $jobs = new Collection();
        foreach ($sheetExports as $sheetIndex => $sheetExport) {
            if ($sheetExport instanceof FromCollection) {
                $jobs = $jobs->merge($this->exportCollection($sheetExport, $temporaryFile, $writerType, $sheetIndex));
            } elseif ($sheetExport instanceof FromQuery) {
                $jobs = $jobs->merge($this->exportQuery($sheetExport, $temporaryFile, $writerType, $sheetIndex));
            }

            $jobs->push(new CloseSheet($sheetExport, $temporaryFile, $writerType, $sheetIndex));
        }

        return $jobs;
    }

    private function exportCollection(
        FromCollection $export,
        TemporaryFile $temporaryFile,
        string $writerType,
        int $sheetIndex
    ): Collection {
        return $export
            ->collection()
            ->chunk($this->getChunkSize($export))
            ->map(function ($rows) use ($writerType, $temporaryFile, $sheetIndex, $export) {
                if ($rows instanceof Traversable) {
                    $rows = iterator_to_array($rows);
                }

                return new AppendDataToSheet(
                    $export,
                    $temporaryFile,
                    $writerType,
                    $sheetIndex,
                    $rows
                );
            });
    }

    private function exportQuery(
        FromQuery $export,
        TemporaryFile $temporaryFile,
        string $writerType,
        int $sheetIndex
    ): Collection {
        $query = $export->query();

        $count = $export instanceof WithCustomQuerySize ? $export->querySize() : $query->count();
        $spins = ceil($count / $this->getChunkSize($export));

        $jobs = new Collection();

        for ($page = 1; $page <= $spins; ++$page) {
            $jobs->push(new AppendQueryToSheet(
                $export,
                $temporaryFile,
                $writerType,
                $sheetIndex,
                $page,
                $this->getChunkSize($export)
            ));
        }

        return $jobs;
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
}
