<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Events;

use Huanhyperf\Excel\Reader;

class AfterImport extends Event
{
    /**
     * @var Reader
     */
    public $reader;

    /**
     * @var object
     */
    private $importable;

    /**
     * @param object $importable
     */
    public function __construct(Reader $reader, $importable)
    {
        $this->reader = $reader;
        $this->importable = $importable;
    }

    public function getReader(): Reader
    {
        return $this->reader;
    }

    /**
     * @return object
     */
    public function getConcernable()
    {
        return $this->importable;
    }

    /**
     * @return mixed
     */
    public function getDelegate()
    {
        return $this->reader;
    }
}
