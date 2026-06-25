<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_KEY = 'app.settings';

    /**
     * Definições conhecidas e os seus valores por omissão (fallback para config/env).
     *
     * @var array<string, array{type: string, group: string, encrypted: bool, default: callable|null}>
     */
    private array $schema = [
        'httpsms_base_url' => ['type' => 'string', 'group' => 'httpsms', 'encrypted' => false, 'default' => null],
        'httpsms_api_key' => ['type' => 'string', 'group' => 'httpsms', 'encrypted' => true, 'default' => null],
        'httpsms_default_from' => ['type' => 'string', 'group' => 'httpsms', 'encrypted' => false, 'default' => null],
        'httpsms_webhook_secret' => ['type' => 'string', 'group' => 'httpsms', 'encrypted' => true, 'default' => null],
        'queue_connection' => ['type' => 'string', 'group' => 'queue', 'encrypted' => false, 'default' => null],
        'queue_name' => ['type' => 'string', 'group' => 'queue', 'encrypted' => false, 'default' => null],
        'status_poll_enabled' => ['type' => 'bool', 'group' => 'queue', 'encrypted' => false, 'default' => null],
    ];

    public function __construct()
    {
        $this->schema['httpsms_base_url']['default'] = fn () => config('services.httpsms.base_url');
        $this->schema['httpsms_api_key']['default'] = fn () => config('services.httpsms.api_key');
        $this->schema['httpsms_default_from']['default'] = fn () => config('services.httpsms.default_from');
        $this->schema['httpsms_webhook_secret']['default'] = fn () => config('services.httpsms.webhook_secret');
        $this->schema['queue_connection']['default'] = fn () => config('queue.default');
        $this->schema['queue_name']['default'] = fn () => 'default';
        $this->schema['status_poll_enabled']['default'] = fn () => true;
    }

    /**
     * @return array<string, mixed>
     */
    private function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return Setting::all()->mapWithKeys(fn (Setting $s) => [$s->key => $s->castedValue()])->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $stored = $this->all();

        if (array_key_exists($key, $stored) && $stored[$key] !== null && $stored[$key] !== '') {
            return $stored[$key];
        }

        if (isset($this->schema[$key]) && $this->schema[$key]['default']) {
            $resolved = ($this->schema[$key]['default'])();

            return $resolved !== null ? $resolved : $default;
        }

        return $default;
    }

    public function set(string $key, mixed $value): void
    {
        $meta = $this->schema[$key] ?? ['type' => 'string', 'group' => 'general', 'encrypted' => false];

        $stored = match ($meta['type']) {
            'bool' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => $value === null ? null : (string) $value,
        };

        if ($meta['encrypted'] && $stored !== null && $stored !== '') {
            $stored = encrypt($stored);
        }

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'type' => $meta['type'],
                'group' => $meta['group'],
                'is_encrypted' => $meta['encrypted'],
            ]
        );

        $this->flush();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Definições seguras para enviar ao frontend (segredos mascarados).
     *
     * @return array<string, mixed>
     */
    public function forUi(): array
    {
        return [
            'httpsms_base_url' => $this->get('httpsms_base_url'),
            'httpsms_default_from' => $this->get('httpsms_default_from'),
            'httpsms_api_key_set' => filled($this->get('httpsms_api_key')),
            'httpsms_webhook_secret_set' => filled($this->get('httpsms_webhook_secret')),
            'queue_connection' => $this->get('queue_connection'),
            'queue_name' => $this->get('queue_name'),
            'status_poll_enabled' => (bool) $this->get('status_poll_enabled'),
            'webhook_url' => url('/api/webhooks/httpsms'),
        ];
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
