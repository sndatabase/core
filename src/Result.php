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
 * Description of Result
 *
 * @author Darth Killer
 * @property-read int $numRows
 * @property-read int $affectedRows
 * @todo self::FETCH_NAMED : similar as self::FETCH_ASSOC + confict resolution
 */
abstract class Result extends Object implements \IteratorAggregate, ParameterTypes {
    /**
     * Fetches are as associative arrays. Default mode if not changed.
     */
    const FETCH_ASSOC = 0x1;
    /**
     * Fetches are as numerical arrays
     */
    const FETCH_NUM = 0x2;
    /**
     * Fetches are combinaisons of associative and numerical arrays
     */
    const FETCH_BOTH = 0x3; // self::FETCH_ASSOC | self::FETCH_BOTH
    /**
     * Feches are as stdClass objects
     */
    const FETCH_OBJ = 0xc; // self::FETCH_CLASS | 0x8
    /**
     * Fetches are as objects. You must specity class name, and optionnally an array of constructor args
     */
    const FETCH_CLASS = 0x4;
    /**
     * Fetches are as objects. Class name is shifted from result
     */
    const FETCH_CLASSTYPE = 0x14; // self::FETCH_CLASS | 0x10
    /**
     * Add this flag to the fetch mode to fetch properties into new object before constructor call
     */
    const FETCH_PROPS_EARLY = 0x20;
    /**
     * Fetches are put into pre-existing object
     */
    const FETCH_INTO = 0x40;
    /**
     * Fetches returns true or false. Usually combined with bindColumn().
     */
    const FETCH_BOUND = 0x80;
    /**
     * Feches a specific column from result row. Specify numerical index value (defaults to 0)
     */
    const FETCH_COLUMN = 0x100;
    /**
     * Fetches all rows of a 2-columns result as key/value pairs
     */
    const FETCHALL_KEY_PAIR = 0x200;
    /**
     * Fetches all rows as results of a specified callable
     */
    const FETCHALL_CALLBACK = 0x400;
    /**
     * Add this flag to fetch all rows, shifting first column to be used as key in the all-rows array
     */
    const FETCHALL_UNIQUE = 0x800;
    /**
     * Similar to FETCHALL_UNIQUE, except resoves naming conflicts
     */
    const FETCHALL_GROUP = 0x1000;

    /**
     *
     * @var array
     */
    private $parameters = array();

    /**
     * @param int $mode
     * @param mixed $param
     * @param array $ctor_args
     * @return boolean
     */
    abstract public function setFetchMode($mode, $param = null, array $ctor_args = array());

    /**
     * @param int $mode
     * @param mixed $param
     * @param array $ctor_args
     * @return mixed
     */
    abstract public function fetch($mode, $param = null, array $ctor_args = array());

    /**
     * @param int $mode
     * @param mixed $param
     * @param array $ctor_args
     * @return array|boolean
     */
    abstract public function fetchAll($mode, $param = null, array $ctor_args = array());
    /**
     *
     * @param int|string $col
     * @param &mixed $param
     * @param int $type
     * @return boolean
     */
    public function bindColumn($col, &$param, $type = self::PARAM_STR) {
        if(!is_int($col) and ctype_digit($tag)) $tag = intval($tag);
        elseif(!is_string($col)) return false;
        $this->parameters[$col] = array('type' => $type);
        $this->parameters[$col]['param'] =& $param;
    }

    protected function doBindParams(array $values) {
        foreach($values as $key => $value) {
            if(isset($this->parameters[$key])) {
                $param =& $this->parameters[$key]['param'];
                $type = $this->parameters[$key]['type'];
                switch($type) {
                    case self::PARAM_BOOL:
                        $param = (bool)$value;
                        break;
                    case self::PARAM_DATE:
                        $param = \DateTime::createFromFormat('Y-m-d', $value);
                        break;
                    case self::PARAM_DATETIME:
                        $param = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
                        break;
                    case self::PARAM_FLOAT:
                        $param = floatval($value);
                        break;
                    case self::PARAM_INT:
                        $param = intval($value);
                        break;
                    case self::PARAM_LOB:
                        fwrite($param, $value);
                        break;
                    case self::PARAM_NULL:
                        $param = null;
                        break;
                    case self::PARAM_STR:
                        $param = "$value";
                        break;
                    case self::PARAM_TIME:
                        $param = \DateTime::createFromFormat('H:i:s', $value);
                        break;
                }
            }
        }
    }

    /**
     * @return int
     */
    abstract protected function numRows();
    /**
     * @return int
     */
    abstract protected function affectedRows();

    public function __get($name) {
        switch ($name) {
            case 'numRows':
            case 'affectedRows':
                return $this->$name();
            default:
                return parent::__get($name);
        }
    }
}
