<?php

/*
 * The MIT License
 *
 * Copyright 2015 Samy Naamani.
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
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 */
abstract class Connection extends Object {
    const ATTR_ERRMODE = 0;
    const ATTR_DEFAULT_FETCH_MODE = 1;

    /**
     * This error mode will throw exceptions as DBException at each SQL error
     */
    const ERRMODE_EXCEPTION = 100;
    /**
     * This error mode will cause a fatal error when encountering a SQL error
     */
    const ERRMODE_ERROR = 101;
    /**
     * This error mode will cause a warning when encountering a SQL error
     */
    const ERRMODE_WARNING = 102;
    /**
     * This error mode will cause a notice when encountering a SQL error
     */
    const ERRMODE_NOTICE = 104;
    /**
     * This error mode will remain silent when encountering a SQL error
     */
    const ERRMODE_SILENT = 105;

    private $attributes = array();

    /**
     * Connection constructor. Sets up default attributes
     */
    public function __construct() {
        parent::__construct();
        $this->setAttribute (self::ATTR_DEFAULT_FETCH_MODE, Result::FETCH_ASSOC);
        $this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
    }

    /**
     * Executes quick statement and returns a result set
     * @param string $statement Statement to execute
     * @return Result|boolean Result set, or false on failure
     */
    abstract public function query($statement);

    /**
     * Error handler, depends on error mode attribute
     * @param DBException $ex Exception with the SQL error
     * @throws DBException
     */
    public function handleError(DBException $ex) {
        if($this->getAttribute(self::ATTR_ERRMODE) == self::ERRMODE_EXCEPTION) throw $ex;
        elseif($this->getAttribute(self::ATTR_ERRMODE) != self::ERRMODE_SILENT) {
            switch($this->getAttribute(self::ATTR_ERRMODE)) {
                case self::ERRMODE_ERROR:
                    $level = E_USER_ERROR;
                    break;
                case self::ERRMODE_WARNING:
                    $level = E_USER_WARNING;
                    break;
                case self::ERRMODE_NOTICE:
                    $level = E_USER_NOTICE;
                    break;
            }
            trigger_error($ex->getMessage(), $level);
        }
    }

    /**
     * Quick execution of a statement, and returns number of affected rows
     * @param string $statement Statement to execute
     * @return int|boolean Affected rows, or false on failure
     */
    public function exec($statement) {
        $stmt = $this->query($statement);
        return ($stmt instanceof Result) ? $this->countLastAffectedRows() : false;
    }

    /**
     * Creates a Parameterized statement
     * @param string $statement Initial statement
     * @return ParameterizedStatement Statement, ready to be parametered
     */
    public function queryWithParam($statement) {
        return new ParameterizedStatement($this, $statement);
    }

    /**
     * Creates a Prepared statement
     * @param string $statement Statement to prepare
     * @return PreparedStatement|boolean Prepared statement, false on failure
     */
    abstract public function prepare($statement);

    /**
     * Starts a transaction
     * @return Transaction Object representing started transaction
     */
    abstract public function startTransaction();

    /**
     * Sets connection attribute
     * @param int $attribute
     * @param mixed $value
     */
    public function setAttribute($attribute, $value) {
        $this->attributes[$attribute] = $value;
    }

    /**
     * Reads connection attribute
     * @param int $attribute
     * @return mixed
     */
    public function getAttribute($attribute) {
        return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
    }

    /**
     * Quoting a string value (used by statements)
     * @param string $string String to quote
     * @return string
     */
    abstract public function quote($string);

    /**
     * Counts number of last affected rows. Used by some drivers, ineffective and overriden for others.
     * @see Statement::$affectedRows
     * @return int
     */
    abstract public function countLastAffectedRows();

    /**
     * Fetches last inserted ID
     * @return int
     */
    abstract public function lastInsertId();
}
