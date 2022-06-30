<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Exceptions;

use InvalidArgumentException;
use Throwable;

class NoFilenameGivenException extends InvalidArgumentException implements HyperfExcelException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct(
        $message = 'A filename needs to be passed in order to download the export',
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
