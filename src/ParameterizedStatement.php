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
 * Class specialized on parameterized statements.
 * Unlike prepared statements, parameterized ones only call the database on execution, and are not compile on database's end.
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 */
class ParameterizedStatement extends Statement {

    /**
     * Statement parser
     * @var \PHPSQLParser
     */
    private static $parser;

    /**
     * Statement builder
     * @var \PHPSQLCreator
     */
    private static $creator;

    /**
     * Input statement
     * @var string
     */
    private $statement;

    /**
     * Result set
     * @var Result|null
     */
    private $result = null;

    /**
     * Number of affected rows
     * @var int
     */
    private $affRows = 0;

    protected static function __constructStatic() {
        if (parent::__constructStatic()) {
            return true;
        } else {
            self::$parser = new \PHPSQLParser();
            self::$creator = new \PHPSQLCreator();
        }
    }

    /**
     * Constructor
     * @param Connection $cnx Parent connection
     * @param string $statement Initial statement
     */
    public function __construct(Connection $cnx, $statement) {
        parent::__construct($cnx);
        $this->statement = $statement;
    }

    public function bindValue($tag, $value, $type = DB::PARAM_AUTO) {
        if (!is_int($tag) and ctype_digit($tag))
            $tag = intval($tag);
        elseif (is_string($tag)) {
            if (':' != substr($tag, 0, 1))
                $tag = ":$tag";
        } else
            return false;
        $this->parameters[$tag] = array('param' => $value, 'type' => $type);
        return true;
    }

    private function walk(&$elem, &$index, array &$params) {
        if (is_array($elem)) {
            if (array_key_exists('expr_type', $elem)) {
                if ($elem['expr_type'] == 'colref') {
                    if ($elem['base_expr'] == '?') {
                        if (empty($params))
                            throw new InvalidParameterNumberException;
                        if (isset($params[++$index])) {
                            $param = $params[$index];
                            $elem = array(
                                'expr_type' => 'const',
                                'base_expr' => $this->connection->quote($param['param'], $param['type'])
                            );
                            unset($params[$index]);
                        } else
                            throw new InvalidParameterNumberException;
                    }
                    elseif (preg_match('#^:[a-z][a-z0-9]*$#i', $elem['base_expr'])) {
                        if (empty($params))
                            throw new InvalidParameterNumberException;
                        $tag = $elem['base_expr'];
                        if (isset($params[$tag])) {
                            $param = $params[$tag];
                            $elem = array(
                                'expr_type' => 'const',
                                'base_expr' => $this->connection->quote($param['param'], $param['type'])
                            );
                            unset($params[$tag]);
                        } else
                            throw new InvalidParameterNumberException;
                    }
                }
            } else
                $this->walk($elem);
        }
    }

    /**
     * Binds parameters into statement to create a new statement ready to execute
     * @return string final statement
     */
    protected function doBind() {
        $parsed = self::$parser->parse($this->statement);
        $index = 0;
        $params = $this->getParameters();
        $this->walk($parsed, $index, $params);
        return self::$creator->create($parsed);
    }

    public function execute() {
        $this->result = $this->connection->query($this->doBind());
        if($this->result === false) {
            $this->result = null;
            $this->affRows = 0;
            return false;
        } else {
            $this->affRows = $this->connection->countLastAffectedRows();
            return true;
        }
    }

    public function getResultset() {
        return $this->result;
    }

    protected function getAffectedRows() {
        return $this->affRows;
    }

}
