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
 * Superclass for Prepared statements
 * This abstract class allows differenciation with Paparemered statement
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 */
abstract class PreparedStatement extends Statement {

    /**
     * Parameters list
     * @var array
     */
    private $parameters = array();

    /**
     * Binds parameter to statement
     * @param string|int $tag Parameter marker in the statement. If marker is '?', use integer value here.
     * @param &mixed $param Parameter to bind, as reference
     * @param int $type Parameter type, defaults to string.
     * @return boolean
     */
    public function bindParam($tag, &$param, $type = DB::PARAM_AUTO) {
        if (!is_int($tag) and ctype_digit($tag))
            $tag = intval($tag);
        elseif (is_string($tag)) {
            if (':' != substr($tag, 0, 1))
                $tag = ":$tag";
        } else
            return false;
        $this->parameters[$tag] = array('param' => &$param, 'type' => $type);
        return true;
    }

    public function bindValue($tag, $value, $type = DB::PARAM_AUTO) {
        return $this->bindParam($tag, $value, $type);
    }

    /**
     * List of bound parameters, as associative array tag => param.
     * Each parameter is itself an array of 2 elements : 'param' => parameter value, and 'type' => parameter type
     * @return array
     */
    final protected function getParameters() {
        return $this->parameters;
    }

    /**
     * Driver-specific parameter rebinding. Called upon execution.
     */
    abstract protected function doBind();
    /**
     * Driver-specific execution, called upon execution and after parameter rebinding
     * @return boolean Success
     */
    abstract protected function doExecute();
    public function execute() {
        $this->doBind();
        return $this->doExecute();
    }

}
