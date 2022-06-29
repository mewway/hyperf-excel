<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Concerns;

use Huanhyperf\Excel\Validators\Failure;
use Hyperf\Utils\Collection;

trait SkipsFailures
{
    /**
     * @var Failure[]
     */
    protected $failures = [];

    public function onFailure(Failure ...$failures)
    {
        $this->failures = array_merge($this->failures, $failures);
    }

    /**
     * @return Collection|Failure[]
     */
    public function failures(): Collection
    {
        return new Collection($this->failures);
    }
}
