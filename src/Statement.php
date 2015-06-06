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
 * Superclass for all statements, both prepared and parametered
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 * @property-read Connection $connection Parent connection
 * @property-read int $affectedRows Number of rows affected by last INSERT, UPDATE or DELETE statement
 */
abstract class Statement extends Object implements ParameterTypes {
    /**
     * Parent connection
     * @var Connection
     */
    private $cnx;
    /**
     * Statement constructor
     * @param Connection $cnx Parent connection
     */
    public function __construct(Connection $cnx) {
        parent::__construct();
        $this->cnx = $cnx;
    }

    public function __get($name) {
        switch($name) {
            case 'connection':
                return $this->cnx;
            case 'affectedRows':
                return $this->getAffectedRows();
            default:
                return parent::__get($name);
        }
    }
    /**
     * Parameters list
     * @var array
     */
    private $parameters = array();

    /**
     * Returns number of rows affected by INSERT, UPDATE or DELETE.
     * @see Statement::$affectedRows
     * @return int
     */
    abstract protected function getAffectedRows();

    /**
     * Binds parameter to statement
     * @param string|int $tag Parameter marker in the statement. If marker is '?', use integer value here.
     * @param &mixed $param Parameter to bind, as reference
     * @param int $type Parameter type, defaults to string.
     * @return boolean
     */
    public function bindParam($tag, &$param, $type = self::PARAM_STR) {
        if(!is_int($tag) and ctype_digit($tag)) $tag = intval($tag);
        elseif(is_string($tag)) {
            if(':' != substr($tag, 0, 1)) $tag = ":$tag";
        } else return false;
        $this->parameters[$tag] = array('param' => &$param, 'type' => $type);
        return true;
    }

    /**
     * Binds value to statement
     * @param string|int $tag Parameter marker in the statement. If marker is '?', use integer value here.
     * @param mixed $value Parameter to bind, as value
     * @param int $type Parameter type, defaults to string.
     * @return boolean
     */
    public function bindValue($tag, $value, $type = self::PARAM_STR) {
        return $this->bindParam($tag, $value, $type);
    }

    /**
     * Get all bound parameters, in order to bind them to inner components
     * @see doBind()
     * @return array
     */
    final protected function getParameters() {
        return $this->parameters;
    }

    /**
     * Binds parameters to statement before execution
     */
    abstract protected function doBind();

    /**
     * Executes statement
     * @return boolean
     */
    abstract public function execute();

    /**
     * Recover resultset. Null if never executed.
     * @return Result|null
     */
    abstract public function getResult();

    /**
     * Converts bound parameter into value, according to type
     * @param mixed $param Parameter to convert
     * @param int $type Parameter type
     * @return mixed Converted value ready to put into statement
     */
    protected function param2Value($param, $type) {
        if($type & self::PARAM_STR) {
            if($type == self::PARAM_FLOAT) $value = floatval($param);
            elseif($type & self::PARAM_DATETIME) {
                switch($type) {
                    case self::PARAM_DATE:
                        $format = 'Y-m-d';
                        break;
                    case self::PARAM_TIME:
                        $format = 'H:i:s';
                        break;
                    default:
                        $format = 'Y-m-d H:i:s';
                }
                if(interface_exists('\\DateTimeInterface') 
                        and $param instanceof \DateTimeInterface
                        or $param instanceof \DateTime)
                    $value = $param->format($format);
                elseif(is_string($param)) {
                    $temp = new \DateTime($param);
                    $value = $temp->format($format);
                }
                elseif(is_int($param)) $value = date($format, $param);
                else $value = 0;
            }
            elseif($type == self::PARAM_LOB) {
                rewind($param);
                $value = '';
                while(!feof($param)) $value .= fgets ($param);
            }
            return $value;
        }
        elseif($type & self::PARAM_INT) {
            if($type == self::PARAM_BOOL) return $param ? 1 : 0;
            else return intval($param);
        }
        elseif($type == self::PARAM_NULL) return 'NULL';
    }
}
