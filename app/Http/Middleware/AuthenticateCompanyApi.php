<?php

namespace App\Http\Middleware;

use App\Services\CompanyTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica pedidos à API pública v1 através de um token Bearer (sk_live_…).
 * Disponibiliza a empresa resolvida via $request->company().
 */
class AuthenticateCompanyApi
{
    public function __construct(private readonly CompanyTokenService $tokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken() ?? $request->header('x-api-key');

        if (blank($plain)) {
            return $this->unauthorized('Falta o token de autenticação. Usa o header Authorization: Bearer sk_live_…');
        }

        $company = $this->tokens->resolve($plain);

        if (! $company) {
            return $this->unauthorized('Token inválido ou revogado.');
        }

        // Disponibiliza a empresa autenticada para os controllers.
        $request->setUserResolver(fn () => $company);
        $request->attributes->set('company', $company);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'error' => 'unauthorized',
            'message' => $message,
        ], 401);
    }
}
