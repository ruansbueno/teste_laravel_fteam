<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IntegrationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->header('X-Client-Id');
        if (!$clientId) {
            return response()->json(['error' => 'X-Client-Id header is required'], 400);
        }

        $requestId = (string) Str::uuid();
        Log::withContext([
            'request_id' => $requestId,
            'client_id' => $clientId,
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);
        return $response;
    }
}
