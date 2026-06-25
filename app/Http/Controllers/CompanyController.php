<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyToken;
use App\Models\Device;
use App\Services\CompanyTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::query()
            ->withCount(['tokens', 'messages'])
            ->with([
                'tokens' => fn ($q) => $q->whereNull('revoked_at')->latest(),
                'devices' => fn ($q) => $q->select('id', 'company_id', 'phone_number', 'name', 'status'),
            ])
            ->latest()
            ->get()
            ->map(fn (Company $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'contact_email' => $c->contact_email,
                'status_callback_url' => $c->status_callback_url,
                'messages_per_minute' => $c->messages_per_minute,
                'price_per_segment' => (float) $c->price_per_segment,
                'currency' => $c->currency,
                'is_active' => $c->is_active,
                'messages_count' => $c->messages_count,
                'numbers' => $c->devices->map(fn (Device $d) => [
                    'id' => $d->id,
                    'phone_number' => $d->phone_number,
                    'status' => $d->status,
                ]),
                'tokens' => $c->tokens->map(fn (CompanyToken $t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'prefix' => $t->prefix,
                    'last_used_at' => $t->last_used_at?->diffForHumans(),
                ]),
            ]);

        // Números no pool partilhado (sem empresa) — disponíveis para atribuir.
        $availableNumbers = Device::whereNull('company_id')
            ->where('is_active', true)
            ->get(['id', 'phone_number', 'status'])
            ->map(fn (Device $d) => [
                'id' => $d->id,
                'phone_number' => $d->phone_number,
                'status' => $d->status,
            ]);

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
            'availableNumbers' => $availableNumbers,
        ]);
    }

    public function usage(Company $company): Response
    {
        $this->authorize('viewAny', Company::class);

        $base = $company->messages()->outbound();

        // Contagens por estado (todo o histórico).
        $byStatus = (clone $base)
            ->selectRaw('status, count(*) as total, coalesce(sum(segments),0) as segs')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $count = fn (array $statuses) => collect($statuses)->sum(fn ($s) => (int) ($byStatus[$s]->total ?? 0));
        $segs = fn (array $statuses) => collect($statuses)->sum(fn ($s) => (int) ($byStatus[$s]->segs ?? 0));

        $sentStatuses = ['sent', 'delivered'];
        $billableSegments = $segs($sentStatuses);

        // Este mês.
        $monthBase = (clone $base)->where('created_at', '>=', now()->startOfMonth());
        $monthSegments = (clone $monthBase)->whereIn('status', $sentStatuses)->sum('segments');
        $monthCount = (clone $monthBase)->count();

        // Utilização diária (últimos 30 dias).
        $since = now()->subDays(29)->startOfDay();
        $rows = (clone $base)->where('created_at', '>=', $since)->get(['status', 'segments', 'created_at']);
        $daily = [];
        for ($i = 0; $i < 30; $i++) {
            $d = $since->copy()->addDays($i)->toDateString();
            $daily[$d] = ['date' => $d, 'sent' => 0, 'delivered' => 0, 'failed' => 0];
        }
        foreach ($rows as $r) {
            $d = $r->created_at->toDateString();
            if (! isset($daily[$d])) {
                continue;
            }
            if (in_array($r->status, ['failed', 'expired'], true)) {
                $daily[$d]['failed']++;
            } elseif ($r->status === 'delivered') {
                $daily[$d]['delivered']++;
                $daily[$d]['sent']++;
            } elseif ($r->status === 'sent') {
                $daily[$d]['sent']++;
            }
        }

        $price = (float) $company->price_per_segment;

        return Inertia::render('Companies/Usage', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'currency' => $company->currency,
                'price_per_segment' => $price,
                'messages_per_minute' => $company->messages_per_minute,
            ],
            'stats' => [
                'total' => $count(['pending', 'scheduled', 'queued', 'sending', 'sent', 'delivered', 'failed', 'expired']),
                'sent' => $count($sentStatuses),
                'delivered' => $count(['delivered']),
                'failed' => $count(['failed', 'expired']),
                'pending' => $count(['pending', 'scheduled', 'queued', 'sending']),
                'billable_segments' => $billableSegments,
                'total_cost' => round($billableSegments * $price, 2),
                'month_count' => $monthCount,
                'month_segments' => (int) $monthSegments,
                'month_cost' => round($monthSegments * $price, 2),
            ],
            'daily' => array_values($daily),
        ]);
    }

    public function assignNumber(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $data = $request->validate(['device_id' => ['required', 'integer', 'exists:devices,id']]);
        Device::where('id', $data['device_id'])->update(['company_id' => $company->id]);

        return back()->with('success', 'Número atribuído à empresa.');
    }

    public function unassignNumber(Company $company, Device $device): RedirectResponse
    {
        $this->authorize('update', $company);

        abort_unless($device->company_id === $company->id, 404);
        $device->update(['company_id' => null]);

        return back()->with('success', 'Número devolvido ao pool partilhado.');
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

        $data = $this->validateData($request);

        // Não sobrescrever o segredo do callback se vier vazio.
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

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'status_callback_url' => ['nullable', 'url', 'max:255'],
            'callback_secret' => ['nullable', 'string', 'max:255'],
            'messages_per_minute' => ['required', 'integer', 'min:1', 'max:6000'],
            'price_per_segment' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:8'],
            'is_active' => ['boolean'],
        ]);
    }
}
