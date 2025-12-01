<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:3000',
            'http://localhost:8081',
            'http://localhost:19006',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:8081',
            'http://127.0.0.1:19006',
        ];

        $origin = $request->headers->get('Origin');
        
        // Check if origin is allowed
        $isAllowedOrigin = $origin && in_array($origin, $allowedOrigins);

        // Handle preflight requests - must return before calling $next()
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);

            // Set CORS headers for preflight
            if ($isAllowedOrigin) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
            } else {
                // Allow all origins for development (you can restrict this in production)
                $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
            }

            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');

            return $response;
        }

        // Process the request
        $response = $next($request);

        // Add CORS headers to the response
        if ($isAllowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } else if ($origin) {
            // Allow all origins for development (you can restrict this in production)
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}
