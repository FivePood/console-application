<?php

namespace Services\Infrastructure\Output\Api;

interface ShowInterface
{
    public function viewCommand(string $commandName, array $command): void;

    public function viewList(array $list): void;

    public function viewExists(string $commandName, array $command): void;

    public function viewAdding(string $commandName): void;
}