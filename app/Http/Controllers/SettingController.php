<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpSmsException;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\HttpSms\HttpSmsClient;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function edit(Request $request): Response
    {
        $this->authorize('settings.manage');

        return Inertia::render('Settings/Index', [
            'settings' => $this->settings->forUi(),
            'queueConnections' => array_keys(config('queue.connections')),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Não sobrescrever segredos com valores vazios (mascarados na UI).
        foreach (['httpsms_api_key', 'httpsms_webhook_secret'] as $secret) {
            if (blank($data[$secret] ?? null)) {
                unset($data[$secret]);
            }
        }

        $this->settings->setMany($data);

        return back()->with('success', 'Configurações guardadas.');
    }

    public function testConnection(HttpSmsClient $client): RedirectResponse
    {
        $this->authorize('settings.manage');

        try {
            $phones = $client->listPhones();

            return back()->with('success', 'Ligação OK. '.count($phones).' dispositivo(s) encontrado(s) na conta httpSMS.');
        } catch (HttpSmsException $e) {
            return back()->with('error', 'Falha na ligação: '.$e->getMessage());
        }
    }
}
