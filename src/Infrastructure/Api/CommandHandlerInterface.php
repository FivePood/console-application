<?php

namespace Services\Infrastructure\Api;

interface CommandHandlerInterface
{
    public function parseCommand(array $arrayArgument): void;
}