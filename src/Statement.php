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
 * Superclass for all statements, both prepared and parametered
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 * @property-read Connection $connection Parent connection
 * @property-read int $affectedRows Number of rows affected by last INSERT, UPDATE or DELETE statement
 */
abstract class Statement extends Object implements ParameterTypes {
    /**
     * Parent connection
     * @var Connection
     */
    private $cnx;
    /**
     * Statement constructor
     * @param Connection $cnx Parent connection
     */
    public function __construct(Connection $cnx) {
        parent::__construct();
        $this->cnx = $cnx;
    }

    public function __get($name) {
        switch($name) {
            case 'connection':
                return $this->cnx;
            case 'affectedRows':
                return $this->getAffectedRows();
            default:
                return parent::__get($name);
        }
    }

    /**
     * Binds value to statement, according to type and tag
     * @param int|string $tag Value tag. Integer for '?' marks, otherwise marked label started by ':'
     * @param mixed $value Value to bind
     * @param int $type Type to use to bind the value. Constant DB::PARAM_*. Defaults to DB::PARAM_AUTO, which automatically checks the type of the value
     */
    abstract public function bindValue($tag, $value, $type = DB::PARAM_AUTO);
    /**
     * Executes statement
     * @return boolean Execution success
     * @throws DBException
     * @throws InvalidParameterNumberException
     */
    abstract public function execute();
    /**
     * Get result set after execution
     * @return Result|null Result set. Null if N/A
     */
    abstract public function getResultset();
    /**
     * Number of rows affected by last INSERT, UPDATE or DELETE statement
     * @return int Number of rows
     */
    abstract protected function getAffectedRows();
}
