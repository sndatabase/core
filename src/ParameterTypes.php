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
 * Parameters type interface
 * Declares parameter types as constants for both Statement and Result classes.
 * As this version of the api is meant to be compatible PHP 5.3, a trait was not an option.
 *
 * @author Samy Naamani <samy@namani.net>
 * @license https://github.com/sndatabase/core/blob/master/LICENSE MIT
 */
interface ParameterTypes {
    const PARAM_NULL = 0x0;
    const PARAM_STR = 0x1;
    const PARAM_FLOAT = 0x3; // self::PARAM_STR | 0x2
    const PARAM_DATE = 0x5; // self::PARAM_STR | 0x4
    const PARAM_TIME = 0x9; // self::PARAM_STR | 0x8
    const PARAM_DATETIME = 0xd; // self::PARAM_DATE | self::PARAM_TIME
    const PARAM_INT = 0x10;
    const PARAM_BOOL = 0x30; // self::PARAM_INT | 0x20
    const PARAM_LOB = 0x41; // self::PARAM_STR | 0x40
}
