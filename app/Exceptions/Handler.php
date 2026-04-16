<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (ValidationException $e) {
            return ApiResponse::error('VALIDATION_ERROR', 'The given data was invalid.', 422, $e->errors());
        });

        $this->renderable(function (AuthenticationException $e) {
            return ApiResponse::error('UNAUTHORIZED', $e->getMessage() ?: 'Unauthenticated.', 401);
        });

        $this->renderable(function (AuthorizationException $e) {
            return ApiResponse::error('FORBIDDEN', $e->getMessage() ?: 'Forbidden.', 403);
        });

        $this->renderable(function (ModelNotFoundException $e) {
            return ApiResponse::error('NOT_FOUND', 'Resource not found.', 404);
        });

        $this->renderable(function (ThrottleRequestsException $e) {
            return ApiResponse::error('RATE_LIMITED', 'Too many requests.', 429);
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            return parent::render($request, $e);
        }

        return parent::render($request, $e);
    }
}
