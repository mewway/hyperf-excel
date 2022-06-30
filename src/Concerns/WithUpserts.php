<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Concerns;

interface WithUpserts
{
    /**
     * @return array|string
     */
    public function uniqueBy();
}
