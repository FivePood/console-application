<?php

declare(strict_types=1);

namespace Migrations;

use Exception;
use Services\Domain\Api\ConnectionInterface;

class Migrate
{
    private ConnectionInterface $connection;

    /** @throws Exception */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection->connect();
        $this->init();
    }

    /** @throws Exception */
    private function init(): void
    {
        $migrations = $this->getMigrationFiles();

        if (empty($migrations)) {
            echo "Database up to date.\n";
        } else {
            echo "Starting the migrations...\n";
            foreach ($migrations as $migration) {
                $command = sprintf(
                    'mysql -u%s -p%s -h %s -D %s < %s',
                    $this->connection->getUser(),
                    $this->connection->getPassword(),
                    $this->connection->getHost(),
                    $this->connection->getDBName(),
                    $migration
                );
                shell_exec($command);
                $this->connection->request(sprintf('INSERT INTO `%s` (`name`) VALUES ("%s")', $this->connection->getVersion(), basename($migration)));
                echo basename($migration) . "\n";
            }
            echo "\nMigration completed.\n";
        }
    }

    private function getMigrationFiles(): bool|array
    {
        $sqlFolder = str_replace('\\', '/', realpath(dirname(__FILE__)) . '/');
        $allFiles = glob($sqlFolder . '*.sql');

        $query = sprintf('show tables from `%s` like "%s"', $this->connection->getDBName(), $this->connection->getVersion());
        $data = $this->connection->request($query);
        $firstMigration = !$data->num_rows;

        if ($firstMigration) {
            return $allFiles;
        }

        $versionsFiles = [];

        $query = sprintf('SELECT `name` FROM `%s`', $this->connection->getVersion());
        $data = $this->connection->request($query)->fetch_all(MYSQLI_ASSOC);
        foreach ($data as $row) {
            $versionsFiles[] = $sqlFolder . $row['name'];
        }

        return array_diff($allFiles, $versionsFiles);
    }
}
