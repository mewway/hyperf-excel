<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Imports;

use Huanhyperf\Excel\Concerns\SkipsOnError;
use Huanhyperf\Excel\Concerns\ToModel;
use Huanhyperf\Excel\Concerns\WithUpsertColumns;
use Huanhyperf\Excel\Concerns\WithUpserts;
use Huanhyperf\Excel\Concerns\WithValidation;
use Huanhyperf\Excel\Exceptions\RowSkippedException;
use Huanhyperf\Excel\Validators\RowValidator;
use Huanhyperf\Excel\Validators\ValidationException;
use Hyperf\Database\Model\Model;
use Hyperf\Utils\Collection;
use Throwable;

class ModelManager
{
    /**
     * @var array
     */
    private $rows = [];

    /**
     * @var RowValidator
     */
    private $validator;

    /**
     * @var bool
     */
    private $remembersRowNumber = false;

    public function __construct(RowValidator $validator)
    {
        $this->validator = $validator;
    }

    public function add(int $row, array $attributes)
    {
        $this->rows[$row] = $attributes;
    }

    public function setRemembersRowNumber(bool $remembersRowNumber)
    {
        $this->remembersRowNumber = $remembersRowNumber;
    }

    /**
     * @throws ValidationException
     */
    public function flush(ToModel $import, bool $massInsert = false)
    {
        if ($import instanceof WithValidation) {
            $this->validateRows($import);
        }

        if ($massInsert) {
            $this->massFlush($import);
        } else {
            $this->singleFlush($import);
        }

        $this->rows = [];
    }

    /**
     * @param null|int $rowNumber
     *
     * @return Collection|Model[]
     */
    public function toModels(ToModel $import, array $attributes, $rowNumber = null): Collection
    {
        if ($this->remembersRowNumber) {
            $import->rememberRowNumber($rowNumber);
        }

        $model = $import->model($attributes);

        if (null !== $model) {
            return \is_array($model) ? new Collection($model) : new Collection([$model]);
        }

        return new Collection([]);
    }

    private function massFlush(ToModel $import)
    {
        $this->rows()
            ->flatMap(function (array $attributes, $index) use ($import) {
                 return $this->toModels($import, $attributes, $index);
             })
            ->mapToGroups(function ($model) {
                 return [\get_class($model) => $this->prepare($model)->getAttributes()];
             })
            ->each(function (Collection $models, string $model) use ($import) {
                 try {
                     // @var Model $model

                     if ($import instanceof WithUpserts) {
                         $model::query()->upsert(
                             $models->toArray(),
                             $import->uniqueBy(),
                             $import instanceof WithUpsertColumns ? $import->upsertColumns() : null
                         );
                     } else {
                         $model::query()->insert($models->toArray());
                     }
                 } catch (Throwable $e) {
                     if ($import instanceof SkipsOnError) {
                         $import->onError($e);
                     } else {
                         throw $e;
                     }
                 }
             });
    }

    private function singleFlush(ToModel $import)
    {
        $this
            ->rows()
            ->each(function (array $attributes, $index) use ($import) {
                $this->toModels($import, $attributes, $index)->each(function (Model $model) use ($import) {
                    try {
                        if ($import instanceof WithUpserts) {
                            $model->upsert(
                                $model->getAttributes(),
                                $import->uniqueBy(),
                                $import instanceof WithUpsertColumns ? $import->upsertColumns() : null
                            );
                        } else {
                            $model->saveOrFail();
                        }
                    } catch (Throwable $e) {
                        if ($import instanceof SkipsOnError) {
                            $import->onError($e);
                        } else {
                            throw $e;
                        }
                    }
                });
            });
    }

    private function prepare(Model $model): Model
    {
        if ($model->usesTimestamps()) {
            $time = $model->freshTimestamp();

            $updatedAtColumn = $model->getUpdatedAtColumn();

            // If model has updated at column and not manually provided.
            if ($updatedAtColumn && null === $model->{$updatedAtColumn}) {
                $model->setUpdatedAt($time);
            }

            $createdAtColumn = $model->getCreatedAtColumn();

            // If model has created at column and not manually provided.
            if ($createdAtColumn && null === $model->{$createdAtColumn}) {
                $model->setCreatedAt($time);
            }
        }

        return $model;
    }

    /**
     * @throws ValidationException
     */
    private function validateRows(WithValidation $import)
    {
        try {
            $this->validator->validate($this->rows, $import);
        } catch (RowSkippedException $e) {
            foreach ($e->skippedRows() as $row) {
                unset($this->rows[$row]);
            }
        }
    }

    private function rows(): Collection
    {
        return new Collection($this->rows);
    }
}
