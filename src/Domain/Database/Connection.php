<?php

declare(strict_types=1);

namespace Services\Domain\Database;

use Exception;
use mysqli;
use mysqli_result;
use Services\Domain\Api\ConnectionInterface;

class Connection implements ConnectionInterface
{
    private mysqli|false $mysqli;
    private mixed $stats;
    private mixed $errorMode;
    private mixed $exception;
    private array $params;

    private array $defaultParams = [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'console-application',
        'port' => null,
        'socket' => null,
        'pConnect' => false,
        'charset' => 'utf8',
        'errMode' => 'exception',
        'exception' => 'Exception',
        'versions' => 'versions',
    ];

    public function __construct()
    {
        $this->params = array_merge($this->defaultParams, Params::get(Params::DEFAULT_DB));
        $this->errorMode = $this->params['errMode'];
        $this->exception = $this->params['exception'];

        if ($this->params['pConnect']) {
            $this->params['host'] = "p:" . $this->params['host'];
        }

        $this->mysqli = mysqli_connect(
            $this->params['host'],
            $this->params['user'],
            $this->params['pass'],
            $this->params['db'],
            $this->params['port'],
            $this->params['socket']
        );
        if (!$this->mysqli) {
            $this->getError(error: $this->mysqli->connect_errno . " " . $this->mysqli->connect_error);
        }

        $this->mysqli->set_charset($this->params['charset']) or $this->getError($this->mysqli->error);
    }

    /** @throws Exception */
    public function connect(): ConnectionInterface
    {
        $query = $this->request('set names utf8');
        if (!$query) {
            throw new Exception(message: 'Unable to connect to data server.');
        }
        return $this;
    }

    public function request(): mysqli_result|bool
    {
        return $this->getRawQuery($this->prepareQuery(func_get_args()));
    }

    public function getUser(): string
    {
        return "{$this->params['user']}";
    }

    public function getPassword(): string
    {
        return "{$this->params['pass']}";
    }

    public function getHost(): string
    {
        return "{$this->params['host']}";
    }

    public function getDBName(): string
    {
        return "{$this->params['db']}";
    }

    public function getVersion(): string
    {
        return "{$this->params['versions']}";
    }

    public function getOne(): mixed
    {
        $query = $this->prepareQuery(func_get_args());
        if ($res = $this->getRawQuery($query)) {
            $row = mysqli_fetch_array(result: $res, mode: MYSQLI_ASSOC);
            if (is_array($row)) {
                return reset(array: $row);
            }
            mysqli_free_result($res);
        }
        return false;
    }

    public function getAll(): array
    {
        $ret = [];
        $query = $this->prepareQuery(func_get_args());
        if ($res = $this->getRawQuery($query)) {
            while ($row = mysqli_fetch_array(result: $res, mode: MYSQLI_ASSOC)) {
                $ret[] = $row;
            }
            mysqli_free_result($res);
        }
        return $ret;
    }

    private function getRawQuery(string $query): mysqli_result|bool
    {
        $start = microtime(as_float: true);
        $timer = microtime(as_float: true) - $start;
        $res = $this->mysqli->query($query);

        $this->stats[] = [
            'query' => $query,
            'start' => $start,
            'timer' => $timer,
        ];
        if (!$res) {
            $error = $this->mysqli->error;

            end(array: $this->stats);
            $key = key($this->stats);
            $this->stats[$key]['error'] = $error;
            $this->cutStats();

            $this->getError(error: "$error. Full query: [$query]");
        }
        $this->cutStats();
        return $res;
    }

    private function prepareQuery(array $args): string
    {
        $query = '';
        $raw = array_shift(array: $args);
        $array = preg_split(pattern: '~(\?[nsiuap])~u', subject: $raw, limit: 0, flags: PREG_SPLIT_DELIM_CAPTURE);
        $aNum = count($args);
        $pNum = floor(num: count($array) / 2);
        if ($pNum != $aNum) {
            $this->getError(error: "Number of args ($aNum) doesn't match number of placeholders ($pNum) in [$raw]");
        }

        foreach ($array as $i => $part) {
            if (($i % 2) == 0) {
                $query .= $part;
                continue;
            }

            $value = array_shift(array: $args);
            $part = match ($part) {
                '?n' => $this->escapeIdent($value),
                '?s' => $this->escapeString($value),
                '?i' => $this->escapeInt($value),
                '?a' => $this->createIn($value),
                '?u' => $this->createSet($value),
                '?p' => $value,
            };
            $query .= $part;
        }
        return $query;
    }

    private function escapeInt(mixed $value): bool|int|string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (!is_numeric($value)) {
            $this->getError(error: "Integer (?i) placeholder expects numeric value, " . gettype($value) . " given");
            return false;
        }
        if (is_float($value)) {
            $value = number_format(num: $value, decimals: 0, decimal_separator: '.', thousands_separator: '');
        }
        return $value;
    }

    private function escapeString(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        return "'" . $this->mysqli->real_escape_string($value) . "'";
    }

    private function escapeIdent(mixed $value): bool|string
    {
        if ($value) {
            return "`" . str_replace(search: "`", replace: "``", subject: $value) . "`";
        } else {
            $this->getError(error: "Empty value for identifier (?n) placeholder");
            return false;
        }
    }

    private function createIn(mixed $data): bool|string
    {
        if (!is_array($data)) {
            $this->getError(error: "Value for IN (?a) placeholder should be array");
            return false;
        }
        if (!$data) {
            return 'NULL';
        }
        $query = $comma = '';
        foreach ($data as $value) {
            $query .= $comma . $this->escapeString($value);
            $comma = ",";
        }
        return $query;
    }

    private function createSet(mixed $data): bool|string
    {
        if (!is_array($data)) {
            $this->getError(error: "SET (?u) placeholder expects array, " . gettype($data) . " given");
            return false;
        }
        if (!$data) {
            $this->getError(error: "Empty array for SET (?u) placeholder");
            return false;
        }
        $query = $comma = '';
        foreach ($data as $key => $value) {
            $query .= $comma . $this->escapeIdent($key) . '=' . $this->escapeString($value);
            $comma = ",";
        }
        return $query;
    }

    private function getError(string $error): void
    {
        $error = __CLASS__ . ": " . $error;

        if ($this->errorMode == 'error') {
            $error .= ". Error initiated in {$this->getCaller()}, thrown";
            trigger_error(message: $error, error_level: E_USER_ERROR);
        } else {
            throw new $this->exception($error);
        }
    }

    private function getCaller(): string
    {
        $trace = debug_backtrace();
        $caller = '';
        foreach ($trace as $t) {
            if (isset($t['class']) && $t['class'] == __CLASS__) {
                $caller = $t['file'] . " on line " . $t['line'];
            } else {
                break;
            }
        }
        return $caller;
    }

    private function cutStats(): void
    {
        if (count($this->stats) > 100) {
            reset(array: $this->stats);
            $first = key(array: $this->stats);
            unset($this->stats[$first]);
        }
    }
}
