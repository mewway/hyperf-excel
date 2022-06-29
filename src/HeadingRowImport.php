<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

use Huanhyperf\Excel\Concerns\Importable;
use Huanhyperf\Excel\Concerns\WithLimit;
use Huanhyperf\Excel\Concerns\WithMapping;
use Huanhyperf\Excel\Concerns\WithStartRow;
use Huanhyperf\Excel\Imports\HeadingRowFormatter;

class HeadingRowImport implements WithStartRow, WithLimit, WithMapping
{
    use Importable;

    /**
     * @var int
     */
    private $headingRow;

    public function __construct(int $headingRow = 1)
    {
        $this->headingRow = $headingRow;
    }

    public function startRow(): int
    {
        return $this->headingRow;
    }

    public function limit(): int
    {
        return 1;
    }

    /**
     * @param mixed $row
     */
    public function map($row): array
    {
        return HeadingRowFormatter::format($row);
    }
}
