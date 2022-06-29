<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Filters;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class LimitFilter implements IReadFilter
{
    /**
     * @var int
     */
    private $startRow;

    /**
     * @var int
     */
    private $endRow;

    public function __construct(int $startRow, int $limit)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $limit;
    }

    /**
     * @param string $column
     * @param int    $row
     * @param string $worksheetName
     *
     * @return bool
     */
    public function readCell($column, $row, $worksheetName = '')
    {
        return $row >= $this->startRow && $row < $this->endRow;
    }
}
