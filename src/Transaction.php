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
 * Superclass for transactions
 *
 * @author Samy Naamani <samy@namani.net>
 * @property-read Connection $connection Parent connection
 * @property-read boolean $inTransaction Checks that a transaction is in progress
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 */
abstract class Transaction extends Object {
    /**
     * Parent connection (real attribute)
     * @var Connection
     */
    private $cnx;
    /**
     * Checks that a transaction is in progress (real attribute)
     * @var boolean
     */
    private $in = false;
    /**
     * Transaction name. Null if none. (real attribute)
     * @var string|null
     */
    private $name;
    /**
     * Transaction constructor
     * @param Connection $cnx Parent connection
     * @param string|null $name Transaction name. Null if none.
     * @throws DBException
     */
    public function __construct(Connection $cnx, $name = null) {
        parent::__construct();
        $this->cnx = $cnx;
        $this->name = $name;
        $this->in = $this->doStart($this->name);
    }
    public function __get($name) {
        switch($name) {
            case 'connection':
                return $this->cnx;
            case 'inTransaction':
                return $this->in;
            default:
                return parent::__get($name);
        }
    }
    /**
     * Starts transaction (driver-dependant implementation).
     * Called by constructor.
     * @param string|null $name Transaction name. Null if none.
     * @return boolean Success
     * @throws DBException
     */
    abstract protected function doStart($name = null);
    /**
     * Commit changes (driver-dependant implementation).
     * @param string|null $name Transaction name. Null if none.
     * @return boolean Commit success
     * @throws DBException
     */
    abstract protected function doCommit($name = null);
    /**
     * Rolls back changes (driver-dependant implementation).
     * @param string|null $name Transaction name. Null if none.
     * @return boolean Rollback success
     * @throws DBException
     */
    abstract protected function doRollBack($name = null);

    /**
     * Commit changes
     * @return boolean Commit success
     * @throws DBException
     */
    public function commit() {
        return $this->inTransaction ? ($this->in = $this->doCommit($this->name)) : false;
    }

    /**
     * Rolls back changes
     * @return boolean Rollback success
     * @throws DBException
     */
    public function rollBack() {
        return $this->inTransaction ? ($this->in = $this->doRollBack($this->name)) : false;
    }

    public function __destruct() {
        $this->rollBack();
    }
}
