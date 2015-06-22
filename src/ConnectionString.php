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
 * Connection string builder
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 */
final class ConnectionString extends Object {
    /**
     * Parts of the connection string (as URL parts), excluding attributes
     * @var array
     */
    private $parts = array();
    /**
     * Attribute list
     * @var array
     */
    private $attributes = array();

    /**
     * Connection string builder constructor
     * @param string $driver Driver name (and database type)
     */
    public function __construct($driver) {
        parent::__construct();
        $this->setDriver($driver);
    }

    /**
     * Sets driver (which defines database type)
     * @param string $driver Driver name
     */
    public function setDriver($driver) {
        $this->parts['scheme'] = strtolower($driver);
    }

    /**
     * Sets database host
     * @param string $host
     */
    public function setHost($host) {
        $this->parts['host'] = $host;
    }

    /**
     * Sets connection username
     * @param string $user
     */
    public function setUser($user) {
        $this->parts['user'] = $user;
    }

    /**
     * Sets connection password
     * @param string $pwd
     */
    public function setPwd($pwd) {
        $this->parts['pass'] = $pwd;
    }

    /**
     * Sets connection port
     * @param int $port
     */
    public function setPort($port) {
        $this->parts['port'] = $port;
    }

    /**
     * Sets database path (used by file-based database types)
     * @param string $path
     */
    public function setPath($path) {
        $this->parts['path'] = $path;
    }

    /**
     * Sets connection socket path (used by some database types)
     * @param string $socket
     */
    public function setSocket($socket) {
        $this->setPath($socket);
    }

    /**
     * Sets database name to select upon connection
     * @param string $dbname
     */
    public function setDbname($dbname) {
        $this->parts['fragment'] = $dbname;
    }

    /**
     * Sets error handling mode, as attribute
     * @param int $errmode Constant DB::ERRMODE_*
     */
    public function setErrmode($errmode) {
        if(in_array($errmode, array(DB::ERRMODE_EXCEPTION, DB::ERRMODE_FATAL_ERROR, DB::ERRMODE_WARNING, DB::ERRMODE_NOTICE, DB::ERRMODE_SILENT)))
            $this->attributes[DB::ATTR_ERRMODE] = $errmode;
    }

    /**
     * Sets charset to use, as attribute
     * @param string $charset Charset to use
     */
    public function setCharset($charset) {
        $this->attributes[DB::ATTR_CHARSET] = $charset;
    }

    /**
     * Sets default fetch mode
     * @param int $mode Fetch mode
     * @param mixed $complement_info For some fetch modes : additionnal information
     * @param array $ctor_args For DB::FETCH_CLASS : constructor argument list
     * @see FetchMode
     */
    public function setFetchMode($mode, $complement_info = null, array $ctor_args = array()) {
        $fetchMode = new FetchMode($mode, $complement_info, $ctor_args);
        $this->attributes[DB::ATTR_DEFAULT_FETCH_MODE] = serialize($fetchMode);
    }

    /**
     * Compiles and returns connection string
     * @return string
     */
    public function toString() {
        return http_build_url('', array_merge($this->parts, array('query' => http_build_query($this->attributes))));
    }

    public function __toString() {
        return $this->toString();
    }
}
