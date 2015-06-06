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
 * Fetch Mode descriptior
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 * @property-read string|null $classname For FETCH_CLASS  mode : class name to use
 * @property-read array $ctor_args For FETCH_CLASS mode : constructor arguments
 * @property-read object|null $obj For FETCH_INTO mode : object to fetch data into
 * @property-read int|null $col For FETCH_COLUMN mode : numeric index to fetch
 * @property-read callable|null $callback for FETCH_CALLBACK mode : callable to use
 */
class FetchMode extends Object {
    private $_mode;
    private $_classname = null;
    private $_ctor_args = array();
    private $_obj = null;
    private $_col = null;
    private $_callback = null;

    /**
     * Fetch mode constructor
     * @param int $mode Actual fetch mode : combinaison of Result::FETCH_* constants
     * @param mixed $param For some modes, additionnal parameter
     * @param array $ctor_args For FETCH_CLASS mode : constructor arguments
     */
    public function __construct($mode, &$param = null, array $ctor_args = array()) {
        parent::__construct();
        $this->_mode = $mode;
        if($this->hasMode(Result::FETCH_CLASS)
                and !$this->hasMode(Result::FETCH_CLASSTYPE)
                and !$this->hasMode(Result::FETCH_OBJ)) {
            $this->_classname = $param;
            $this->_ctor_args = $ctor_args;
        }
        elseif($this->hasMode(Result::FETCH_INTO)) $this->_obj =& $param;
        elseif($this->hasMode(Result::FETCH_COLUMN)) $this->_col = $param;
        elseif($this->hasMode(Result::FETCH_CALLBACK)) $this->_callback = $param;
    }

    public function __get($name) {
        switch($name) {
            case 'classname':
            case 'col':
            case 'ctor_args':
            case 'callback':
                $attr = "_$name";
                return $this->$attr;
            case 'obj':
                return $this->_obj;
            default:
                return parent::__get($name);
        }
    }

    /**
     * Checks if flag mode is within fetch mode
     * @param int $mode Flag mode to check for
     * @return boolean
     */
    public function hasMode($mode) {
        return $mode == ($this->_mode & $mode);
    }
}
