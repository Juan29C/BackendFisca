<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        DocumentoDuplicadoException::class,
    ];

    public function register(): void
    {
        $this->reportable(function (DocumentoDuplicadoException $e) {
            return false;
        });

        $this->renderable(function (DocumentoDuplicadoException $e, $request) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
                'error'   => 'DOCUMENTO_DUPLICADO',
            ], 409);
        });

        $this->renderable(function (QueryException $e, $request) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Registro duplicado.',
                ], 409);
            }
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                // No mostramos trazas en producción
                $message = config('app.debug') ? $e->getMessage() : 'Error interno del servidor';
                $code = 500;
                
                if ($e instanceof HttpException) {
                    $code = $e->getStatusCode();
                }

                return response()->json([
                    'ok'      => false,
                    'message' => $message,
                ], $code);
            }
        });
    }
}
