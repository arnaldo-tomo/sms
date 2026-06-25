<?php

namespace App\Services\HttpSms;

use App\Exceptions\HttpSmsException;
use App\Services\SettingsService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Cliente fino para a API REST do httpSMS (https://docs.httpsms.com).
 */
class HttpSmsClient
{
    private ?string $apiKeyOverride = null;
    private ?string $baseUrlOverride = null;

    public function __construct(private readonly SettingsService $settings)
    {
    }

    /**
     * Devolve uma instância que usa credenciais específicas (ex.: de uma empresa),
     * em vez das definições globais.
     */
    public function withCredentials(?string $apiKey, ?string $baseUrl = null): static
    {
        $clone = clone $this;
        $clone->apiKeyOverride = $apiKey;
        $clone->baseUrlOverride = $baseUrl;

        return $clone;
    }

    private function apiKey(): ?string
    {
        return $this->apiKeyOverride ?? $this->settings->get('httpsms_api_key');
    }

    private function baseUrl(): string
    {
        return (string) ($this->baseUrlOverride ?: $this->settings->get('httpsms_base_url'));
    }

    private function http(): PendingRequest
    {
        $apiKey = $this->apiKey();

        if (blank($apiKey)) {
            throw new HttpSmsException('A API Key do httpSMS não está configurada. Define-a em Configurações.');
        }

        return Http::baseUrl(rtrim($this->baseUrl(), '/'))
            ->withHeaders([
                'x-api-key' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(20)
            ->retry(2, 250, throw: false);
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey()) && filled($this->baseUrl());
    }

    /**
     * Envia um SMS. Devolve os dados da mensagem criada no httpSMS.
     *
     * @return array<string, mixed>
     */
    public function sendMessage(string $from, string $to, string $content, ?string $requestId = null): array
    {
        $payload = array_filter([
            'from' => $from,
            'to' => $to,
            'content' => $content,
            'request_id' => $requestId,
        ], fn ($v) => $v !== null && $v !== '');

        $response = $this->http()->post('/messages/send', $payload);

        if ($response->failed()) {
            throw new HttpSmsException(
                'Falha ao enviar SMS via httpSMS: '.$response->body(),
                $response->status(),
                $response->json(),
            );
        }

        return $response->json('data', []);
    }

    /**
     * Obtém uma mensagem pelo seu ID no httpSMS.
     *
     * @return array<string, mixed>
     */
    public function getMessage(string $messageId): array
    {
        $response = $this->http()->get("/messages/{$messageId}");

        if ($response->failed()) {
            throw new HttpSmsException(
                "Falha ao obter a mensagem {$messageId}: ".$response->body(),
                $response->status(),
                $response->json(),
            );
        }

        return $response->json('data', []);
    }

    /**
     * Lista os telefones (dispositivos) registados na conta httpSMS.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listPhones(): array
    {
        $response = $this->http()->get('/phones', ['limit' => 100]);

        if ($response->failed()) {
            throw new HttpSmsException(
                'Falha ao listar dispositivos: '.$response->body(),
                $response->status(),
                $response->json(),
            );
        }

        return $response->json('data', []);
    }

    /**
     * Último heartbeat de um número (estado do dispositivo).
     *
     * @return array<string, mixed>|null
     */
    public function latestHeartbeat(string $owner): ?array
    {
        $response = $this->http()->get('/heartbeats', [
            'owner' => $owner,
            'limit' => 1,
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json('data', []);

        return $data[0] ?? null;
    }
}
