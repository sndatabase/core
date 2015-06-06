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
 * Description of Connection
 *
 * @author Darth Killer
 */
abstract class Connection extends Object {
    /**
     * @param string $statement
     * @return Result
     */
    public function query($statement) {
        $stmt = $this->queryWithParam($statement);
        $stmt->execute();
        return $stmt->getResult();
    }

    /**
     * @param string $statement
     * @return int
     */
    public function exec($statement) {
        return $this->query($statement)->affectedRows;
    }

    /**
     * @param string $statement
     * @return ParameteredStatement
     */
    abstract public function queryWithParam($statement);

    /**
     * @param string $statement
     * @return PreparedStatement
     */
    abstract public function prepare($statement);

    /**
     * @return Transaction
     */
    abstract public function startTransaction();

    /**
     * @param string|int $attribute
     * @param mixed $value
     * @return boolean
     */
    abstract public function setAttribute($attribute, $value);
}
