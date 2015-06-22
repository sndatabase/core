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
 * Superclass for result sets
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 * @property-read Connection $connection Parent connection
 * @property-read int $numRows Number of found rows for SELECT statements
 * @todo self::FETCH_NAMED : similar as self::FETCH_ASSOC + confict resolution
 */
abstract class Result extends Object implements \Countable, \IteratorAggregate {
    /**
     * Bound parameters to populate upon fetch
     * @var array
     */
    private $parameters = array();

    /**
     * Prefetched data, as array of associative arrays
     * @var array Prefetched rows
     */
    private $prefetched = array();

    /**
     * Default fetch mode for this result set
     * @var FetchMode
     */
    private $fetchMode;

    /**
     * Parent connection (real attribute)
     * @var Connection
     */
    private $cnx;

    /**
     * Cursor fetching mode : constant DB::CURSOR_*
     * @var int
     */
    private $cursorMode = DB::CURSOR_FIRST;

    /**
     * Result set constructor
     * @param Connection $cnx Parent connection
     */
    public function __construct(Connection $cnx) {
        parent::__construct();
        $this->cnx = $cnx;
        $this->fetchMode = $this->connection->getDefaultFetchMode();
        $this->prefetch();
    }

    public function __get($name) {
        switch($name) {
            case 'connection':
                return $this->cnx;
            case 'numRows':
                return count($this);
            default:
                return parent::__get($name);
        }
    }

    /**
     * Sets fetch mode for this result set
     * @param int $mode Fetch mode : combinaison of constants DB::FETCH_*
     * @param mixed $complement_info For some modes : additionnal information
     * @param array $ctor_args For DB::FETCH_CLASS : constructor argument list
     * @see FetchMode
     */
    public function setFetchMode($mode, $complement_info = null, array $ctor_args = array()) {
        $this->fetchMode = new FetchMode($mode, $complement_info, $ctor_args);
    }

    /**
     * Sets cursor fetching mode for next fetch
     * @param int $mode Constant DB::CURSOR_*
     * @throws \InvalidArgumentException
     */
    public function setCursorMode($mode) {
        switch($mode) {
            case DB::CURSOR_FIRST:
            case DB::CURSOR_PREV:
            case DB::CURSOR_NEXT:
            case DB::CURSOR_LAST:
                $this->cursorMode = $mode;
                break;
            default:
                throw new \InvalidArgumentException('Invalid cursor mode');
        }
    }

    /**
     * Driver-specific fetch. Fetch mode is expected to be array_assoc only.
     * @return array|boolean fetched row, as associative array. If no row left, returns false.
     */
    abstract protected function doFetch();

    /**
     * Prefetch method allows to load in memory all the rows quickly and allows for a faster acces with scrollable possibilities.
     * @staticvar boolean $prefetched Variable checking if prefetch has already been done. If so, won't be done a second time.
     */
    final private function prefetch() {
        static $prefetched = false;
        if(!$prefetched) {
            $prefetched = true;
            while($row = $this->doFetch()) $this->prefetched[] = $row;
        }
    }

