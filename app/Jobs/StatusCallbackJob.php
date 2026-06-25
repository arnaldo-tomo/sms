<?php

namespace App\Jobs;

use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

/**
 * Notifica o webhook da empresa (status callback) quando uma mensagem muda
 * de estado — tal como o Twilio faz com o StatusCallback.
 */
class StatusCallbackJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly int $messageId)
    {
    }

    public function handle(): void
    {
        $message = Message::with('company')->find($this->messageId);

        if (! $message || ! $message->company || blank($message->company->status_callback_url)) {
            return;
        }

        $payload = [
            'id' => $message->uuid,
            'status' => $message->status,
            'to' => $message->to_number,
            'from' => $message->from_number,
            'error' => $message->error,
            'sent_at' => $message->sent_at?->toIso8601String(),
            'delivered_at' => $message->delivered_at?->toIso8601String(),
            'timestamp' => now()->toIso8601String(),
        ];

        $request = Http::timeout(15)->acceptJson();

        // Assinatura HMAC opcional para a empresa validar a origem.
        if (filled($secret = $message->company->callback_secret)) {
            $request = $request->withHeaders([
                'X-Signature' => hash_hmac('sha256', json_encode($payload), $secret),
            ]);
        }

        $request->post($message->company->status_callback_url, $payload)->throw();
    }
}
