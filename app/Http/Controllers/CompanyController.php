<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpSmsException;
use App\Models\Company;
use App\Models\CompanyToken;
use App\Services\CompanyTokenService;
use App\Services\DeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::query()
            ->withCount(['devices', 'tokens', 'messages'])
            ->with(['tokens' => fn ($q) => $q->whereNull('revoked_at')->latest()])
            ->latest()
            ->get()
            ->map(fn (Company $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'contact_email' => $c->contact_email,
                'httpsms_base_url' => $c->httpsms_base_url,
                'httpsms_api_key_set' => filled($c->httpsms_api_key),
                'status_callback_url' => $c->status_callback_url,
                'messages_per_minute' => $c->messages_per_minute,
                'is_active' => $c->is_active,
                'devices_count' => $c->devices_count,
                'messages_count' => $c->messages_count,
                'tokens' => $c->tokens->map(fn (CompanyToken $t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'prefix' => $t->prefix,
                    'last_used_at' => $t->last_used_at?->diffForHumans(),
                ]),
            ]);

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request, CompanyTokenService $tokens): RedirectResponse
    {
        $this->authorize('create', Company::class);

        $data = $this->validateData($request);
        $company = Company::create($data);

        // Cria logo um primeiro token e mostra-o uma vez.
        $generated = $tokens->generate($company, 'default');

        return back()->with('success', 'Empresa criada.')
            ->with('new_token', $generated['plain']);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $data = $this->validateData($request, $company);

        // Não sobrescrever a API key se vier vazia.
        if (blank($data['httpsms_api_key'] ?? null)) {
            unset($data['httpsms_api_key']);
        }
        if (blank($data['callback_secret'] ?? null)) {
            unset($data['callback_secret']);
        }

        $company->update($data);

        return back()->with('success', 'Empresa atualizada.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        return back()->with('success', 'Empresa eliminada.');
    }

    public function generateToken(Request $request, Company $company, CompanyTokenService $tokens): RedirectResponse
    {
        $this->authorize('update', $company);

        $request->validate(['name' => ['nullable', 'string', 'max:50']]);
        $generated = $tokens->generate($company, $request->input('name', 'default'));

        return back()->with('success', 'Novo token gerado.')
            ->with('new_token', $generated['plain']);
    }

    public function revokeToken(Company $company, CompanyToken $token): RedirectResponse
    {
        $this->authorize('update', $company);

        abort_unless($token->company_id === $company->id, 404);
        $token->update(['revoked_at' => now()]);

        return back()->with('success', 'Token revogado.');
    }

    public function sync(Company $company, DeviceService $devices): RedirectResponse
    {
        $this->authorize('update', $company);

        try {
            $count = $devices->sync($company);

            return back()->with('success', "{$count} dispositivo(s) sincronizado(s) para {$company->name}.");
        } catch (HttpSmsException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, ?Company $company = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'httpsms_api_key' => ['nullable', 'string', 'max:255'],
            'httpsms_base_url' => ['required', 'url', 'max:255'],
            'status_callback_url' => ['nullable', 'url', 'max:255'],
            'callback_secret' => ['nullable', 'string', 'max:255'],
            'messages_per_minute' => ['required', 'integer', 'min:1', 'max:6000'],
            'is_active' => ['boolean'],
        ]);
    }
}
