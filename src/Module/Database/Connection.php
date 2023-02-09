<?php

namespace Database;

use Exception;
use mysqli;
use mysqli_result;

class Connection implements ConnectionInterface
{
    private mysqli|false $_conn;
    private mixed $_stats;
    private mixed $_errorMode;
    private mixed $_exception;
    private array $_params;

    private array $_defaultParams = [
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

    public function __construct(Params $params)
    {
        $this->_params = array_merge($this->_defaultParams, $params->get());
        $this->_errorMode = $this->_params['errMode'];
        $this->_exception = $this->_params['exception'];

        if ($this->_params['pConnect']) {
            $this->_params['host'] = "p:" . $this->_params['host'];
        }

        $this->_conn = mysqli_connect(
            $this->_params['host'],
            $this->_params['user'],
            $this->_params['pass'],
            $this->_params['db'],
            $this->_params['port'],
            $this->_params['socket']
        );
        if (!$this->_conn) {
            $this->error(mysqli_connect_errno() . " " . mysqli_connect_error());
        }

        mysqli_set_charset($this->_conn, $this->_params['charset']) or $this->error(mysqli_error($this->_conn));
    }

    /** @throws Exception */
    public function connect(): ConnectionInterface
    {
        $query = $this->query('set names utf8');
        if (!$query) {
            throw new Exception('Unable to connect to data server.');
        }
        return $this;
    }

    public function query(): mysqli_result|bool
    {
        return $this->rawQuery($this->prepareQuery(func_get_args()));
    }

    public function getUser(): string
    {
        return $this->_params['user'];
    }

    public function getPassword(): string
    {
        return $this->_params['pass'];
    }

    public function getHost(): string
    {
        return $this->_params['host'];
    }

    public function getDBName(): string
    {
        return $this->_params['db'];
    }

    public function getVersion(): string
    {
        return $this->_params['versions'];
    }

    public function getOne(): mixed
    {
        $query = $this->prepareQuery(func_get_args());
        if ($res = $this->rawQuery($query)) {
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
        if ($res = $this->rawQuery($query)) {
            while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
                $ret[] = $row;
            }
            mysqli_free_result($res);
        }
        return $ret;
    }

    private function rawQuery($query): mysqli_result|bool
    {
        $start = microtime(true);
        $res = mysqli_query($this->_conn, $query);
        $timer = microtime(true) - $start;

        $this->_stats[] = [
            'query' => $query,
            'start' => $start,
            'timer' => $timer,
        ];
        if (!$res) {
            $error = mysqli_error($this->_conn);

            end($this->_stats);
            $key = key($this->_stats);
            $this->_stats[$key]['error'] = $error;
            $this->cutStats();

            $this->error("$error. Full query: [$query]");
        }
        $this->cutStats();
        return $res;
    }

    private function prepareQuery($args): string
    {
        $query = '';
        $raw = array_shift($args);
        $array = preg_split('~(\?[nsiuap])~u', $raw, null, PREG_SPLIT_DELIM_CAPTURE);
        $aNum = count($args);
        $pNum = floor(count($array) / 2);
        if ($pNum != $aNum) {
            $this->error("Number of args ($aNum) doesn't match number of placeholders ($pNum) in [$raw]");
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

    private function escapeInt($value): bool|int|string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (!is_numeric($value)) {
            $this->error("Integer (?i) placeholder expects numeric value, " . gettype($value) . " given");
            return false;
        }
        if (is_float($value)) {
            $value = number_format($value, 0, '.', '');
        }
        return $value;
    }

    private function escapeString($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        return "'" . mysqli_real_escape_string($this->_conn, $value) . "'";
    }

    private function escapeIdent($value)
    {
        if ($value) {
            return "`" . str_replace("`", "``", $value) . "`";
        } else {
            $this->error("Empty value for identifier (?n) placeholder");
        }
    }

    private function createIn($data)
    {
        if (!is_array($data)) {
            $this->error("Value for IN (?a) placeholder should be array");
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

    private function createSet($data)
    {
        if (!is_array($data)) {
            $this->error("SET (?u) placeholder expects array, " . gettype($data) . " given");
            return;
        }
        if (!$data) {
            $this->error("Empty array for SET (?u) placeholder");
            return;
        }
        $query = $comma = '';
        foreach ($data as $key => $value) {
            $query .= $comma . $this->escapeIdent($key) . '=' . $this->escapeString($value);
            $comma = ",";
        }
        return $query;
    }

    private function error($err): void
    {
        $err = __CLASS__ . ": " . $err;

        if ($this->_errorMode == 'error') {
            $err .= ". Error initiated in {$this->caller()}, thrown";
            trigger_error($err, E_USER_ERROR);
        } else {
            throw new $this->_exception($err);
        }
    }

    private function caller(): string
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
        if (count($this->_stats) > 100) {
            reset($this->_stats);
            $first = key($this->_stats);
            unset($this->_stats[$first]);
        }
    }
}
