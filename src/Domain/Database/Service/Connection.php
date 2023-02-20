<?php

declare(strict_types=1);

namespace Services\Domain\Database\Service;

use Exception;
use mysqli;
use mysqli_result;
use Services\Domain\Database\Api\ConnectionInterface;
use Services\Domain\Database\Api\ParamsInterface;

class Connection implements ConnectionInterface
{
    private mysqli|false $_conn;
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

    public function __construct(ParamsInterface $params)
    {
        $this->params = array_merge($this->defaultParams, $params->get());
        $this->errorMode = $this->params['errMode'];
        $this->exception = $this->params['exception'];

        if ($this->params['pConnect']) {
            $this->params['host'] = "p:" . $this->params['host'];
        }

        $this->_conn = mysqli_connect(
            $this->params['host'],
            $this->params['user'],
            $this->params['pass'],
            $this->params['db'],
            $this->params['port'],
            $this->params['socket']
        );
        if (!$this->_conn) {
            $this->getError(mysqli_connect_errno() . " " . mysqli_connect_error());
        }

        mysqli_set_charset($this->_conn, $this->params['charset']) or $this->getError(mysqli_error($this->_conn));
    }

    /** @throws Exception */
    public function connect(): ConnectionInterface
    {
        $query = $this->request('set names utf8');
        if (!$query) {
            throw new Exception('Unable to connect to data server.');
        }
        return $this;
    }

    public function request(): mysqli_result|bool
    {
        return $this->getRawQuery($this->prepareQuery(func_get_args()));
    }

    public function getUser(): string
    {
        return $this->params['user'];
    }

    public function getPassword(): string
    {
        return $this->params['pass'];
    }

    public function getHost(): string
    {
        return $this->params['host'];
    }

    public function getDBName(): string
    {
        return $this->params['db'];
    }

    public function getVersion(): string
    {
        return $this->params['versions'];
    }

    public function getOne(): mixed
    {
        $query = $this->prepareQuery(func_get_args());
        if ($res = $this->getRawQuery($query)) {
            $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
            if (is_array($row)) {
                return reset($row);
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
            while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
                $ret[] = $row;
            }
            mysqli_free_result($res);
        }
        return $ret;
    }

    private function getRawQuery(string $query): mysqli_result|bool
    {
        $start = microtime(true);
        $res = mysqli_query($this->_conn, $query);
        $timer = microtime(true) - $start;

        $this->stats[] = [
            'query' => $query,
            'start' => $start,
            'timer' => $timer,
        ];
        if (!$res) {
            $error = mysqli_error($this->_conn);

            end($this->stats);
            $key = key($this->stats);
            $this->stats[$key]['error'] = $error;
            $this->cutStats();

            $this->getError("$error. Full query: [$query]");
        }
        $this->cutStats();
        return $res;
    }

    private function prepareQuery(array $args): string
    {
        $query = '';
        $raw = array_shift($args);
        $array = preg_split('~(\?[nsiuap])~u', $raw, 0, PREG_SPLIT_DELIM_CAPTURE);
        $aNum = count($args);
        $pNum = floor(count($array) / 2);
        if ($pNum != $aNum) {
            $this->getError("Number of args ($aNum) doesn't match number of placeholders ($pNum) in [$raw]");
        }

        foreach ($array as $i => $part) {
            if (($i % 2) == 0) {
                $query .= $part;
                continue;
            }

            $value = array_shift($args);
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
            $this->getError("Integer (?i) placeholder expects numeric value, " . gettype($value) . " given");
            return false;
        }
        if (is_float($value)) {
            $value = number_format($value, 0, '.', '');
        }
        return $value;
    }

    private function escapeString(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        return "'" . mysqli_real_escape_string($this->_conn, $value) . "'";
    }

    private function escapeIdent(mixed $value)
    {
        if ($value) {
            return "`" . str_replace("`", "``", $value) . "`";
        } else {
            $this->getError("Empty value for identifier (?n) placeholder");
        }
    }

    private function createIn(mixed $data)
    {
        if (!is_array($data)) {
            $this->getError("Value for IN (?a) placeholder should be array");
            return;
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

    private function createSet(mixed $data)
    {
        if (!is_array($data)) {
            $this->getError("SET (?u) placeholder expects array, " . gettype($data) . " given");
            return;
        }
        if (!$data) {
            $this->getError("Empty array for SET (?u) placeholder");
            return;
        }
        $query = $comma = '';
        foreach ($data as $key => $value) {
            $query .= $comma . $this->escapeIdent($key) . '=' . $this->escapeString($value);
            $comma = ",";
        }
        return $query;
    }

    private function getError(string $err): void
    {
        $err = __CLASS__ . ": " . $err;

        if ($this->errorMode == 'error') {
            $err .= ". Error initiated in {$this->getCaller()}, thrown";
            trigger_error($err, E_USER_ERROR);
        } else {
            throw new $this->exception($err);
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
            reset($this->stats);
            $first = key($this->stats);
            unset($this->stats[$first]);
        }
    }
}
