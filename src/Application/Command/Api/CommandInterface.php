<?php declare(strict_types=1);

namespace Command\Api;

interface CommandInterface
{
    public function getList(): array;

    public function getCommand(string $commandName): array;

    public function upsertCommand(string $commandName, array $arguments, array $options): void;
}
