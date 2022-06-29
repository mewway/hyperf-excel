<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

use Huanhyperf\Excel\Concerns\ShouldQueue;
use Huanhyperf\Excel\Concerns\ShouldQueueWithoutChain;
use Huanhyperf\Excel\Concerns\WithChunkReading;
use Huanhyperf\Excel\Concerns\WithEvents;
use Huanhyperf\Excel\Concerns\WithLimit;
use Huanhyperf\Excel\Concerns\WithProgressBar;
use Huanhyperf\Excel\Events\BeforeImport;
use Huanhyperf\Excel\Files\TemporaryFile;
use Huanhyperf\Excel\Imports\HeadingRowExtractor;
use Huanhyperf\Excel\Jobs\AfterImportJob;
use Huanhyperf\Excel\Jobs\QueueImport;
use Huanhyperf\Excel\Jobs\ReadChunk;
use Hyperf\Utils\Collection;
use Illuminate\Foundation\Bus\PendingDispatch;
use Throwable;

class ChunkReader
{
    /**
     * @return null|\Illuminate\Foundation\Bus\PendingDispatch
     */
    public function read(WithChunkReading $import, Reader $reader, TemporaryFile $temporaryFile)
    {
        if ($import instanceof WithEvents && isset($import->registerEvents()[BeforeImport::class])) {
            $reader->beforeImport($import);
        }

        $chunkSize = $import->chunkSize();
        $totalRows = $reader->getTotalRows();
        $worksheets = $reader->getWorksheets($import);
        $queue = property_exists($import, 'queue') ? $import->queue : null;
        $delayCleanup = property_exists($import, 'delayCleanup') ? $import->delayCleanup : 600;

        if ($import instanceof WithProgressBar) {
            $import->getConsoleOutput()->progressStart(array_sum($totalRows));
        }

        $jobs = new Collection();
        foreach ($worksheets as $name => $sheetImport) {
            $startRow = HeadingRowExtractor::determineStartRow($sheetImport);

            if ($sheetImport instanceof WithLimit) {
                $limit = $sheetImport->limit();

                if ($limit <= $totalRows[$name]) {
                    $totalRows[$name] = $sheetImport->limit();
                }
            }

            for ($currentRow = $startRow; $currentRow <= $totalRows[$name]; $currentRow += $chunkSize) {
                $jobs->push(new ReadChunk(
                    $import,
                    $reader->getPhpSpreadsheetReader(),
                    $temporaryFile,
                    $name,
                    $sheetImport,
                    $currentRow,
                    $chunkSize
                ));
            }
        }

        $afterImportJob = new AfterImportJob($import, $reader);

        if ($import instanceof ShouldQueueWithoutChain) {
            $jobs->push($afterImportJob->delay($delayCleanup));

            return $jobs->each(function ($job) use ($queue) {
                dispatch($job->onQueue($queue));
            });
        }

        $jobs->push($afterImportJob);

        if ($import instanceof ShouldQueue) {
            return new PendingDispatch(
                (new QueueImport($import))->chain($jobs->toArray())
            );
        }

        $jobs->each(function ($job) {
            try {
                dispatch_now($job);
            } catch (Throwable $e) {
                if (method_exists($job, 'failed')) {
                    $job->failed($e);
                }

                throw $e;
            }
        });

        if ($import instanceof WithProgressBar) {
            $import->getConsoleOutput()->progressFinish();
        }

        unset($jobs);

        return null;
    }
}
