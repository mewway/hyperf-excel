<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Concerns;

trait RemembersRowNumber
{
    /**
     * @var int
     */
    protected $rowNumber;

    public function rememberRowNumber(int $rowNumber)
    {
        $this->rowNumber = $rowNumber;
    }

    /**
     * @return null|int
     */
    public function getRowNumber()
    {
        return $this->rowNumber;
    }
}
