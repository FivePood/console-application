<?php

declare(strict_types=1);

namespace Services\Application\Command\Api;

interface CommandInterface
{
    public function getList(): array;

    /**
     * @param string $commandName
     * @return array[string[]]
     */
    public function getCommand(string $commandName): array;

    public function upsertCommand(string $commandName, array $arguments, array $options): void;
}
