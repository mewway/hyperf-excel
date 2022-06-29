<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

use Huanhyperf\Excel\Concerns\ToArray;
use Huanhyperf\Excel\Concerns\ToCollection;
use Huanhyperf\Excel\Concerns\ToModel;
use Huanhyperf\Excel\Concerns\WithCalculatedFormulas;
use Huanhyperf\Excel\Concerns\WithFormatData;
use Huanhyperf\Excel\Concerns\WithMappedCells;
use Hyperf\Utils\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MappedReader
{
    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function map(WithMappedCells $import, Worksheet $worksheet)
    {
        $mapped = [];
        foreach ($import->mapping() as $name => $coordinate) {
            $cell = Cell::make($worksheet, $coordinate);

            $mapped[$name] = $cell->getValue(
                null,
                $import instanceof WithCalculatedFormulas,
                $import instanceof WithFormatData
            );
        }

        if ($import instanceof ToModel) {
            $model = $import->model($mapped);

            if ($model) {
                $model->saveOrFail();
            }
        }

        if ($import instanceof ToCollection) {
            $import->collection(new Collection($mapped));
        }

        if ($import instanceof ToArray) {
            $import->array($mapped);
        }
    }
}
