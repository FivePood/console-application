<?php

use Services\Application\Command\Command;
use Services\Domain\Database\Connection;
use Services\Infrastructure\Console\CommandHandler;

require_once __DIR__ . '/vendor/autoload.php';

try {
    $conn = new Connection();
    $do = new CommandHandler($conn, new Command($conn));
    $do->parseCommand($argv);
} catch (Exception $e) {
    echo "\nRuntime error. {$e->getMessage()}\n";
}
echo "\n";
