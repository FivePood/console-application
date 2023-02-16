<?php declare(strict_types=1);

namespace Output;

class Show
{
    public function viewCommand(string $commandName, array $command): void
    {
        if (!empty($command)) {
            list($arguments, $options, $optionsMeanings) = $command;
            echo "Called command: $commandName\n";

            $stringArguments = null;
            if ($arguments) {
                foreach ($arguments as $argument) {
                    if ($argument) {
                        $stringArguments .= "     -  $argument\n";
                    }
                }
            }
            echo $stringArguments ? "   Arguments:\n" . $stringArguments : "    No arguments\n";

            $stringOptions = null;
            if ($options) {
                foreach ($options as $optionId => $optionName) {
                    if ($optionName) {
                        $stringOptions .= "     -  $optionName\n";
                        if (isset($optionId) && array_key_exists($optionId, $optionsMeanings)) {
                            foreach ($optionsMeanings[$optionId] as $meaning) {
                                $stringOptions .= "        -  $meaning\n";
                            }
                        }
                    }
                }
            }
            echo $stringOptions ? "   Options:\n" . $stringOptions : "   No options\n";
        } else {
            echo "Command $commandName is not in the database.\n";
        }
    }

    public function viewList(array $list): void
    {
        echo "Command list:\n";
        foreach ($list as $command) {
            echo "  {$command["commandId"]}. {$command["commandName"]}\n";
        }
    }

    public function viewExists(string $commandName, array $command): void
    {
        echo "Command $commandName already exists.\n";
        $this->viewCommand($commandName, $command);
    }

    public function viewAdding(string $commandName): void
    {
        echo "Command $commandName added.\n";
    }
}
