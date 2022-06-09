<?php

class Migrate
{
    private DBConnection $_databaseConnection;

    function __construct(DBConnection $databaseConnection)
    {
        $this->_databaseConnection = $databaseConnection;
        $this->init();
    }

    private function init(): void
    {
        try {
            $conn = $this->connectDB();
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $migrations = $this->getMigrationFiles($conn);

        if (empty($migrations)) {
            echo 'Database up to date.';
        } else {
            echo "Starting the migration...\n";
            foreach ($migrations as $file) {
                $command = sprintf('mysql -u%s -p%s -h %s -D %s < %s', $conn->getUser(), $conn->getPassword(), $conn->getHost(), $conn->getDBName(), $file);
                shell_exec($command);
                $conn->query(sprintf('INSERT INTO `%s` (`name`) VALUES ("%s")', $conn->getVersion(), basename($file)));
                echo basename($file) . "\n";
            }
            echo "\nMigration completed.";
        }
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

    private function getMigrationFiles(DBConnection $conn): bool|array
    {
        $sqlFolder = str_replace('\\', '/', realpath(dirname(__FILE__)) . '/');
        $allFiles = glob($sqlFolder . '*.sql');

        $query = sprintf('show tables from `%s` like "%s"', $conn->getDBName(), $conn->getVersion());
        $data = $conn->query($query);
        $firstMigration = !$data->num_rows;

        if ($firstMigration) {
            return $allFiles;
        }

        $versionsFiles = [];

        $query = sprintf('SELECT `name` FROM `%s`', $conn->getVersion());
        $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        foreach ($data as $row) {
            $versionsFiles[] = $sqlFolder . $row['name'];
        }

        return array_diff($allFiles, $versionsFiles);
    }
}
