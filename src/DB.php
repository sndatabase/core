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

/**
 * Main Database class.
 * Houses constants, and grants access to driver factories
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 */
final class DB {
    const ATTR_CHARSET = 'charset';
    const ATTR_DEFAULT_FETCH_MODE = 'fetchmode';

    const PARAM_AUTO = 0x0;
    const PARAM_STR = 0x1;
    const PARAM_DATE = 0x3; // 0x2 | PARAM_STR
    const PARAM_TIME = 0x5; // 0x4 | PARAM_STR
    const PARAM_DATETIME = 0x7; // PARAM_DATE | PARAM_TIME
    const PARAM_INT = 0x10;
    const PARAM_BOOL = 0x20;
    const PARAM_FLOAT = 0x40;
    const PARAM_NULL = 0x80;

    const CURSOR_FIRST = 1;
    const CURSOR_PREV = 2;
    const CURSOR_NEXT = 3;
    const CURSOR_LAST = 4;

    const FETCH_ASSOC = 0x1;
    const FETCH_NUM = 0x2;
    const FETCH_BOTH = 0x3; // FETCH_ASSOC | FETCH_NUM
    const FETCH_CLASS = 0x4;
    const FETCH_OBJ = 0x8;
    const FETCH_CLASSTYPE = 0x14; // 0x10 | FETCH_CLASS
    const FETCH_INTO = 0x20;
    const FETCH_COLUMN = 0x40;
    const FETCHALL_KEY_PAIR = 0x100;
    const FETCHALL_UNIQUE = 0x200;
    const FETCH_PROPS_EARLY = 0x10000;
    /**
     * List of registered driver factories
     * @var Factory[]
     */
    private static $factories = array();

    /**
     * This class is static, thus the constructor is inaccessible and never used
     */
    final private function __construct() {}

    /**
     * Registers a driver, based on its factory
     * @param Factory $factory
     */
    final public static function register(Factory $factory) {
        if(!isset(self::$factories[$factory->driver]))
            self::$factories[$factory->driver] = $factory;
    }

    /**
     * Unregisters a driver, based on its factory
     * @param Factory $factory
     */
    final public static function unregister(Factory $factory) {
        unset(self::$factories[$factory->driver]);
    }

    /**
     * Get a factory for the requested driver
     * @param string $driver Driver name. Case insensitive.
     * @return Factory Matching factory.
     * @throws DriverException
     */
    final private static function getFactory($driver) {
        $key = array_search(strtolower($driver), array_map('strtolower', array_keys(self::$factories)));
        if($key === false) throw new DriverException('Driver not found');
        $factories = array_values(self::$factories);
        return $factories[$key];
    }

    /**
     * Get connection from connection string
     * @param string $cnxString Connection string
     * @return Connection Connection instance
     * @throws ConnectionFailedException
     * @throws DriverException
     */
    final public static function getConnection($cnxString) {
        $factory = self::getFactory(parse_url($cnxString, PHP_URL_SCHEME));
        return $factory->getConnection($cnxString);
    }

    /**
     * Requests for a connection string builder
     * @param string $driver Driver name
     * @return ConnectionString
     * @throws DriverException
     */
    final public static function getConnectionStringBuilder($driver) {
        $factory = self::getFactory($driver);
        return new ConnectionString($factory->driver);
    }
}
