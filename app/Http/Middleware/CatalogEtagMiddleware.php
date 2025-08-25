<?php

namespace App\Http\Middleware;

use App\Services\CatalogVersion;
use Closure;
use Illuminate\Http\Request;

class CatalogEtagMiddleware
{
    public function __construct(private CatalogVersion $version) {}

    public function handle(Request $request, Closure $next)
    {
        $etag = '"'.$this->version->get().'"';

        $ifNoneMatch = $request->headers->get('If-None-Match');
        if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        return $response->header('ETag', $etag);
    }
}
