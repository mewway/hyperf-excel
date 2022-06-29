<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Concerns;

use Hyperf\Utils\Collection;
use Throwable;

trait SkipsErrors
{
    /**
     * @var Throwable[]
     */
    protected $errors = [];

    public function onError(Throwable $e)
    {
        $this->errors[] = $e;
    }

    /**
     * @return Collection|Throwable[]
     */
    public function errors(): Collection
    {
        return new Collection($this->errors);
    }
}
