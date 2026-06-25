<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Polling de reserva: atualiza o estado das mensagens que ainda não chegaram
 * a um estado final (útil quando os webhooks não estão configurados).
 */
class RefreshMessageStatusJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(SmsService $sms): void
    {
        Message::query()
            ->with('company')
            ->whereNotNull('httpsms_id')
            ->whereIn('status', [
                Message::STATUS_SENDING,
                Message::STATUS_SENT,
            ])
            ->where('updated_at', '>=', now()->subDay())
            ->limit(200)
            ->get()
            ->each(function (Message $message) use ($sms): void {
                try {
                    $sms->pollStatus($message);
                } catch (\Throwable) {
                    // ignora; tenta de novo no próximo ciclo
                }
            });
    }
}
