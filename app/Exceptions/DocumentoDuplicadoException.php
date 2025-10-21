<?php

namespace App\Exceptions;

class DocumentoDuplicadoException extends ApiException
{
    protected string $errorKey = 'DOCUMENTO_YA_TIENE_ARCHIVO';
    protected int $httpStatus = 409;

    public function __construct(string $message = 'Este documento ya tiene un archivo cargado.')
    {
        parent::__construct($message);
    }
}
