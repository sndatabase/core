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
     * Fetches returns what given callback returns
     */
    const FETCH_CALLBACK = 0x400;
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
     *
     * @var FetchMode
     */
    private $fetchMode;

    /**
     * @param int $mode
     * @param mixed $param
     * @param array $ctor_args
     */
    public function __construct(FetchMode $mode) {
        parent::__construct();
        $this->setFetchMode($mode);
    }

    /**
     * @return array|boolean
     */
    abstract protected function doFetch();

    /**
     * @param int|FetchMode $mode
     * @param mixed $param
     * @param array $ctor_args
     * @return boolean
     */
    public function setFetchMode($mode, $param = null, array $ctor_args = array()) {
        if(!($mode instanceof FetchMode)) $mode = new FetchMode($mode, $param, $ctor_args);
        $this->fetchMode = $mode;
    }

    /**
     * @param int|FetchMode $mode
     * @param mixed $param
     * @param array $ctor_args
     * @return mixed
     */
    public function fetch($mode = null, $param = null, array $ctor_args = array()) {
        if(is_null($mode)) $mode = $this->fetchMode;
        elseif(!($mode instanceof FetchMode)) $mode = new FetchMode ($mode, $param, $ctor_args);
        $row = $this->doFetch();
        if($row === false) return false;
        /* @var $row array */
        $this->doBindParams($row);
        if($mode->hasMode(self::FETCH_INTO)) return $this->fetchInto ($row, $mode->obj);
        if($mode->hasMode(self::FETCH_BOUND)) return is_array($row);
        return $this->doFetchFromRow($row, $mode);
    }

    private function doFetchFromRow(array $row, FetchMode $mode) {
        if($mode->hasMode(self::FETCH_BOTH)) return $this->fetchBoth ($row);
        if($mode->hasMode(self::FETCH_ASSOC)) return $row;
        if($mode->hasMode(self::FETCH_NUM)) return $this->fetchNum($row);
        if($mode->hasMode(self::FETCH_CLASS)) {
            if($mode->hasMode(self::FETCH_OBJ)) return $this->fetchObj ($row);
            if($mode->hasMode(self::FETCH_CLASSTYPE)) return $this->fetchClasstype ($row, $mode->hasMode (self::FETCH_PROPS_EARLY));
            return $this->fetchClass($row, $mode->classname, $mode->hasMode(self::FETCH_PROPS_EARLY), $mode->ctor_args);
        }
        if($mode->hasMode(self::FETCH_COLUMN)) return $this->doFetchColumn ($row, $mode->col);
        if($mode->hasMode(self::FETCH_CALLBACK)) return $mode->callback($row);
        return false;

    }

    /**
     * @param int|FetchMode $mode
     * @param mixed $param
     * @param array $ctor_args
     * @return array
     */
    public function fetchAll($mode = null, $param = null, array $ctor_args = array()) {
        $result = array();
        if(is_null($mode)) $mode = $this->fetchMode;
        elseif(!($mode instanceof FetchMode)) $mode = new FetchMode ($mode, $param, $ctor_args);
        while(false !== ($row = $this->doFetch())) {
            /* @var $row array */
            if($mode->hasMode(self::FETCHALL_KEY_PAIR)) {
                $result[$this->doFetchColumn ($row, 0)] = $this->doFetchColumn ($row, 1);
                continue;
            }
            if($mode->hasMode(self::FETCHALL_UNIQUE)) {
                $result[array_shift($row)] = $this->doFetchFromRow($row, $mode);
                continue;
            }
            if($mode->hasMode(self::FETCHALL_GROUP)) {
                $key = array_shift($row);
                if(isset($result[$key])) {
                    if(!is_array($result[$key])) $result[$key] = array($result[$key]);
                    $result[$key][] = $this->doFetchFromRow($row, $mode);
                } else $result[$key] = $this->doFetchFromRow ($row, $mode);
            }
            if(false !== ($v = $this->doFetchFromRow($row, $mode)))
                $result[] = $v;
        }
        return $result;
    }
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

    public function getIterator() {
        return new ResultIterator($this);
    }

    final private function fetchNum(array $row) {
        return array_values($row);
    }

    final private function fetchBoth(array $row) {
        return array_merge($this->fetchAssoc($row), $this->fetchNum($row));
    }

    final private function fetchObj(array $row) {
        return (object)$row;
    }

    final private function fetchClass(array $row, $classname, $props_early = false, array $ctor_args = array()) {
        $refl = new \ReflectionClass($classname);
        $obj = $props_early ? $refl->newInstanceWithoutConstructor() : $refl->newInstanceArgs($ctor_args);
        $this->doFetchInto($row, $obj);
        if($props_early) {
            $reflObj = new \ReflectionObject($obj);
            $reflObj->getConstructor()->invokeArgs($obj, $ctor_args);
        }
        return $obj;
    }

    final private function fetchClasstype(array $row, $props_early = false) {
        $classname = array_shift($row);
        return $this->doFetchClass($row, $classname, $props_early);
    }

    final private function fetchInto(array $row, &$obj) {
        $reflObj = new \ReflectionObject($obj);
        foreach($$row as $key => $val) {
            if($reflObj->hasProperty($key)) {
                $reflProp = $reflObj->getProperty($key);
                $reflProp->setValue($obj, $val);
            }
            else $obj->$key = $val;
        }
    }

    final private function doFetchColumn(array $row, $index) {
        return isset($row[$index]) ? $row[$index] : null;
    }

    final public function fetchColumn($index) {
        $row = $this->fetch(self::FETCH_NUM);
        if($row === false) return false;
        return $this->doFetchColumn($row, $index);
    }
}
