<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel\Concerns;

trait RemembersChunkOffset
{
    /**
     * @var null|int
     */
    protected $chunkOffset;

    public function setChunkOffset(int $chunkOffset)
    {
        $this->chunkOffset = $chunkOffset;
    }

    /**
     * @return null|int
     */
    public function getChunkOffset()
    {
        return $this->chunkOffset;
    }
}
