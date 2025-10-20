<?php

namespace App\Exceptions;

use Exception;
use Throwable;

abstract class ApiException extends Exception
{
    protected string $errorKey = 'API_ERROR';
    protected int $httpStatus = 400;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code ?: $this->httpStatus, $previous);
    }

    public function render($request)
    {
        return response()->json([
            'ok'      => false,
            'message' => $this->getMessage(),
            'error'   => $this->errorKey,
        ], $this->httpStatus);
    }
}
