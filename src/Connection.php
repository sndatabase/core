<?php

/*
 * The MIT License
 *
 * Copyright 2015 Darth Killer.
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
 * Description of Connection
 *
 * @author Darth Killer
 */
abstract class Connection extends Object {
    const ATTR_ERRMODE = 0;
    const ATTR_DEFAULT_FETCH_MODE = 1;

    const ERRMODE_EXCEPTION = 100;
    const ERRMODE_ERROR = 101;
    const ERRMODE_WARNING = 102;
    const ERRMODE_NOTICE = 104;
    const ERRMODE_SILENT = 105;

    private $attributes = array();
    public function __construct() {
        parent::__construct();
        $this->setAttribute (self::ATTR_DEFAULT_FETCH_MODE, Result::FETCH_ASSOC);
        $this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
    }
    /**
     * @param string $statement
     * @return Result|boolean
     */
    abstract public function query($statement);

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
     * @param string $statement
     * @return int|boolean
     */
    public function exec($statement) {
        $stmt = $this->query($statement);
        return ($stmt instanceof Result) ? $stmt->affectedRows : false;
    }

    /**
     * @param string $statement
     * @return ParameteredStatement
     */
    public function queryWithParam($statement) {
        return new ParameteredStatement($this, $statement);
    }

    /**
     * @param string $statement
     * @return PreparedStatement|boolean
     */
    abstract public function prepare($statement);

    /**
     * @return Transaction
     */
    abstract public function startTransaction();

    /**
     * @param int $attribute
     * @param mixed $value
     */
    public function setAttribute($attribute, $value) {
        $this->attributes[$attribute] = $value;
    }

    /**
     *
     * @param int $attribute
     * @return mixed
     */
    public function getAttribute($attribute) {
        return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
    }

    /**
     * @param string $string
     * @return string
     */
    abstract public function quote($string);

    /**
     * @return int
     */
    abstract public function countLastAffectedRows();
}
