<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Events;

use Huanhyperf\Excel\Sheet;

class AfterSheet extends Event
{
    /**
     * @var Sheet
     */
    public $sheet;

    /**
     * @var object
     */
    private $exportable;

    /**
     * @param object $exportable
     */
    public function __construct(Sheet $sheet, $exportable)
    {
        $this->sheet = $sheet;
        $this->exportable = $exportable;
    }

    public function getSheet(): Sheet
    {
        return $this->sheet;
    }

    /**
     * @return object
     */
    public function getConcernable()
    {
        return $this->exportable;
    }

    /**
     * @return mixed
     */
    public function getDelegate()
    {
        return $this->sheet;
    }
}
