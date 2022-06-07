<?php

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASSWORD = '';
const DB_NAME = 'console-application';
const DB_TABLE_VERSIONS = 'versions';
const DB_TABLE_COMMAND = 'command';

class ConnectDatabase
{
    public $dbSet = [
        'host' => DB_HOST,
        'user' => DB_USER,
        'pass' => DB_PASSWORD,
        'db' => DB_NAME,
    ];
}
