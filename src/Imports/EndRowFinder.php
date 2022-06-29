<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Imports;

use Huanhyperf\Excel\Concerns\WithLimit;

class EndRowFinder
{
    /**
     * @param object|WithLimit $import
     *
     * @return null|int
     */
    public static function find($import, int $startRow = null, int $highestRow = null)
    {
        if (! $import instanceof WithLimit) {
            return null;
        }

        $limit = $import->limit();

        if ($limit > $highestRow) {
            return null;
        }

        // When no start row given,
        // use the first row as start row.
        $startRow ??= 1;

        // Subtract 1 row from the start row, so a limit
        // of 1 row, will have the same start and end row.
        return ($startRow - 1) + $limit;
    }
}
