<?php

namespace Huanhyperf\Excel\Concerns;

use Hyperf\Utils\Collection;

interface FromCollection
{
    /**
     * @return Collection
     */
    public function collection();
}
