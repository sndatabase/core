<?php

/*
 * The MIT License
 *
 * Copyright 2015 Samy Naamani <samy@namani.net>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SNDatabase;

use SNTools\Object;

/**
 * Superclass for all connections
 *
 * @author Samy Naamani <samy@namani.net>
 * @property-read string $connectionString Connection String
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 */
abstract class Connection extends Object {

    /**
     * Default fetch mode
     * @var FetchMode
     */
    private $defaultFetchMode;

    /**
     * Connection string (real attribute)
     * @var string
     */
    private $cnxString;

    /**
     * Connection constructor
     * @param string $connectionString Connection string
     */
    public function __construct($connectionString) {
        parent::__construct();
        $this->cnxString = $connectionString;
        $this->connect();
    }

    public function __get($name) {
        switch($name) {
            case 'connectionString':
                return $this->cnxString;
            default:
                return parent::__get($name);
        }
    }

    /**
     * Establish connection, using connection string.
     * Driver-dependant
     */
    abstract public function connect();

    /**
     * Public getter for default fetch mode
     * @return FetchMode
     */
    final public function getDefaultFetchMode() {
        return $this->defaultFetchMode;
    }

    /**
     * Non-public setter for default fetch mode
     * @param FetchMode $defaultFetchMode
     */
    final protected function setDefaultFetchMode(FetchMode $defaultFetchMode) {
        $this->defaultFetchMode = $defaultFetchMode;
    }


    /**
     * Starts a new transaction
     * @param string|null $name Transaction name. Null if none.
     * @return Transaction Transaction object
     */
    abstract public function startTransaction($name = null);

    /**
     * Creates a prepared statement
     * @param string $statement Statement
     * @return PreparedStatement
     */
    abstract public function prepare($statement);

    /**
     * Creates a statement with parameters
     * @param string $statement Statement
     * @return ParameterizedStatement
     */
    public function perform($statement) {
        return new ParameterizedStatement($this, $statement);
    }

    /**
     * Executes a simple query and returns its result set
     * @param string $statement Statement
     * @return Result
     */
    abstract public function query($statement);

    /**
     * Executes a write-only statement and returns how many rows it affected.
     * @param string $statement Statement
     * @return int Number of affected rows
     */
    public function exec($statement) {
        return $this->query($statement) ? $this->countLastAffectedRows() : false;
    }

    /**
     * Number of rows affected by last statement
     * @return int
     */
    abstract public function countLastAffectedRows();

    /**
     * Last inserted ID
     * @return int
     */
    abstract public function lastInsertId();

    /**
     * For text and string values to quote, protects against injection.
     * Driver-dependant.
     * @param string $string Text to quote
     * @return string Quoted text
     */
    abstract protected function escapeString($string);

    /**
     * Quote parameter according to type, to protect against injections
     * @param mixed $value Value to protect
     * @param int $type Type : constant DB::PARAM_*. Defaults to DB::PARAM_AUTO to autodetect type.
     */
    public function quote($value, $type = DB::PARAM_AUTO) {
        if (is_array($value))
            return sprintf('(%s)', implode(', ', array_map(function($v) use($this, $type) {
                                return $this->quote($v, $type);
                            }, $value)));
        $type = ($type == DB::PARAM_AUTO) ? $this->quotedType($value) : $type;
        switch ($type) {
            case DB::PARAM_STR:
                $value = $this->escapeString($value);
                break;
            case DB::PARAM_DATE:
            case DB::PARAM_TIME:
            case DB::PARAM_DATETIME:
                switch ($type) {
                    case DB::PARAM_DATE:
                        $format = 'Y-m-d';
                        break;
                    case DB::PARAM_TIME:
                        $format = 'H:i:s';
                        break;
                    default:
                        $format = 'Y-m-d H:i:s';
                }
                if (interface_exists('\\DateTimeInterface') and $value instanceof \DateTimeInterface or $value instanceof \DateTime)
                    $value = $value->format($format);
                else {
                    $dt = date_create($value);
                    if ($dt === false)
                        $dt = new \DateTime;
                    if (is_int($value))
                        $dt->setTimestamp($value);
                    $value = $dt->format($format);
                }
                break;
            case DB::PARAM_INT:
                $value = intval($value);
                break;
            case DB::PARAM_BOOL:
                $value = $value ? 1 : 0;
                break;
            case DB::PARAM_FLOAT:
                $value = floatval($value);
                break;
            case DB::PARAM_NULL:
                $value = 'NULL';
                break;
            default:
                throw new \UnexpectedValueException('Invalid parameter value');
        }
        return ($type & DB::PARAM_STR) ? "'$value'" : $value;
    }

    public function quotedType($value) {
        switch (gettype($value)) {
            case 'NULL':
                $type = DB::PARAM_NULL;
                break;
            case 'boolean':
                $type = DB::PARAM_BOOL;
                break;
            case 'integer':
                $type = DB::PARAM_INT;
                break;
            case 'double':
                $type = DB::PARAM_FLOAT;
                break;
            case 'string':
                $type = DB::PARAM_STR;
                break;
            case 'object':
                if (interface_exists('\\DateTimeInterface') and $value instanceof \DateTimeInterface or $value instanceof \DateTime)
                    $type = DB::PARAM_DATETIME;
                else
                    throw new \RuntimeException('Parameter type not recognized');
                break;
            default:
                throw new \RuntimeException('Parameter type not recognized');
        }
    }

}
