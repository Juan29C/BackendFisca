<?php

namespace App\Exceptions;

class ExpedienteDuplicadoException extends ApiException
{
    protected string $errorKey = 'EXPEDIENTE_DUPLICADO';
    protected int $httpStatus = 409;

    public function __construct(string $message = 'El código de expediente ya existe.')
    { parent::__construct($message); }
}
