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
 * Description of Statement
 *
 * @author Darth Killer
 * @property-read Connection $connection
 */
abstract class Statement extends Object implements ParameterTypes {
    /**
     *
     * @var Connection
     */
    private $cnx;
    /**
     *
     * @param Connection $cnx
     */
    public function __construct(Connection $cnx) {
        parent::__construct();
        $this->cnx = $cnx;
    }

    public function __get($name) {
        switch($name) {
            case 'connection':
                return $this->cnx;
            default:
                return parent::__get($name);
        }
    }
    /**
     *
     * @var array
     */
    private $parameters = array();

    /**
     *
     * @param string|int $tag
     * @param &mixed $param
     * @param int $type
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
     *
     * @param string|int $tag
     * @param mixed $value
     * @param int $type
     * @return boolean
     */
    public function bindValue($tag, $value, $type = self::PARAM_STR) {
        return $this->bindParam($tag, $value, $type);
    }

    /**
     *
     * @return array
     */
    final protected function getParameters() {
        return $this->parameters;
    }

    abstract protected function doBind();

    /**
     * @return boolean
     */
    abstract public function execute();

    /**
     * @return Result|null
     */
    abstract public function getResult();

    /**
     *
     * @param mixed $param
     * @param int $type
     * @return mixed Converted value ready to inject into query
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
