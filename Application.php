<?php

class Application
{
    private DBConnection $_databaseConnection;

    public function __construct(DBConnection $databaseConnection)
    {
        $this->_databaseConnection = $databaseConnection;
    }

    /**
     * @throws Exception
     */
    public function connectDB(): DBConnection
    {
        $query = $this->_databaseConnection->query('set names utf8');
        if (!$query) {
            throw new Exception('Unable to connect to data server.');
        }
        return $this->_databaseConnection;
    }

    public function viewList(): void
    {
        try {
            $conn = $this->connectDB();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $query = sprintf('SELECT * FROM `%s`', 'command');
        $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        echo "Command list:\n";
        foreach ($data as $key) {
            echo $key["commandId"] . '. ' . $key["commandName"] . "\n";
        }
    }

    public function viewDetails(string $commandName)
    {
        try {
            $conn = $this->connectDB();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $command = $conn->getAll("SELECT * FROM `command` WHERE `command`.`commandName` IN (?s)", ($commandName));
        if (!empty($command)) {
            $arguments = $conn->getAll(
                "SELECT * FROM `command`
                LEFT JOIN `arguments` ON `command`.`commandId` = `arguments`.`commandId`
                WHERE `command`.`commandName` IN (?s)", ($commandName)
            );
            $options = $conn->getAll(
                "SELECT * FROM `command`
                LEFT JOIN `options` ON `command`.`commandId` = `options`.`commandId`
                WHERE `command`.`commandName` IN (?s)", ($commandName)
            );

            echo "-------------------------------------------------\n";
            echo "Called command: $commandName\n\n";
            echo "Arguments:\n";
            foreach ($arguments as $key) {
                echo "  -  " . $key["argumentsName"] . "\n";
            }
            echo "\n";

            echo "Options:\n";
            foreach ($options as $key) {
                echo "  -  " . $key["optionsName"] . "\n";
                $optionsMeanings = $conn->getAll("SELECT * FROM `optionsMeaning` WHERE `optionsId` IN (?s)", ($key["optionsId"]));
                foreach ($optionsMeanings as $optionsMeaning) {
                    echo "     -  " . $optionsMeaning["meaning"] . "\n";
                }
            }

        } else {
            echo "-------------------------------------------------\n";
            echo "Command $commandName is not in the database.\n";
        }
        echo "-------------------------------------------------\n";
    }

    public function addCommand(array $argv)
    {
        try {
            $conn = $this->connectDB();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $recordingFailure = null;

        foreach ($argv as $key => $value) {
            if ($key == 0 || $key == 1) {
                continue;
            }

            if ($key == 2 && trim($value, '{}') == 'help') {
                $this->viewDetails($argv[1]);
                $recordingFailure = true;
                break;
            } elseif ($key == 2 && !empty($commandId)) {
                echo "-------------------------------------------------\n";
                echo "Command already exists\n";
                $this->viewDetails($argv[1]);
                $recordingFailure = true;
                break;
            }

            $commandId = $conn->getOne("SELECT * FROM `command` WHERE `command`.`commandName` IN (?s)", ($argv[1]));
            if (empty($commandId)) {
                $conn->query("INSERT INTO `command` (`commandName`) VALUES (?s)", $argv[1]);
            }

            $commandId = $conn->getOne("SELECT * FROM `command` WHERE `command`.`commandName` IN (?s)", ($argv[1]));

            if (!preg_match("/[\[\]]/", $value)) {
                $conn->query("INSERT INTO `arguments` (`argumentsName`, `commandId`) VALUES (?s, ?s)", trim($value, '{}'), $commandId);
            }

            if (preg_match("/[\[\]]/", $value)) {
                $options = explode("=", trim($value, '[]'));
                $optionsId = $conn->getOne("SELECT * FROM `options` WHERE `options`.`optionsName` IN (?s) AND `options`.`commandId` IN (?s)", ($options[0]), $commandId);
                if (empty($optionsId)) {
                    $conn->query("INSERT INTO `options` (`optionsName`, `commandId`) VALUES (?s, ?s)", $options[0], $commandId);
                    $optionsId = $conn->getOne("SELECT * FROM `options` WHERE `options`.`optionsName` IN (?s) AND `options`.`commandId` IN (?s)", ($options[0]), $commandId);
                }
                $conn->query("INSERT INTO `optionsMeaning` (`meaning`, `optionsId`) VALUES (?s, ?s)", $options[1], $optionsId);
            }
        }

        if (is_null($recordingFailure)) {
            echo "-------------------------------------------------\n";
            echo "Command added \n";
            echo "-------------------------------------------------\n";
            $this->viewList();
        }
    }
}
