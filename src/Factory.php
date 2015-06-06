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
 * Description of Factory
 *
 * @author Darth Killer
 */
abstract class Factory extends Object {
    /**
     *
     * @var array
     */
    private $attributes = array();
    /**
     *
     * @param string $dbtype
     * @return self
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
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function setAttribute($attribute, $value) {
        $this->attributes[$attribute] = $value;
    }
    /**
     *
     * @return array
     */
    final protected function getAttributes() {
        return $this->attributes;
    }
    /**
     * @return Connection
     */
    abstract public function getConnection();
}
