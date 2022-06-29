<?php

namespace Huanhyperf\Excel\Concerns;

use Huanhyperf\Excel\Validators\Failure;

interface SkipsOnFailure
{
    /**
     * @param  Failure[]  $failures
     */
    public function onFailure(Failure ...$failures);
}
