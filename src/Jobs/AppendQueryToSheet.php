<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Jobs;

use Huanhyperf\Excel\Concerns\FromQuery;
use Huanhyperf\Excel\Concerns\ShouldQueue;
use Huanhyperf\Excel\Files\TemporaryFile;
use Huanhyperf\Excel\Jobs\Middleware\LocalizeJob;
use Huanhyperf\Excel\Jobs\Traits\Dispatchable;
use Huanhyperf\Excel\Jobs\Traits\InteractsWithQueue;
use Huanhyperf\Excel\Jobs\Traits\Queueable;
use Huanhyperf\Excel\Writer;

class AppendQueryToSheet implements ShouldQueue
{
    use Queueable;
    use Dispatchable;
    use ProxyFailures;
    use InteractsWithQueue;

    /**
     * @var TemporaryFile
     */
    public $temporaryFile;

    /**
     * @var string
     */
    public $writerType;

    /**
     * @var int
     */
    public $sheetIndex;

    /**
     * @var FromQuery
     */
    public $sheetExport;

    /**
     * @var int
     */
    public $page;

    /**
     * @var int
     */
    public $chunkSize;

    public function __construct(
        FromQuery $sheetExport,
        TemporaryFile $temporaryFile,
        string $writerType,
        int $sheetIndex,
        int $page,
        int $chunkSize
    ) {
        $this->sheetExport = $sheetExport;
        $this->temporaryFile = $temporaryFile;
        $this->writerType = $writerType;
        $this->sheetIndex = $sheetIndex;
        $this->page = $page;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Get the middleware the job should be dispatched through.
     *
     * @return array
     */
    public function middleware()
    {
        return (method_exists($this->sheetExport, 'middleware')) ? $this->sheetExport->middleware() : [];
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function handle(Writer $writer)
    {
        (new LocalizeJob($this->sheetExport))->handle($this, function () use ($writer) {
            $writer = $writer->reopen($this->temporaryFile, $this->writerType);

            $sheet = $writer->getSheetByIndex($this->sheetIndex);

            $query = $this->sheetExport->query()->forPage($this->page, $this->chunkSize);

            $sheet->appendRows($query->get(), $this->sheetExport);

            $writer->write($this->sheetExport, $this->temporaryFile, $this->writerType);
        });
    }
}
