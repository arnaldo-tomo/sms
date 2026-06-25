<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyToken;
use Illuminate\Support\Str;

class CompanyTokenService
{
    /**
     * Gera um novo token para a empresa. Devolve o token em texto simples
     * (mostrado apenas UMA vez — só o hash é guardado).
     *
     * @return array{token: CompanyToken, plain: string}
     */
    public function generate(Company $company, string $name = 'default'): array
    {
        $plain = 'sk_live_'.Str::random(40);

        $token = $company->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plain),
            'prefix' => substr($plain, 0, 16).'…',
        ]);

        return ['token' => $token, 'plain' => $plain];
    }

    /**
     * Resolve a empresa a partir de um token em texto simples.
     * Atualiza last_used_at. Devolve null se inválido/revogado.
     */
    public function resolve(string $plain): ?Company
    {
        $token = CompanyToken::query()
            ->where('token', hash('sha256', $plain))
            ->whereNull('revoked_at')
            ->with('company')
            ->first();

        if (! $token || ! $token->company || ! $token->company->is_active) {
            return null;
        }

        $token->forceFill(['last_used_at' => now()])->saveQuietly();

        return $token->company;
    }
}
