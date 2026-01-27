<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class LogApiRequests
{
    /**
     * Log API requests for debugging and monitoring.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $startTime = microtime(true);

        $response = $next($request);

        // Ensure we have a Response object
        if (! $response instanceof Response) {
            return $response;
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        // Only log if enabled via config
        if (config('app.log_api_requests', false)) {
            Log::info('API Request', [
                'timestamp' => now()->toIso8601String(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'status' => $response->getStatusCode(),
                'duration_ms' => $duration,
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Add performance header
        $response->headers->set('X-Response-Time', $duration.'ms');

        return $response;
    }
}
