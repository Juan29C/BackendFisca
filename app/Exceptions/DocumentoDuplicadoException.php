<?php

namespace App\Exceptions;

class DocumentoDuplicadoException extends ApiException
{
    protected string $errorKey = 'DOCUMENTO_DUPLICADO';
    protected int $httpStatus = 409;

    public function __construct(string $message = 'El documento ya fue registrado para este expediente.')
    {
        parent::__construct($message);
    }
}
