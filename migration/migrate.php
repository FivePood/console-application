<?php

include "db.php";
include "params.php";

/**
 * @throws Exception
 */
function connectDB()
{
    $sets = new ConnectDatabase();
    $conn = new DatabaseAccess($sets->dbSet);

    if (!$conn)
        throw new Exception('Unable to connect to data server.');
    else {
        $query = $conn->query('set names utf8');
        if (!$query)
            throw new Exception('Unable to connect to data server.');
        else
            return $conn;
    }
}

function getMigrationFiles($conn)
{
    $sqlFolder = str_replace('\\', '/', realpath(dirname(__FILE__)) . '/');
    $allFiles = glob($sqlFolder . '*.sql');

    $query = sprintf('show tables from `%s` like "%s"', DB_NAME, DB_TABLE_VERSIONS);
    $data = $conn->query($query);
    $firstMigration = !$data->num_rows;

    if ($firstMigration) {
        return $allFiles;
    }

    $versionsFiles = [];

    $query = sprintf('SELECT `name` FROM `%s`', DB_TABLE_VERSIONS);
    $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
    foreach ($data as $row) {
        array_push($versionsFiles, $sqlFolder . $row['name']);
    }

    return array_diff($allFiles, $versionsFiles);
}

$conn = connectDB();

$files = getMigrationFiles($conn);

if (empty($files)) {
    echo 'Database up to date.';
} else {
    echo "Starting the migration...\n";
    foreach ($files as $file) {
        $command = sprintf('mysql -u%s -p%s -h %s -D %s < %s', DB_USER, DB_PASSWORD, DB_HOST, DB_NAME, $file);
        shell_exec($command);
        $conn->query(sprintf('INSERT INTO `%s` (`name`) VALUES ("%s")', DB_TABLE_VERSIONS, basename($file)));
        echo basename($file) . "\n";
    }
    echo "\nMigration completed.";
}
