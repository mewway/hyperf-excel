<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Mixins;

use Huanhyperf\Excel\Concerns\Exportable;
use Huanhyperf\Excel\Concerns\FromCollection;
use Huanhyperf\Excel\Concerns\WithHeadings;
use Huanhyperf\Excel\Sheet;
use Hyperf\Utils\Collection;
use Hyperf\Utils\Contracts\Arrayable;

class DownloadCollection
{
    /**
     * @return callable
     */
    public function downloadExcel()
    {
        return function (string $fileName, string $writerType = null, $withHeadings = false, array $responseHeaders = []) {
            $export = new class($this, $withHeadings) implements FromCollection, WithHeadings {
                use Exportable;

                /**
                 * @var bool
                 */
                private $withHeadings;

                /**
                 * @var Collection
                 */
                private $collection;

                public function __construct(Collection $collection, bool $withHeading = false)
                {
                    $this->collection = $collection->toBase();
                    $this->withHeadings = $withHeading;
                }

                /**
                 * @return Collection
                 */
                public function collection()
                {
                    return $this->collection;
                }

                public function headings(): array
                {
                    if (! $this->withHeadings) {
                        return [];
                    }

                    $firstRow = $this->collection->first();

                    if ($firstRow instanceof Arrayable || \is_object($firstRow)) {
                        return array_keys(Sheet::mapArraybleRow($firstRow));
                    }

                    return $this->collection->collapse()->keys()->all();
                }
            };

            return $export->download($fileName, $writerType, $responseHeaders);
        };
    }
}
