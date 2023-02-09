<?php

namespace Command;

interface CommandInterface
{
    public function getList(): array;

    public function getCommand(string $commandName): array;

    public function upsertCommand(string $commandName, array $arguments, array $options): void;
}
