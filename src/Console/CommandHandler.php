<?php

namespace Console;

use Exception;
use migrations\Migrate;
use Command\Command;
use Command\CommandInterface;
use Command\DefaultCommand;
use Output\Show;
use Output\ShowInterface;

class CommandHandler
{
    private CommandInterface $command;
    private ShowInterface $show;

    public function __construct()
    {
        $this->command = new Command();
        $this->show = new Show();
    }

    /** @throws Exception */
    public function parseCommand(array $arrayArgument): void
    {
        if (count($arrayArgument) > 1) {
            if ($arrayArgument[1] === DefaultCommand::MIGRATE) {
                new Migrate();
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
