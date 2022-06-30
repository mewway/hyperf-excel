<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Concerns;

use PhpOffice\PhpSpreadsheet\Style\Color;

interface WithBackgroundColor
{
    /**
     * @return array|Color|string
     */
    public function backgroundColor();
}
