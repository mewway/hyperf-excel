<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Jobs;

use Huanhyperf\Excel\Concerns\FromView;
use Huanhyperf\Excel\Concerns\ShouldQueue;
use Huanhyperf\Excel\Files\TemporaryFile;
use Huanhyperf\Excel\Jobs\Middleware\LocalizeJob;
use Huanhyperf\Excel\Jobs\Traits\Dispatchable;
use Huanhyperf\Excel\Jobs\Traits\InteractsWithQueue;
use Huanhyperf\Excel\Jobs\Traits\Queueable;
use Huanhyperf\Excel\Writer;

class AppendViewToSheet implements ShouldQueue
{
    use Queueable;
    use Dispatchable;
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
     * @var FromView
     */
    public $sheetExport;

    /**
     * @param array $data
     */
    public function __construct(FromView $sheetExport, TemporaryFile $temporaryFile, string $writerType, int $sheetIndex)
    {
        $this->sheetExport = $sheetExport;
        $this->temporaryFile = $temporaryFile;
        $this->writerType = $writerType;
        $this->sheetIndex = $sheetIndex;
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

            $sheet->fromView($this->sheetExport, $this->sheetIndex);

            $writer->write($this->sheetExport, $this->temporaryFile, $this->writerType);
        });
    }
}
