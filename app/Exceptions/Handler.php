<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Throwable;
use Illuminate\Http\Request;

class Handler extends ExceptionHandler
{
    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Always return JSON response for API routes
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. No valid API token provided.',
                'error' => 'missing_token',
                'hint' => 'Include Authorization: Bearer {your_token} in request headers'
            ], 401);
        }

        // Fallback for web routes (though you shouldn't need this in API-only app)
        return response()->json([
            'success' => false,
            'message' => 'Authentication required'
        ], 401);
    }

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Reporting logic
        });
    }
}