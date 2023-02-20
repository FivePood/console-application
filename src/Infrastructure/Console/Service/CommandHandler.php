<?php

declare(strict_types=1);

namespace Services\Infrastructure\Console\Service;

use Exception;
use Migrations\Migrate;
use Services\Application\Command\Api\CommandInterface;
use Services\Application\Command\Service\Command;
use Services\Domain\Database\Api\ConnectionInterface;
use Services\Domain\Database\Service\Connection;
use Services\Domain\Database\Service\Params;
use Services\Infrastructure\Console\Api\CommandHandlerInterface;
use Services\Infrastructure\Output\Api\ShowInterface;
use Services\Infrastructure\Output\Service\Show;

class CommandHandler implements CommandHandlerInterface
{
    private CommandInterface $command;
    private ShowInterface $show;
    private ConnectionInterface $connection;

    public function __construct()
    {
        $this->connection = new Connection(new Params());
        $this->command = new Command($this->connection);
        $this->show = new Show();
    }

    /** @throws Exception */
    public function parseCommand(array $arrayArgument): void
    {
        if (count($arrayArgument) > 1) {
            if ($arrayArgument[1] === DefaultCommand::MIGRATE) {
                new Migrate($this->connection);
            } elseif (trim($arrayArgument[2], '{}') === DefaultCommand::HELP) {
                $command = $this->command->getCommand($arrayArgument[1]);
                $this->show->viewCommand($arrayArgument[1], $command);
            } else {
                $this->processCommand($arrayArgument);
            }
        } else {
            $list = $this->command->getList();
            $this->show->viewList($list);
        }
    }

    /** @throws Exception */
    private function processCommand(array $arrayArgument): void
    {
        $currentCommand = $this->command->getCommand($arrayArgument[1]);
        if ($currentCommand) {
            $this->show->viewExists($arrayArgument[1], $currentCommand);
        } else {
            $arguments = [];
            $options = [];
            foreach ($arrayArgument as $key => $value) {
                if ($key === 0 || $key === 1) {
                    continue;
                }
                if (preg_match("/[\[\]]/", $value)) {
                    $parameters = explode("=", trim($value, '[]'));
                    $options[$parameters[0]][$key] = trim($parameters[1], '{}');
                    continue;
                }
                $arguments[] = trim($value, '{}');
            }
            $this->command->upsertCommand($arrayArgument[1], $arguments, $options);
            $this->show->viewAdding($arrayArgument[1]);
        }
    }
}
