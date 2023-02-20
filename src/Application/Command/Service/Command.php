<?php

declare(strict_types=1);

namespace Services\Application\Command\Service;

use Exception;
use Services\Application\Command\Api\CommandInterface;
use Services\Domain\Database\Api\ConnectionInterface;

class Command implements CommandInterface
{
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /** @throws Exception */
    public function getList(): array
    {
        $conn = $this->connection->connect();
        $query = sprintf('SELECT * FROM `%s`', 'command');
        return $conn->request($query)->fetch_all(MYSQLI_ASSOC);
    }

    /** @throws Exception */
    public function getCommand(string $commandName): array
    {
        $conn = $this->connection->connect();
        $parameters = $conn->getAll(
            "SELECT command.commandId AS commandId,
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
                                     WHERE command.commandName IN (?s)",
            ($commandName)
        );
        if ($parameters) {
            $commandId = $parameters[0]['commandId'];
            $arguments = [];
            $options = [];
            $meanings = [];
            foreach ($parameters as $parameter) {
                if ($parameter['argumentName'] && !array_key_exists($parameter['argumentId'], $arguments)) {
                    $arguments[$parameter['argumentId']] = $parameter['argumentName'];
                }
                if ($parameter['optionName'] && !array_key_exists($parameter['optionId'], $options)) {
                    $options[$parameter['optionId']] = $parameter['optionName'];
                }
                if ($parameter['meaning']) {
                    if (array_key_exists($parameter['optionId'], $meanings) &&
                        array_key_exists($parameter['meaningId'], $meanings[$parameter['optionId']])) {
                        continue;
                    }
                    $meanings[$parameter['optionId']][$parameter['meaningId']] = $parameter['meaning'];
                }
            }
            return [$arguments, $options, $meanings, $commandId];
        } else {
            return [];
        }
    }

    /** @throws Exception */
    public function upsertCommand(string $commandName, array $arguments, array $options): void
    {
        try {
            $conn = $this->connection->connect();
            $currentArguments = [];
            $currentOptions = [];
            $currentMeanings = [];
            $commandId = null;
            $currentCommand = $this->getCommand($commandName);
            if ($currentCommand) {
                list($currentArguments, $currentOptions, $currentMeanings, $commandId) = $currentCommand;
            }
            if (!$commandId) {
                $conn->request("INSERT INTO command (commandName) VALUES (?s)", $commandName);
                $commandId = $conn->getOne("SELECT * FROM command WHERE command.commandName IN (?s)", ($commandName));
            }
            $this->upsertArguments($conn, $arguments, $currentArguments, $commandId);
            $this->upsertOptions($conn, $options, $currentOptions, $currentMeanings, $commandId);
        } catch (Exception $e) {
            echo "\nError adding command. {$e->getMessage()}\n";
        }
    }

    /** @throws Exception */
    private function upsertArguments(
        ConnectionInterface $conn,
        array $arguments,
        array $currentArguments,
        int $commandId
    ): void {
        try {
            $divergenceArguments = array_diff($arguments, $currentArguments);
            foreach ($divergenceArguments as $divergenceArgument) {
                if (in_array($divergenceArgument, $currentArguments)) {
                    $conn->request(
                        "DELETE FROM argument WHERE argumentName=(?s) AND commandId=(?s)",
                        $divergenceArgument,
                        $commandId
                    );
                    continue;
                }
                $conn->request(
                    "INSERT INTO argument (argumentName, commandId) VALUES (?s, ?s)",
                    $divergenceArgument,
                    $commandId
                );
            }
        } catch (Exception $e) {
            echo "\nError upsert argument. {$e->getMessage()}\n";
        }
    }

    /** @throws Exception */
    private function upsertOptions(
        ConnectionInterface $conn,
        array $options,
        array $currentOptions,
        array $currentMeanings,
        int $commandId
    ): void {
        try {
            $divergenceOptions = array_diff(array_keys($options), $currentOptions);
            foreach ($divergenceOptions as $divergenceOption) {
                if (in_array($divergenceOption, $currentOptions)) {
                    $optionId = array_search($divergenceOption, $currentOptions);
                    $conn->request(
                        "DELETE option
                                  FROM option
                                  LEFT JOIN `meaning` ON `option`.optionId = `meaning`.optionId
                                  WHERE optionId=(?s)",
                        $optionId
                    );
                    unset($currentOptions[$optionId]);
                    unset($currentMeanings[$optionId]);
                    continue;
                }
                $conn->request(
                    "INSERT INTO `option` (optionName, commandId) VALUES (?s, ?s)",
                    $divergenceOption,
                    $commandId
                );
                $option = $conn->getOne(
                    "SELECT * FROM `option` WHERE `option`.`optionName` IN (?s) AND `option`.`commandId` IN (?s)",
                    $divergenceOption,
                    $commandId
                );
                $currentOptions[$option] = $divergenceOption;
            }
            $this->upsertMeanings($conn, $options, $currentOptions, $currentMeanings);
        } catch (Exception $e) {
            echo "\nError upsert option. {$e->getMessage()}\n";
        }
    }

    private function upsertMeanings(
        ConnectionInterface $conn,
        array $options,
        array $currentOptions,
        array $currentMeanings
    ): void {
        try {
            foreach ($options as $option => $meaning) {
                $divergenceMeanings = array_diff($meaning, $currentMeanings);
                $optionId = array_search($option, $currentOptions);
                foreach ($divergenceMeanings as $divergenceMeaning) {
                    if (in_array($divergenceMeaning, $currentMeanings)) {
                        $meaningId = array_search($divergenceMeaning, $currentMeanings);
                        $conn->request("DELETE FROM `meaning` WHERE meaningId=(?s)", $meaningId);
                        continue;
                    }
                    $conn->request(
                        "INSERT INTO `meaning` (meaning, optionId) VALUES (?s, ?s)",
                        $divergenceMeaning,
                        $optionId
                    );
                }
            }
        } catch (Exception $e) {
            echo "\nError upsert meaning. {$e->getMessage()}\n";
        }
    }
}
