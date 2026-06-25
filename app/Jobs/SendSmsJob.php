<?php

namespace App\Jobs;

use App\Exceptions\HttpSmsException;
use App\Models\Message;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $backoff = 30;

    public function __construct(public readonly int $messageId)
    {
    }

    public function handle(SmsService $sms): void
    {
        $message = Message::find($this->messageId);

        if (! $message || $message->status === Message::STATUS_DELIVERED) {
            return;
        }

        try {
            $sms->send($message);
        } catch (HttpSmsException $e) {
            // Erros de configuração não devem ser repetidos indefinidamente.
            if ($e->statusCode === 401 || $e->statusCode === 403) {
                $sms->markFailed($message, $e->getMessage());

                return;
            }

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $message = Message::find($this->messageId);

        if ($message) {
            app(SmsService::class)->markFailed(
                $message,
                $exception?->getMessage() ?? 'Falha desconhecida no envio.'
            );
        }
    }
}
