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

/**
 * Class specialized on parametered statements.
 * Unlike prepared statements, parametered ones only call the database on execution, and are not compile on database's end.
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 */
class ParameteredStatement extends Statement {
    /**
     * Input statement
     * @var string
     */
    private $statement;
    
    /**
     * Statement being compiled during execution
     * @var string
     */
    private $actualStatement = '';

    /**
     * Result set
     * @var Result
     */
    private $result = null;

    /**
     * Number of affected rows
     * @var int
     */
    private $affRows = 0;
    
    /**
     * Constructor
     * @param Connection $cnx Parent connection
     * @param string $statement Initial statement
     */
    public function __construct(Connection $cnx, $statement) {
        parent::__construct($cnx);
        $this->statement = $statement;
    }

    protected function param2Value($param, $type) {
        $value = parent::param2Value($param, $type);
        return ($type & self::PARAM_STR) ? $this->connection->quote($value) : $value;
    }

    protected function doBind() {
        $this->actualStatement = $this->statement;
        $params = $this->getParameters();
        foreach($params as $tag => $param) {
            if(is_int($tag)) continue;
            $value = $this->param2Value($param['param'], $param['type']);
            $this->actualStatement = str_replace($tag, $value, $this->actualStatement);
            unset($params[$tag]);
        }
        ksort($params, SORT_NUMERIC | SORT_ASC);
        foreach($params as $param) {
            $pos = strpos($this->actualStatement, '?');
            if($pos === false) break;
            $value = $this->param2Value($param['param'], $param['type']);
            $this->actualStatement = implode('', array(
                substr($this->actualStatement, 0, $pos),
                $value,
                substr($this->actualStatement, $pos + 1)
            ));
        }
    }

    public function execute() {
        $this->doBind();
        $this->result = $this->connection->query($this->actualStatement);
        $this->affRows = $this->connection->countLastAffectedRows();
        return true;
    }

    public function getResult() {
        return $this->result;
    }

    protected function getAffectedRows() {
        return $this->affRows;
    }
}
