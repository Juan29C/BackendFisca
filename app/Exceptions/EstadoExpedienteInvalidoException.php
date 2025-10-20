<?php

namespace App\Exceptions;

class EstadoExpedienteInvalidoException extends ApiException
{
    protected string $errorKey = 'ESTADO_EXPEDIENTE_INVALIDO';
    protected int $httpStatus = 409;

    public function __construct(string $message = 'El estado del expediente no permite subir este tipo de documento.')
    {
        parent::__construct($message);
    }
}
