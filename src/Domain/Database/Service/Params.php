<?php

namespace Database\Service;

class Params
{
    private const HOST = 'localhost';
    private const USER = 'root';
    private const PASSWORD = '';
    private const DB_NAME = 'console-application';
    private const VERSIONS = 'versions';

    public function get(): array
    {
        return [
            'host' => self::HOST,
            'user' => self::USER,
            'pass' => self::PASSWORD,
            'db' => self::DB_NAME,
            'versions' => self::VERSIONS,
        ];
    }
}
