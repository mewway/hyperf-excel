<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Validators;

use Huanhyperf\Excel\Concerns\SkipsOnFailure;
use Huanhyperf\Excel\Concerns\WithValidation;
use Huanhyperf\Excel\Exceptions\RowSkippedException;
use Hyperf\Utils\Str;
use Hyperf\Validation\ValidationException as HyperfValidationException;
use Hyperf\Validation\ValidatorFactory as Factory;

class RowValidator
{
    /**
     * @var Factory
     */
    private $validator;

    public function __construct(Factory $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @throws ValidationException
     * @throws RowSkippedException
     */
    public function validate(array $rows, WithValidation $import)
    {
        $rules = $this->rules($import);
        $messages = $this->messages($import);
        $attributes = $this->attributes($import);

        try {
            $validator = $this->validator->make($rows, $rules, $messages, $attributes);

            if (method_exists($import, 'withValidator')) {
                $import->withValidator($validator);
            }

            $validator->validate();
        } catch (HyperfValidationException $e) {
            $failures = [];
            foreach ($e->errors() as $attribute => $messages) {
                $row = strtok($attribute, '.');
                $attributeName = strtok('');
                $attributeName = $attributes['*.' . $attributeName] ?? $attributeName;

                $failures[] = new Failure(
                    $row,
                    $attributeName,
                    str_replace($attribute, $attributeName, $messages),
                    $rows[$row] ?? []
                );
            }

            if ($import instanceof SkipsOnFailure) {
                $import->onFailure(...$failures);

                throw new RowSkippedException(...$failures);
            }

            throw new ValidationException(
                $e,
                $failures
            );
        }
    }

    private function messages(WithValidation $import): array
    {
        return method_exists($import, 'customValidationMessages')
            ? $this->formatKey($import->customValidationMessages())
            : [];
    }

    private function attributes(WithValidation $import): array
    {
        return method_exists($import, 'customValidationAttributes')
            ? $this->formatKey($import->customValidationAttributes())
            : [];
    }

    private function rules(WithValidation $import): array
    {
        return $this->formatKey($import->rules());
    }

    private function formatKey(array $elements): array
    {
        return collect($elements)->mapWithKeys(function ($rule, $attribute) {
            $attribute = Str::startsWith($attribute, '*.') ? $attribute : '*.' . $attribute;

            return [$attribute => $this->formatRule($rule)];
        })->all();
    }

    /**
     * @param array|callable|object|string $rules
     *
     * @return array|string
     */
    private function formatRule($rules)
    {
        if (is_array($rules)) {
            foreach ($rules as $rule) {
                $formatted[] = $this->formatRule($rule);
            }

            return $formatted ?? [];
        }

        if (is_object($rules) || is_callable($rules)) {
            return $rules;
        }

        if (Str::contains($rules, 'required_if') && preg_match('/(.*):(.*),(.*)/', $rules, $matches)) {
            $column = Str::startsWith($matches[2], '*.') ? $matches[2] : '*.' . $matches[2];

            return $matches[1] . ':' . $column . ',' . $matches[3];
        }

        return $rules;
    }
}