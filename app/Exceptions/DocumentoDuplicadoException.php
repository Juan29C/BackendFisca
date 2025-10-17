<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class DocumentoDuplicadoException extends Exception
{
    public function __construct(
        string $message = 'El documento ya fue registrado para este expediente.',
        int $code = 409,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render($request)
    {
        return response()->json([
            'ok'      => false,
            'message' => $this->getMessage(),
            'error'   => 'DOCUMENTO_DUPLICADO',
        ], 409);
    }
}
