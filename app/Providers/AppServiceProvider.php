<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
        if ($this->app->isLocal()) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
        Schema::defaultStringLength(191);

        
        JsonResource::withoutWrapping();

       
        $this->registerResponseMacros();
    }

    /**
     * Register custom response macros for consistent API responses.
     */
    protected function registerResponseMacros(): void
    {
        Response::macro('success', function ($data = null, int $status = 200) {
            return response()->json([
                'success' => true,
                'data' => $data
            ], $status);
        });

        Response::macro('error', function (string $message, int $status = 500, $errors = null) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors
            ], $status);
        });

        Response::macro('notFound', function (string $message = 'Resource not found') {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 404);
        });

        Response::macro('validationError', function (array $errors, string $message = 'Validation failed') {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors
            ], 422);
        });

        Response::macro('unauthorized', function (string $message = 'Unauthorized') {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 401);
        });

        Response::macro('forbidden', function (string $message = 'Forbidden') {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 403);
        });
    }
}