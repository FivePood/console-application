<?php
include 'DBConnection.php';
include 'DBConnectionParams.php';
include 'Application.php';

$dbConn = new DBConnection(new DBConnectionParams());
$do = new Application($dbConn);

if (empty($argv[1])) {
    $do->viewList();
} else {
    $do->addCommand($argv);
}
echo "\n";
