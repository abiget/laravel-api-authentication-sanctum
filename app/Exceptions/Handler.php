<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
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
        $this->reportable(function (Throwable $exception) {
            if ($exception instanceof \Illuminate\Database\QueryException) {
                return response()->json([
                    'message' => 'Internal Server Error',
                ], 500);
            }

            if ($exception instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Validation Failed',
                    'errors' => $exception->errors(),
                ], 422);
            }

            if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'message' => 'Model Not Found',
                ], 404);
            }

            if ($exception instanceof \Symfony\Component\Routing\Exception\MethodNotAllowedException) {
                return response()->json([
                    'message' => 'Method Not Allowed',
                ], 405);
            }

            // not found exception
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json([
                    'message' => 'Route Not Found',
                ], 404);
            }

            // any other exception
            if ($exception instanceof \Exception) {
                return response()->json([
                    'message' => 'Internal Server Error',
                ], 500);
            }
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            if(!request()->bearerToken())
                return response()->json(['error' => 'Token not provided'], Response::HTTP_UNAUTHORIZED);
            else if(!auth()->user())
                return response()->json(['error' => 'Invalide Token'], Response::HTTP_UNAUTHORIZED);
        }
    }
}
