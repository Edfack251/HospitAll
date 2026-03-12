<?php
namespace App\Core\Exceptions;

use Exception;

class AuthorizationException extends Exception
{
    public function __construct($message = "No tienes permisos para realizar esta acción.", $code = 403)
    {
        parent::__construct($message, $code);
    }
}
