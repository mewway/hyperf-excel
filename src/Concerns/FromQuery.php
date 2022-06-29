<?php

namespace Huanhyperf\Excel\Concerns;

use Hyperf\Database\Model\Builder as EloquentBuilder;
use Hyperf\Database\Model\Relations\Relation;
use Hyperf\Database\Query\Builder;

interface FromQuery
{
    /**
     * @return Builder|EloquentBuilder|Relation
     */
    public function query();
}
