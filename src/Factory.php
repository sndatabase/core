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
 * Factory superclass
 * Driver factories assist into building database connections
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 * @property-read string $driver Driver name
 */
abstract class Factory extends Object {
    /**
     * Driver name (real attribute)
     * @var string
     */
    private static $_driver = '';

    /**
     * Sets up driver name. Subclass shall call this method once before anything else.
     * For instance, subclass can call this method from its static constructor overriden from Object.
     * @param string $driver Driver name
     */
    final protected static function setDriver($driver) {
        if(empty(static::$_driver)) static::$_driver = $driver;
    }

    public function __get($name) {
        switch($name) {
            case 'driver':
                return static::$_driver;
            default:
                return parent::__get($name);
        }
    }

    /**
     * Get connection from connection string
     * @param string $cnxString Connection string
     * @return Connection Connection instance
     * @throws ConnectionFailedException
     */
    abstract public function getConnection($cnxString);
}
