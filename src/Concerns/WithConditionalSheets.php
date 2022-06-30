<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Concerns;

trait WithConditionalSheets
{
    /**
     * @var array
     */
    protected $conditionallySelectedSheets = [];

    /**
     * @param array|string $sheets
     *
     * @return $this
     */
    public function onlySheets($sheets)
    {
        $this->conditionallySelectedSheets = is_array($sheets) ? $sheets : func_get_args();

        return $this;
    }

    public function sheets(): array
    {
        return \array_filter($this->conditionalSheets(), function ($name) {
            return \in_array($name, $this->conditionallySelectedSheets, false);
        }, ARRAY_FILTER_USE_KEY);
    }

    abstract public function conditionalSheets(): array;
}
