<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Exceptions;

use Exception;
use Huanhyperf\Excel\Validators\Failure;
use Hyperf\Utils\Collection;

class RowSkippedException extends Exception
{
    /**
     * @var Failure[]
     */
    private $failures;

    public function __construct(Failure ...$failures)
    {
        $this->failures = $failures;

        parent::__construct();
    }

    /**
     * @return Collection|Failure[]
     */
    public function failures(): Collection
    {
        return new Collection($this->failures);
    }

    /**
     * @return int[]
     */
    public function skippedRows(): array
    {
        return $this->failures()->map->row()->all();
    }
}
