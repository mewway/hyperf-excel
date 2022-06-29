<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Events;

use Huanhyperf\Excel\Writer;

class BeforeExport extends Event
{
    /**
     * @var Writer
     */
    public $writer;

    /**
     * @var object
     */
    private $exportable;

    /**
     * @param object $exportable
     */
    public function __construct(Writer $writer, $exportable)
    {
        $this->writer = $writer;
        $this->exportable = $exportable;
    }

    public function getWriter(): Writer
    {
        return $this->writer;
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
        return $this->writer;
    }
}
