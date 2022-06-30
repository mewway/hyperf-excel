<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Exceptions;

use Exception;
use Throwable;

class UnreadableFileException extends Exception implements HyperfExcelException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct(
        $message = 'File could not be read',
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
