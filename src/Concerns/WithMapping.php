<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Concerns;

interface WithMapping
{
    /**
     * @param mixed $row
     */
    public function map($row): array;
}
