<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege o webhook usado pelo sistema de chamados de terceiros.
 */
class EnsureIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('atendly.integration_token');

        if ($token === '' || ! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Token de integração inválido.'], 401);
        }

        return $next($request);
    }
}
