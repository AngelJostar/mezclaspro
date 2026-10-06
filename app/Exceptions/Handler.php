<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception, \Illuminate\Http\Request $request) {
            if ($exception->getStatusCode() === 419 && $request->isMethod('POST') && $request->is('login') && !$request->expectsJson()) {
                return redirect('/login')->with('status', 'El formulario venció. Ingresa nuevamente tus credenciales para iniciar sesión.');
            }
        });
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
