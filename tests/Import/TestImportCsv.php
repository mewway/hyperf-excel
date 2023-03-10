<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace HyperfTest\Import;

use Huanhyperf\Excel\Concerns\ToArray;
use Huanhyperf\Excel\Concerns\WithHeadingRow;

class TestImportCsv implements ToArray, WithHeadingRow
{
    public function array(array $array)
    {
        $res = [];
        foreach ($array as $item) {
            // 商品id,商品名称,市场价,原始数据
            $res[] = [
                'item_id' => $item['商品id'],
                'name' => $item['商品名称'],
                'price' => $item['市场价'],
                'raw' => $item['原始数据'],
            ];
        }

        return $res;
    }

    public function headingRow(): int
    {
        return 1;
    }
}
