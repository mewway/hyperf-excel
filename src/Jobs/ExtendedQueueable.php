<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Jobs;

use Huanhyperf\Excel\Jobs\Traits\Queueable;

trait ExtendedQueueable
{
    use Queueable {
        chain as originalChain;
    }

    /**
     * @param $chain
     *
     * @return $this
     */
    public function chain($chain)
    {
        collect($chain)->each(function ($job) {
            $serialized = method_exists($this, 'serializeJob') ? $this->serializeJob($job) : serialize($job);
            $this->chained[] = $serialized;
        });

        return $this;
    }
}
