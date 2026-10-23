<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateScoreBoxApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = (string) config('services.scorebox.api_key', '');

        // If no key is configured in non-production, allow request for ease of testing unless explicitly set
        if ($configuredKey === '') {
            if (app()->isProduction()) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key de ScoreBox no configurada en el servidor.',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            return $next($request);
        }

        $providedKey = $request->header('X-ScoreBox-Api-Key')
            ?? $request->bearerToken()
            ?? $request->query('api_key');

        if (! is_string($providedKey) || $providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API Key de ScoreBox no válida o no proporcionada.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
