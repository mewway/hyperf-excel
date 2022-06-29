<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Mixins;

use Huanhyperf\Excel\Concerns\Exportable;
use Huanhyperf\Excel\Concerns\FromCollection;
use Huanhyperf\Excel\Concerns\WithHeadings;
use Hyperf\Utils\Collection;

class StoreCollection
{
    /**
     * @return callable
     */
    public function storeExcel()
    {
        return function (string $filePath, string $disk = null, string $writerType = null, $withHeadings = false) {
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

                public function __construct(Collection $collection, bool $withHeadings = false)
                {
                    $this->collection = $collection->toBase();
                    $this->withHeadings = $withHeadings;
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

                    return is_array($first = $this->collection->first())
                        ? $this->collection->collapse()->keys()->all()
                        : array_keys($first->toArray());
                }
            };

            return $export->store($filePath, $disk, $writerType);
        };
    }
}
