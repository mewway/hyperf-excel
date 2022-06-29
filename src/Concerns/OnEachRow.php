<?php

namespace Huanhyperf\Excel\Concerns;

use Huanhyperf\Excel\Row;

interface OnEachRow
{
    /**
     * @param  Row  $row
     */
    public function onRow(Row $row);
}
