<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace HyperfTest\Import;

use Huanhyperf\Excel\Concerns\ToArray;
use Huanhyperf\Excel\Concerns\WithHeadingRow;

class TestImport implements ToArray, WithHeadingRow
{
    public function array(array $array)
    {
        $res = [];
        foreach ($array as $item) {
            $res[] = [
                'mobile' => $item['手机（必填）'],
                'name' => $item['账号名称'],
                'role' => $item['角色'],
            ];
        }
        return $res;
    }

    public function headingRow(): int
    {
        return 2;
    }
}
