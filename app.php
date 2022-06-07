<?php

include "db.php";
include "params.php";

$do = new Action();
if (empty($argv[1])) {
    $do->viewList();
} else {
    $do->add($argv);
}
echo "\n";

class Action
{
    public function viewList()
    {
        $sets = new ConnectDatabase();
        $db = new DatabaseAccess($sets->dbSet);
        $query = sprintf('SELECT * FROM `%s`', DB_TABLE_COMMAND);
        $data = $db->query($query)->fetch_all(MYSQLI_ASSOC);
        echo "Command list:\n";
        foreach ($data as $key) {
            echo $key["commandId"] . '. ' . $key["commandName"] . "\n";
        }
    }

    public function help($commandName)
    {
        $sets = new ConnectDatabase();
        $db = new DatabaseAccess($sets->dbSet);
        $command = $db->getAll("SELECT * FROM `command` WHERE `command`.`commandName` IN (?s)", ($commandName));
        if (!empty($command)) {
            $arguments = $db->getAll(
                "SELECT * FROM `command`
                LEFT JOIN `arguments` ON `command`.`commandId` = `arguments`.`commandId`
                WHERE `command`.`commandName` IN (?s)", ($commandName)
            );
            $options = $db->getAll(
                "SELECT * FROM `command`
                LEFT JOIN `options` ON `command`.`commandId` = `options`.`commandId`
                WHERE `command`.`commandName` IN (?s)", ($commandName)
            );

            echo "-------------------------------------------------\n";

            echo "Called command: $commandName\n\n";
            if (!empty($arguments['commandId'])) {
                echo "Arguments:\n";

                foreach ($arguments as $key) {
                    echo "  -  " . $key["argumentsName"] . "\n";
                }
                echo "\n";
            }

            if (!empty($options['commandId'])) {
                echo "Options:\n";
                foreach ($options as $key) {
                    echo "  -  " . $key["optionsName"] . "\n";
                    $optionsMeanings = $db->getAll("SELECT * FROM `optionsMeaning` WHERE `optionsId` IN (?s)", ($key["optionsId"]));
                    if (!empty($optionsMeanings['optionsId'])) {
                        foreach ($optionsMeanings as $optionsMeaning) {
                            echo "     -  " . $optionsMeaning["meaning"] . "\n";
                        }
                    }
                }
            }
        } else {
            echo "-------------------------------------------------\n";
            echo "Command $commandName is not in the database.\n";
        }
        echo "-------------------------------------------------\n";
    }

    public function add($argv)
    {
        $sets = new ConnectDatabase();
        $db = new DatabaseAccess($sets->dbSet);
        $recordingFailure = null;

        foreach ($argv as $key => $value) {
            if ($key == 0 || $key == 1) {
                continue;
            }

            if ($key == 2 && trim($value, '{}') == 'help') {
                $do = new Action();
                $do->help($argv[1]);
                $recordingFailure = true;
                break;
            } elseif ($key == 2 && !empty($commandId)) {
                echo "-------------------------------------------------\n";
                echo "Command already exists\n";
                $do = new Action();
                $do->help($argv[1]);
                $recordingFailure = true;
                break;
            }

            $commandId = $db->getOne("SELECT * FROM `command` WHERE `command`.`commandName` IN (?s)", ($argv[1]));
            if (empty($commandId)) {
                $db->query("INSERT INTO `command` (`commandName`) VALUES (?s)", $argv[1]);
            }

            $commandId = $db->getOne("SELECT * FROM `command` WHERE `command`.`commandName` IN (?s)", ($argv[1]));

            if (!preg_match("/[\[\]]/", $value)) {
                $db->query("INSERT INTO `arguments` (`argumentsName`, `commandId`) VALUES (?s, ?s)", trim($value, '{}'), $commandId);
            }

            if (preg_match("/[\[\]]/", $value)) {
                $optionsArr = explode("=", trim($value, '[]'));
                $optionsId = $db->getOne("SELECT * FROM `options` WHERE `options`.`optionsName` IN (?s)", ($optionsArr[0]));
                if (empty($optionsId)) {
                    $db->query("INSERT INTO `options` (`optionsName`, `commandId`) VALUES (?s, ?s)", $optionsArr[0], $commandId);
                    $optionsId = $db->getOne("SELECT * FROM `options` WHERE `options`.`optionsName` IN (?s)", ($optionsArr[0]));
                }
                $db->query("INSERT INTO `optionsMeaning` (`meaning`, `optionsId`) VALUES (?s, ?s)", $optionsArr[1], $optionsId);
            }
        }

        if (is_null($recordingFailure)) {
            echo "-------------------------------------------------\n";
            echo "Command added \n";
            echo "-------------------------------------------------\n";
            $do = new Action();
            $do->viewList();
        }
    }
}
