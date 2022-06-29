<?php

namespace Huanhyperf\Excel\Jobs;

use Huanhyperf\Excel\Concerns\ShouldQueue;
use Huanhyperf\Excel\Jobs\Traits\Dispatchable;

class QueueImport implements ShouldQueue
{
    use ExtendedQueueable, Dispatchable;

    /**
     * @var int
     */
    public $tries;

    /**
     * @var int
     */
    public $timeout;

    /**
     * @param  ShouldQueue  $import
     */
    public function __construct(ShouldQueue $import = null)
    {
        if ($import) {
            $this->timeout = $import->timeout ?? null;
            $this->tries   = $import->tries ?? null;
        }
    }

    public function handle()
    {
        //
    }
}
