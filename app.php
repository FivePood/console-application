<?php

use Services\Application\Command\Service\Command;
use Services\Domain\Database\Service\Connection;
use Services\Domain\Database\Service\Params;
use Services\Infrastructure\Console\Service\CommandHandler;
use Services\Infrastructure\Output\Service\Show;

require_once __DIR__ . '/vendor/autoload.php';

try {
    $conn = new Connection(new Params());
    $do = new CommandHandler($conn, new Command($conn), new Show());
    $do->parseCommand($argv);
} catch (Exception $e) {
    echo "\nRuntime error. {$e->getMessage()}\n";
}
echo "\n";
