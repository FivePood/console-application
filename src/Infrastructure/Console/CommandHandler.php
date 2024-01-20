<?php

declare(strict_types=1);

namespace Services\Infrastructure\Console;

use Exception;
use Migrations\Migrate;
use Services\Application\Api\CommandInterface;
use Services\Domain\Api\ConnectionInterface;
use Services\Infrastructure\Api\CommandHandlerInterface;
use Services\Infrastructure\Output\Show;

class CommandHandler implements CommandHandlerInterface
{
    private CommandInterface $command;
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection, CommandInterface $command)
    {
        $this->connection = $connection;
        $this->command = $command;
    }

    /** @throws Exception */
    public function parseCommand(array $arrayArgument): void
    {
        if (count($arrayArgument) > 1) {
            if ($arrayArgument[1] === DefaultCommand::MIGRATE) {
                new Migrate($this->connection);
            } elseif (trim(string: $arrayArgument[2], characters: '{}') === DefaultCommand::HELP) {
                $command = $this->command->getCommand($arrayArgument[1]);
                Show::viewCommand($arrayArgument[1], $command);
            } else {
                $this->processCommand($arrayArgument);
            }
        } else {
            $list = $this->command->getList();
            Show::viewList($list);
        }
    }

    /** @throws Exception */
    private function processCommand(array $arrayArgument): void
    {
        $currentCommand = $this->command->getCommand($arrayArgument[1]);
        if ($currentCommand && array_key_exists(3, $currentCommand) && $currentCommand[3]) {
            Show::viewExists($arrayArgument[1], $currentCommand);
        } else {
            $arguments = [];
            $options = [];
            foreach ($arrayArgument as $key => $value) {
                if ($key === 0 || $key === 1) {
                    continue;
                }
                if (preg_match(pattern: "/[\[\]]/", subject: $value)) {
                    $parameters = explode(separator: "=", string: trim(string: $value, characters: '[]'));
                    $options[$parameters[0]][$key] = trim(string: $parameters[1], characters: '{}');
                    continue;
                }
                $arguments[] = trim(string: $value, characters: '{}');
            }
            $this->command->upsertCommand($arrayArgument[1], $arguments, $options);
            Show::viewAdding($arrayArgument[1]);
        }
    }
}
