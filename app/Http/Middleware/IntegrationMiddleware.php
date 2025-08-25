<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class IntegrationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->headers->get('X-Request-Id', (string) Str::uuid());
        $clientId = $request->headers->get('X-Client-Id');

        ## verificação do header 
        if(empty($clientId)){
            return response()->json([
                'error' => 'missing_header',
                'message' => 'O Header X-Cliente-Id é obrigatório'
            ],400)->withHeaders([
                'X-Request-Id' => $requestId
            ]);
        }

        ## configuração do rate limiting 
        $maxAttempts = (int) config('integrations.rate_limit_per_minute', 60);
        $decaySeconds = (int) config('integrations.rate_limit_decay', 60);

        $key = sprintf('integrations:%s', $clientId);
        if(RateLimiter::tooManyAttempts($key, $maxAttempts)){
            $retryAfter =  RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'rate_limited',
                'message' => 'Muitas requisições desse cliente',
                'retry_after' => $retryAfter
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-Request-Id'=> $requestId
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);

        ## medir tempo de resposta
        $startedAt = microtime(true);

        ## logs de entrada
        Log::info('integration.request.in', [
            'request_id' => $requestId,
            'client_id'  => $clientId,
            'method'     => $request->getMethod(),
            'route'      => $request->path(),
            'query'      => $request->query()
        ]);

        ## mantém request id
        Log::withContext([
            'request_id' => $requestId,
            'client_id'  => $clientId
        ]);

        /** @var Response $response */
        $response = $next($request);

        ## retorno sobre tempo e o log de saída
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        Log::info('integration.request.out', [
            'request_id'  => $requestId,
            'client_id'   => $clientId,
            'method'      => $request->getMethod(),
            'route'       => $request->path(),
            'status'      => $response->getStatusCode(),
            'duration_ms' => $durationMs
        ]);

        ## set de headers úteis
        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-Client-Id', $clientId);

        return $response;
    }
}
