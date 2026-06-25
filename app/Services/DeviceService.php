<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Device;
use App\Services\HttpSms\HttpSmsClient;
use Illuminate\Support\Carbon;

class DeviceService
{
    public function __construct(private readonly HttpSmsClient $client)
    {
    }

    /**
     * Sincroniza os dispositivos a partir da API httpSMS.
     * Se for passada uma empresa, usa as credenciais dela e associa os
     * dispositivos a essa empresa.
     *
     * @return int  número de dispositivos sincronizados
     */
    public function sync(?Company $company = null): int
    {
        $client = $company && filled($company->httpsms_api_key)
            ? $this->client->withCredentials($company->httpsms_api_key, $company->httpsms_base_url)
            : $this->client;

        $phones = $client->listPhones();
        $count = 0;

        foreach ($phones as $phone) {
            $phoneNumber = $phone['phone_number'] ?? null;
            if (! $phoneNumber) {
                continue;
            }

            // O estado online/offline vem dos heartbeats, não do objeto /phones.
            $heartbeat = $client->latestHeartbeat($phoneNumber);

            $heartbeatAt = $this->parseTimestamp(
                $heartbeat['timestamp']
                ?? $heartbeat['created_at']
                ?? $phone['last_heartbeat_timestamp']
                ?? null
            );

            $online = $heartbeatAt !== null && $heartbeatAt->gt(now()->subMinutes(15));

            // Bateria/carga/sinal podem vir no heartbeat (não vêm no /phones).
            $stats = $heartbeat ?? $phone;

            Device::updateOrCreate(
                ['phone_number' => $phoneNumber],
                [
                    'company_id' => $company?->id,
                    'httpsms_id' => $phone['id'] ?? null,
                    'name' => $phone['name'] ?? $phoneNumber,
                    'model' => $phone['model'] ?? null,
                    'status' => $online ? 'online' : 'offline',
                    'last_heartbeat_at' => $heartbeatAt,
                    'battery_level' => $this->normalizeBattery($stats),
                    'charging' => (bool) ($stats['charging'] ?? false),
                    'signal_strength' => $this->normalizeSignal($stats),
                    'meta' => $phone,
                ]
            );

            $count++;
        }

        return $count;
    }

    /**
     * Atualiza o heartbeat/estado de um único dispositivo a partir do webhook.
     *
     * @param  array<string, mixed>  $payload
     */
    public function updateFromHeartbeat(string $owner, array $payload): void
    {
        $device = Device::where('phone_number', $owner)->first();
        if (! $device) {
            return;
        }

        $device->update([
            'status' => 'online',
            'last_heartbeat_at' => now(),
            'battery_level' => $this->normalizeBattery($payload) ?? $device->battery_level,
            'charging' => $payload['charging'] ?? $device->charging,
            'signal_strength' => $this->normalizeSignal($payload) ?? $device->signal_strength,
        ]);
    }

    private function parseTimestamp(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * httpSMS reporta a bateria em milli-percentagem (0-100000) ou já 0-100.
     *
     * @param  array<string, mixed>  $data
     */
    private function normalizeBattery(array $data): ?int
    {
        $level = $data['battery_level'] ?? null;
        if ($level === null) {
            return null;
        }

        $level = (float) $level;
        if ($level > 100) {
            $level = $level / 1000;
        }

        return (int) max(0, min(100, round($level)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function normalizeSignal(array $data): ?int
    {
        $signal = $data['signal_strength'] ?? null;

        return $signal === null ? null : (int) max(0, min(100, round((float) $signal)));
    }
}
