<?php

declare(strict_types=1);

namespace Migrations;

use Exception;
use Services\Domain\Database\Api\ConnectionInterface;
use Services\Domain\Database\Service\Connection;

class Migrate
{
    private ConnectionInterface $connection;

    /** @throws Exception */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->init();
    }

    /** @throws Exception */
    private function init(): void
    {
        $conn = $this->connection->connect();
        $migrations = $this->getMigrationFiles($conn);

        if (empty($migrations)) {
            echo "Database up to date.\n";
        } else {
            echo "Starting the migrations...\n";
            foreach ($migrations as $migration) {
                $command = sprintf(
                    'mysql -u%s -p%s -h %s -D %s < %s',
                    $conn->getUser(),
                    $conn->getPassword(),
                    $conn->getHost(),
                    $conn->getDBName(),
                    $migration
                );
                shell_exec($command);
                $conn->request(
                    sprintf('INSERT INTO `%s` (`name`) VALUES ("%s")', $conn->getVersion(), basename($migration))
                );
                echo basename($migration) . "\n";
            }
            echo "\nMigration completed.\n";
        }
    }

    private function getMigrationFiles(Connection $conn): bool|array
    {
        $sqlFolder = str_replace('\\', '/', realpath(dirname(__FILE__)) . '/');
        $allFiles = glob($sqlFolder . '*.sql');

        $query = sprintf('show tables from `%s` like "%s"', $conn->getDBName(), $conn->getVersion());
        $data = $conn->request($query);
        $firstMigration = !$data->num_rows;

        if ($firstMigration) {
            return $allFiles;
        }

        $versionsFiles = [];

        $query = sprintf('SELECT `name` FROM `%s`', $conn->getVersion());
        $data = $conn->request($query)->fetch_all(MYSQLI_ASSOC);
        foreach ($data as $row) {
            $versionsFiles[] = $sqlFolder . $row['name'];
        }

        return array_diff($allFiles, $versionsFiles);
    }
}
