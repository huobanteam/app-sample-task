<?php

namespace App\Exceptions;

use Exception;

class RequestException extends Exception
{

    public $errors;

    public function __construct($message, $code, $errors = array())
    {
        parent::__construct($message, intval($code));
        $this->errors = $errors;
    }
}
