<?php

namespace Huanhyperf\Excel\Concerns;

use Hyperf\Utils\Collection;

interface ToCollection
{
    /**
     * @param  Collection  $collection
     */
    public function collection(Collection $collection);
}
