<?php
include 'DBConnection.php';
include 'DBConnectionParams.php';
include 'migration/Migrate.php';

$dbConn = new DBConnection(new DBConnectionParams());
$do = new Migrate($dbConn);

echo "\n";
