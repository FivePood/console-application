<?php

namespace Services\Infrastructure\Console\Api;

interface CommandHandlerInterface
{
    public function parseCommand(array $arrayArgument): void;
}