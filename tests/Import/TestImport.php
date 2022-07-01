<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace HyperfTest\Import;

use Huanhyperf\Excel\Concerns\ToArray;
use Huanhyperf\Excel\Concerns\WithHeadingRow;

class TestImport implements ToArray, WithHeadingRow
{
    public function array(array $array)
    {
        return [
            'mobile' => $array['mobile'],
            'name' => $array['name'],
            'role' => $array['role'],
        ];
    }

    public function headingRow(): int
    {
        return 5;
    }
}
