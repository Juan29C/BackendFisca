<?php

namespace App\Exceptions;

class TransicionInvalidaException extends ApiException
{
    protected string $errorKey = 'TRANSICION_INVALIDA';
    protected int $httpStatus = 409;

    public function __construct(string $message = 'Transición de estado inválida.')
    {
        parent::__construct($message);
    }
}
