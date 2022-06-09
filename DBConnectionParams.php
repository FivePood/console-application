<?php

class DBConnectionParams
{
    private const DB_HOST = 'localhost';
    private const DB_USER = 'root';
    private const DB_PASSWORD = '';
    private const DB_NAME = 'console-application';
    private const DB_TABLE_VERSIONS = 'versions';

    private array $dbSettings = [
        'host' => self::DB_HOST,
        'user' => self::DB_USER,
        'pass' => self::DB_PASSWORD,
        'db' => self::DB_NAME,
        'versions' => self::DB_TABLE_VERSIONS,
    ];

    public function getDbSettings(): array
    {
        return $this->dbSettings;
    }
}
