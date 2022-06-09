<?php
include 'DBConnection.php';
include 'DBConnectionParams.php';
include 'Application.php';
include 'migration/Migrate.php';

$dbConn = new DBConnection(new DBConnectionParams());

if (!empty($argv[1]) && $argv[1] == 'migrate') {
    $do = new Migrate($dbConn);
} else {
    $do = new Application($dbConn);
    if (empty($argv[1])) {
        $do->viewList();
    } else {
        $do->addCommand($argv);
    }
}
echo "\n";
