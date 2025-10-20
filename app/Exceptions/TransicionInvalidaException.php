<?php

namespace App\Exceptions;

use RuntimeException;

class TransicionInvalidaException extends RuntimeException
{
    public function __construct(string $message = 'Transición de estado inválida.')
    { parent::__construct($message); }
}
