<?php

namespace Services\Domain\Database\Api;

interface ParamsInterface
{
    /**
     * @return string[]
     */
    public function get(): array;
}