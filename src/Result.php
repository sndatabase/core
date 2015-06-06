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
 * Superclass for result sets
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 * @property-read int $numRows Number of found rows for SELECT statements
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
     * Bound parameters to populate upon fetch
     * @var array
     */
    private $parameters = array();

    /**
     * Default fetch mode for the result set
     * @var FetchMode
     */
    private $fetchMode;

    /**
     * Constructor
     * @param FetchMode $mode Default fetch mode for the result set
     */
    public function __construct(FetchMode $mode) {
        parent::__construct();
        $this->setFetchMode($mode);
    }

    /**
     * Real fetch done by inner components
     * @return array|boolean Associative array, or false if nothing to fetch
     */
    abstract protected function doFetch();

    /**
     * Changes default fetch mode for this result set
     * @param int|FetchMode $mode New mode. Either a proper FetchMode, or a FETCH_* flag combinaison
     * @param mixed $param For some fetch modes, complementary parameter. See examples
     * @param array $ctor_args For FETCH_CLASS only, array of constructor arguments.
     */
    public function setFetchMode($mode, $param = null, array $ctor_args = array()) {
        if(!($mode instanceof FetchMode)) $mode = new FetchMode($mode, $param, $ctor_args);
        $this->fetchMode = $mode;
    }

    /**
     * Fetches next row
     * @param int|FetchMode $mode New mode, if needed. Either a proper FetchMode, or a FETCH_* flag combinaison
     * @param mixed $param For some fetch modes, complementary parameter. See examples
     * @param array $ctor_args For FETCH_CLASS only, array of constructor arguments.
     * @return mixed Fetched row
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

    /**
     * Converts row (as an array) into fetched row (as intended result from fetch())
     * @param array $row Row to convert
     * @param FetchMode $mode Fetch mode
     * @return mixed Converted row
     */
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
     * Fetches all row, as a global array
     * @param int|FetchMode $mode New mode, if needed. Either a proper FetchMode, or a FETCH_* flag combinaison
     * @param mixed $param For some fetch modes, complementary parameter. See examples
     * @param array $ctor_args For FETCH_CLASS only, array of constructor arguments.
     * @return array List of all rows found
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
     * Bind a column from the result to a parameter
     * @param int|string $col Column to bind
     * @param &mixed $param Parameter to bind
     * @param int $type Parameter type
     */
    public function bindColumn($col, &$param, $type = self::PARAM_STR) {
        if(!is_int($col) and ctype_digit($tag)) $tag = intval($tag);
        elseif(!is_string($col)) return false;
        $this->parameters[$col] = array('type' => $type);
        $this->parameters[$col]['param'] =& $param;
    }

    /**
     * Populates bound parameters with column values
     * @param array $values
     */
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
     * Returns number of found rows
     * @see Result::$numRows
     * @return int
     */
    abstract protected function numRows();

    public function __get($name) {
        switch ($name) {
            case 'numRows':
                return $this->$name();
            default:
                return parent::__get($name);
        }
    }

    public function getIterator() {
        return new ResultIterator($this);
    }

    /**
     * Converts a row into a numeric-indexed array
     * @param array $row
     * @return array
     */
    final private function fetchNum(array $row) {
        return array_values($row);
    }

    /**
     * Merges row (as associative array) and its numeric-indexed conversion
     * @param array $row
     * @return array
     */
    final private function fetchBoth(array $row) {
        return array_merge($this->fetchAssoc($row), $this->fetchNum($row));
    }

    /**
     * Converts row into object
     * @param array $row
     * @return object
     */
    final private function fetchObj(array $row) {
        return (object)$row;
    }

    /**
     * Converts row into objet of a specific class
     * @param array $row Row to convert
     * @param string $classname Class name
     * @param boolean $props_early If constructeur must be called before or after populating properties
     * @param array $ctor_args Constructor arguments
     * @return object
     */
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

    /**
     * Converts row into object, using (and extracting) first value as class name
     * @param array $row Row to convert
     * @param boolean $props_early If constructeur must be called before or after populating properties
     * @return object
     */
    final private function fetchClasstype(array $row, $props_early = false) {
        $classname = array_shift($row);
        return $this->doFetchClass($row, $classname, $props_early);
    }

    /**
     * Fetches row into existing object
     * @param array $row Row to fetch
     * @param type $obj Object to fetch into
     */
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

    /**
     * Inner component : gets an element of a row based on numerical index
     * @param array $row Row to read
     * @param type $index Numerical index
     * @return mixed|null Null if invalid index
     */
    final private function doFetchColumn(array $row, $index) {
        return isset($row[$index]) ? $row[$index] : null;
    }

    /**
     * Fetches row, then returns element of row based on numerical index
     * @param int $index Numerical index to use
     * @return boolean|mixed|null Found element. Null if invalid index. False if nothing to fetch.
     */
    final public function fetchColumn($index = 0) {
        $row = $this->fetch(self::FETCH_NUM);
        if($row === false) return false;
        return $this->doFetchColumn($row, $index);
    }
}
