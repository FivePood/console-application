<?php

require_once __DIR__ . '/vendor/autoload.php';

try {
    $do = new Console\CommandHandler();
    $do->parseCommand($argv);
} catch (Exception $e) {
    echo "\nRuntime error. {$e->getMessage()}\n";
}
echo "\n";
