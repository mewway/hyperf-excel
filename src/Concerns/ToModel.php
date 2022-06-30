<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Concerns;

use Hyperf\Database\Model\Model;

interface ToModel
{
    /**
     * @return null|Model|Model[]
     */
    public function model(array $row);
}
