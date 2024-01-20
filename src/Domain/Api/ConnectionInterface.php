<?php

declare(strict_types=1);

namespace Services\Domain\Api;

use mysqli_result;

interface ConnectionInterface
{
    public function connect(): ConnectionInterface;

    public function request(): mysqli_result|bool;

    public function getUser(): string;

    public function getPassword(): string;

    public function getHost(): string;

    public function getDBName(): string;

    public function getVersion(): string;

    public function getOne(): mixed;

    public function getAll(): array;
}