    /**
     * Parse a fetched row, using fetch mode.
     * This method also populates bound columns.
     * @param array $row Fetched row to parse
     * @param FetchMode $fetchmode Fetch mode to use
     * @return mixed Fetched row, after transformation and parsing based on fetch mode
     */
    final private function parseFetched(array $row, FetchMode $fetchmode) {
        foreach($row as $tag => $value) {
            if(isset($this->parameters[$tag])) {
                $param =& $this->parameters[$tag]['param'];
                $type = $this->parameters[$tag]['type'];
                $param = $this->connection->quote($value, $type);
            }
        }
        if($fetchmode->hasMode(DB::FETCH_BOTH)) return array_merge ($row, array_values ($row));
        if($fetchmode->hasMode(DB::FETCH_ASSOC)) return $row;
        if($fetchmode->hasMode(DB::FETCH_NUM)) return array_values ($row);
        if($fetchmode->hasMode(DB::FETCH_COLUMN)) {
            $values = array_values($row);
            return isset($values[$fetchmode->col]) ? $values[$fetchmode->col] : null;
        }
        if($fetchmode->hasMode(DB::FETCH_OBJ)) return (object)$row;
        if($fetchmode->hasMode(DB::FETCH_CLASS)) {
            $classname = $fetchmode->hasMode(DB::FETCH_CLASSTYPE) ? array_shift($row) : $fetchmode->classname;
            $refl = new \ReflectionClass($classname);
            $obj = $fetchmode->hasMode(DB::FETCH_PROPS_EARLY)
                    ? $refl->newInstanceWithoutConstructor()
                    : $refl->newInstanceArgs($fetchmode->ctor_args);
            $refl = new \ReflectionObject($obj);
            foreach($row as $tag => $value) {
                if($refl->hasProperty($tag)) $refl->getProperty($tag)->setValue($obj, $value);
                else $obj->$tag = $value;
            }
            if($fetchmode->hasMode(DB::FETCH_PROPS_EARLY))
                $refl->getConstructor()->invokeArgs($obj, $fetchmode->ctor_args);
            return $obj;
        }
        if($fetchmode->hasMode(DB::FETCH_INTO)) {
            $refl = new \ReflectionObject($fetchmode->obj);
            foreach($row as $tag => $value) {
                if($refl->hasProperty($tag)) $refl->getProperty($tag)->setValue($obj, $value);
                else $obj->$tag = $value;
            }
        }
    }

    /**
     * Fetches next row
     * @param int|null $mode New fetch mode. If null, the result set default fetch mode is used.
     * @param mixed $complement_info For some fetch modes, additionnal information
     * @param array $ctor_args For DB::FETCH_CLASS : list of constructor arguments
     * @return mixed|boolean Fetched row, false if none left.
     * @see Result::setFetchMode()
     */
    public function fetch($mode = null, $complement_info = null, array $ctor_args = array()) {
        $fetchmode = is_null($mode) ? $this->fetchMode : new FetchMode($mode, $complement_info, $ctor_args);
        /* @var $fetchmode FetchMode */
        switch($this->cursorMode) {
            case DB::CURSOR_FIRST:
                $this->rewind();
                break;
            case DB::CURSOR_PREV:
                $this->prev();
                break;
            case DB::CURSOR_NEXT:
                $this->next();
                break;
            case DB::CURSOR_LAST:
                $this->end();
                break;
        }
        $this->setCursorMode(DB::CURSOR_NEXT);
        return $this->valid() ? $this->parseFetched($this->current(), $fetchmode) : false;
    }

    /**
     * Fetches all found rows as an array
     * @param int|null $mode Fetch mode to use. If null, will use the result set default fetch mode
     * @param mixed $complement_info For some fetch modes, additionnal information
     * @param array $ctor_args For DB::FETCH_CLASS : list of constructor arguments
     * @return array list of fetched rows
     * @see Result::setFetchMode
     */
    public function fetchAll($mode = null, $complement_info = null, array $ctor_args = array()) {
        $fetchmode = is_null($mode) ? $this->fetchMode : new FetchMode($mode, $complement_info, $ctor_args);
        /* @var $fetchmode FetchMode */
        $rows = array();
        foreach($this->prefetched as $row) {
            if($fetchmode->hasMode(DB::FETCHALL_KEY_PAIR)) {
                $rows[array_shift($row)] = array_shift($row);
            } elseif($fetchmode->hasMode(DB::FETCHALL_UNIQUE)) {
                $rows[array_shift($row)] = $this->parseFetched($rows, $fetchmode);
            } else {
                $rows[] = $this->parseFetched($rows, $fetchmode);
            }
        }
        return $rows;
    }

    public function getIterator() {
        return new ResultIterator($this);
    }

    private function rewind() {
        reset($this->prefetched);
    }

    private function prev() {
        if($this->valid()) prev($this->prefetched);
    }

    private function next() {
        if($this->valid()) next($this->prefetched);
    }

    private function end() {
        end($this->prefetched);
    }

    private function valid() {
        return !is_null(key($this->prefetched));
    }

    private function current() {
        return current($this->prefetched);
    }

    public function count($mode = 'COUNT_NORMAL') {
        return count($this->prefetched, $mode);
    }
}
