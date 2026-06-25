<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HttpSms\WebhookHandler;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function httpsms(Request $request, WebhookHandler $handler): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            return response()->json(['message' => 'Assinatura inválida.'], 401);
        }

        try {
            $handler->handle($request->all());
        } catch (\Throwable $e) {
            Log::error('Erro ao processar webhook httpSMS', [
                'message' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['message' => 'Erro interno.'], 500);
        }

        return response()->json(['message' => 'ok']);
    }

    private function verifySignature(Request $request): bool
    {
        $secret = $this->settings->get('httpsms_webhook_secret');

        // Sem segredo configurado => aceita (útil em desenvolvimento).
        if (blank($secret)) {
            return true;
        }

        $provided = $request->header('X-Webhook-Secret')
            ?? $request->header('x-webhook-signature')
            ?? $request->query('secret');

        return is_string($provided) && hash_equals($secret, $provided);
    }
}
