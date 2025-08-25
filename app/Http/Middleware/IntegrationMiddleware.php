<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IntegrationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Validar header X-Client-Id
        if (!$request->hasHeader('X-Client-Id')) {
            return response()->json(['error' => 'X-Client-Id header is required'], 400);
        }

        $clientId = $request->header('X-Client-Id');
        
        // Log de entrada
        Log::info('Request received', [
            'client_id' => $clientId,
            'route' => $request->route()?->uri(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);

        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2); // ms

        // Log de saída
        Log::info('Request completed', [
            'client_id' => $clientId,
            'route' => $request->route()?->uri(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
        ]);

        return $response;
    }
}