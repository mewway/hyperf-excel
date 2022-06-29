<?php

namespace Huanhyperf\Excel\Validators;

use Hyperf\Validation\ValidationException as HyperfValidationException;

class ValidationException extends HyperfValidationException
{
    /**
     * @var Failure[]
     */
    protected $failures;

    /**
     * @param  HyperfValidationException  $previous
     * @param  array  $failures
     */
    public function __construct(HyperfValidationException $previous, array $failures)
    {
        parent::__construct($previous->validator, $previous->response, $previous->errorBag);
        $this->failures = $failures;
    }

    /**
     * @return string[]
     */
    public function errors(): array
    {
        return collect($this->failures)->map->toArray()->all();
    }

    /**
     * @return array
     */
    public function failures(): array
    {
        return $this->failures;
    }
}
