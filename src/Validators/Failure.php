<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Validators;

use Hyperf\Utils\Contracts\Arrayable;
use JsonSerializable;

class Failure implements Arrayable, JsonSerializable
{
    /**
     * @var int
     */
    protected $row;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var array
     */
    protected $errors;

    /**
     * @var array
     */
    private $values;

    public function __construct(int $row, string $attribute, array $errors, array $values = [])
    {
        $this->row = $row;
        $this->attribute = $attribute;
        $this->errors = $errors;
        $this->values = $values;
    }

    public function row(): int
    {
        return $this->row;
    }

    public function attribute(): string
    {
        return $this->attribute;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function values(): array
    {
        return $this->values;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return collect($this->errors)->map(function ($message) {
            return __('There was an error on row :row. :message', ['row' => $this->row, 'message' => $message]);
        })->all();
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'row' => $this->row(),
            'attribute' => $this->attribute(),
            'errors' => $this->errors(),
            'values' => $this->values(),
        ];
    }
}
