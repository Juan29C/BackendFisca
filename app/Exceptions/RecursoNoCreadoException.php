<?php

namespace App\Exceptions;

class RecursoNoCreadoException extends ApiException
{
    protected string $errorKey = 'RECURSO_NO_CREADO';
    protected int $httpStatus = 500;

    public function __construct(string $message = 'No se pudo crear el recurso.')
    { parent::__construct($message); }
}
