<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Filters;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    /**
     * @var int
     */
    private $headingRow;

    /**
     * @var int
     */
    private $startRow;

    /**
     * @var int
     */
    private $endRow;

    /**
     * @var string
     */
    private $worksheetName;

    public function __construct(int $headingRow, int $startRow, int $chunkSize, string $worksheetName)
    {
        $this->headingRow = $headingRow;
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
        $this->worksheetName = $worksheetName;
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
        //  Only read the heading row, and the rows that are configured in $this->_startRow and $this->_endRow
        return ($worksheetName === $this->worksheetName || '' === $worksheetName)
            && ($row === $this->headingRow || ($row >= $this->startRow && $row < $this->endRow));
    }
}
