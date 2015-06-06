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
 * Factory superclass
 * This class provides access to real factories via its static method.
 * Real factories are subclasses of this class
 * @see Factory::getFactory
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 */
abstract class Factory extends Object {
    /**
     * Factory parameters
     * @var array
     */
    private $parameters = array();
    
    /**
     * Private constructor, public creation is forbidden
     */
    private function __construct() {
        parent::__construct();
    }
    /**
     * Factory creation.
     * For instance, if database type asked for is "MySQL", then
     * Factory is exception to be \SNDatabase\Impl\MySQLFactory
     * @param string $dbtype Database type.
     * @return self New factory
     * @throws DriverException
     */
    final public static function getFactory($dbtype) {
        parent::__construct();
        $class = sprintf('%s\\Impl\\%sFactory', __NAMESPACE__, $dbtype);
        if(class_exists($class) and is_subclass_of($class, self)) {
            return new $class();
        } else throw new DriverException("Driver $dbtype not found");
    }
    /**
     * Sets a parameter for connection creation
     * @param string $parameter
     * @param mixed $value
     */
    public function setParameter($parameter, $value) {
        $this->parameters[$parameter] = $value;
    }
    /**
     * Gather a parameter for connection creation
     * @param string $parameter
     * @return mixed|null Null if not found
     */
    public function getParameter($parameter) {
        return isset($this->parameters[$parameter]) ? $this->parameters[$parameter] : null;
    }
    /**
     * Creates connection
     * @return Connection
     */
    abstract public function getConnection();
}
