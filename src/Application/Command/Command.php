<?php

declare(strict_types=1);

namespace Services\Application\Command;

use Exception;
use Services\Application\Api\CommandInterface;
use Services\Domain\Api\ConnectionInterface;

class Command implements CommandInterface
{
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection->connect();
    }

    /** @throws Exception */
    public function getList(): array
    {
        $query = sprintf('SELECT * FROM `%s`', 'command');
        return $this->connection->request($query)->fetch_all(MYSQLI_ASSOC);
    }

    /** @throws Exception */
    public function getCommand(string $commandName): array
    {
        $parameters = $this->connection->getAll("SELECT command.commandId AS commandId,
                                                        argument.argumentName AS argumentName,
                                                        argument.argumentId AS argumentId,
                                                        `option`.optionId AS optionId,
                                                        `option`.optionName AS optionName,
                                                        `meaning`.meaningId AS meaningId,
                                                        `meaning`.meaning AS meaning
                                                 FROM command
                                                 LEFT JOIN argument ON command.commandId = argument.commandId
                                                 LEFT JOIN `option` ON command.commandId = `option`.commandId
                                                 LEFT JOIN `meaning` ON `option`.optionId = `meaning`.optionId
                                                 WHERE command.commandName IN (?s)", ($commandName));
        if ($parameters) {
            $commandId = (int)$parameters[0]['commandId'];
            $arguments = [];
            $options = [];
            $meanings = [];
            foreach ($parameters as $parameter) {
                if ($parameter['argumentName'] && !array_key_exists($parameter['argumentId'], $arguments)) {
                    $arguments[(int)$parameter['argumentId']] = $parameter['argumentName'];
                }
                if ($parameter['optionName'] && !array_key_exists($parameter['optionId'], $options)) {
                    $options[(int)$parameter['optionId']] = $parameter['optionName'];
                }
                if ($parameter['meaning']) {
                    if (array_key_exists($parameter['optionId'], $meanings) &&
                        array_key_exists($parameter['meaningId'], $meanings[$parameter['optionId']])) {
                        continue;
                    }
                    $meanings[(int)$parameter['optionId']][(int)$parameter['meaningId']] = $parameter['meaning'];
                }
            }
            return [$arguments, $options, $meanings, $commandId];
        } else {
            return [[], [], [], null];
        }
    }

    /** @throws Exception */
    public function upsertCommand(string $commandName, array $arguments, array $options): void
    {
        try {
            list($currentArguments, $currentOptions, $currentMeanings, $commandId) = $this->getCommand($commandName);
            if (!$commandId) {
                $this->connection->request("INSERT INTO command (commandName) VALUES (?s)", $commandName);
                $commandId = $this->connection->getOne("SELECT * FROM command WHERE command.commandName IN (?s)", ($commandName));
            }
            if ($commandId && is_string($commandId)) {
                $commandId = (int)$commandId;
            }

            $this->upsertArguments($arguments, $currentArguments, $commandId);
            $this->upsertOptions($options, $currentOptions, $currentMeanings, $commandId);
        } catch (Exception $e) {
            echo "\nError adding command. {$e->getMessage()}\n";
        }
    }

    /** @throws Exception */
    private function upsertArguments(array $arguments, array $currentArguments, int $commandId): void
    {
        try {
            $divergenceArguments = array_diff($arguments, $currentArguments);
            foreach ($divergenceArguments as $divergenceArgument) {
                if (in_array($divergenceArgument, $currentArguments)) {
                    $this->connection->request("DELETE FROM argument WHERE argumentName=(?s) AND commandId=(?i)", $divergenceArgument, $commandId);
                    continue;
                }
                $this->connection->request("INSERT INTO argument (argumentName, commandId) VALUES (?s, ?i)", $divergenceArgument, $commandId);
            }
        } catch (Exception $e) {
            echo "\nError upsert argument. {$e->getMessage()}\n";
        }
    }

    /** @throws Exception */
    private function upsertOptions(array $options, array $currentOptions, array $currentMeanings, int $commandId): void
    {
        try {
            $divergenceOptions = array_diff(array_keys($options), $currentOptions);
            foreach ($divergenceOptions as $divergenceOption) {
                if (in_array($divergenceOption, $currentOptions)) {
                    $optionId = array_search($divergenceOption, $currentOptions);
                    $this->connection->request("DELETE option
                                                  FROM option
                                                  LEFT JOIN `meaning` ON `option`.optionId = `meaning`.optionId
                                                  WHERE optionId=(?i)", $optionId);
                    unset($currentOptions[$optionId]);
                    unset($currentMeanings[$optionId]);
                    continue;
                }
                $this->connection->request("INSERT INTO `option` (optionName, commandId) VALUES (?s, ?i)", $divergenceOption, $commandId);
                $option = $this->connection->getOne("SELECT * FROM `option` WHERE `option`.`optionName` IN (?s) AND `option`.`commandId` IN (?i)",
                    $divergenceOption,
                    $commandId
                );
                $currentOptions[$option] = $divergenceOption;
            }
            $this->upsertMeanings($options, $currentOptions, $currentMeanings);
        } catch (Exception $e) {
            echo "\nError upsert option. {$e->getMessage()}\n";
        }
    }

    private function upsertMeanings(array $options, array $currentOptions, array $currentMeanings): void
    {
        try {
            foreach ($options as $option => $meaning) {
                $divergenceMeanings = array_diff($meaning, $currentMeanings);
                $optionId = array_search($option, $currentOptions);
                foreach ($divergenceMeanings as $divergenceMeaning) {
                    if (in_array($divergenceMeaning, $currentMeanings)) {
                        $meaningId = array_search($divergenceMeaning, $currentMeanings);
                        $this->connection->request("DELETE FROM `meaning` WHERE meaningId=(?i)", $meaningId);
                        continue;
                    }
                    $part = is_numeric($divergenceMeaning) ? '?i' : '?s';
                    $this->connection->request("INSERT INTO `meaning` (meaning, optionId) VALUES ($part, ?i)", $divergenceMeaning, $optionId);
                }
            }
        } catch (Exception $e) {
            echo "\nError upsert meaning. {$e->getMessage()}\n";
        }
    }
}
