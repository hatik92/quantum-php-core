<?php

/**
 * Quantum PHP Framework
 *
 * An open source software development framework for PHP
 *
 * @package Quantum
 * @author Arman Ag. <arman.ag@softberg.org>
 * @copyright Copyright (c) 2018 Softberg LLC (https://softberg.org)
 * @link http://quantum.softberg.org/
 * @since 2.8.0
 */

namespace Quantum\Exceptions;

/**
 * Class EnvException
 * @package Quantum\Exceptions
 */
class EnvException extends \Exception
{
    /**
     * @return EnvException
     */
    public static function fileNotFound(): EnvException
    {
        return new static('`.env` file not found', E_ERROR);
    }
}
