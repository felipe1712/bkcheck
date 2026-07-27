<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('tenant/*') || $request->is('superadmin/*')) {
                return response('<div style="padding:20px;font-family:sans-serif;background:#fff0f0;border:2px solid red;border-radius:8px;margin:20px;"><h2>Diagnóstico de Error (500)</h2><p><strong>Excepción:</strong> ' . e(get_class($e)) . '</p><p><strong>Mensaje:</strong> ' . e($e->getMessage()) . '</p><p><strong>Archivo:</strong> ' . e($e->getFile()) . ' (Línea ' . $e->getLine() . ')</p><pre style="background:#222;color:#fff;padding:15px;overflow:auto;max-height:400px;font-size:12px;">' . e($e->getTraceAsString()) . '</pre></div>', 500);
            }
        });
    }
}
