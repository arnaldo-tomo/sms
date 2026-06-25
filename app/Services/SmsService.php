<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\Company;
use App\Models\Device;
use App\Models\Message;
use App\Models\User;
use App\Services\HttpSms\HttpSmsClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SmsService
{
    public function __construct(
        private readonly HttpSmsClient $client,
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * Cria as mensagens (uma por destinatário) e coloca-as na fila de envio.
     *
     * @param  Collection<int, array{to: string, contact_id?: int|null}>  $recipients
     * @return Collection<int, Message>
     */
    public function dispatchBulk(
        Collection $recipients,
        string $content,
        ?int $deviceId = null,
        ?string $from = null,
        ?Carbon $scheduledAt = null,
        ?User $user = null,
        ?Company $company = null,
        string $source = 'web',
    ): Collection {
        $device = $deviceId ? Device::find($deviceId) : null;
        $fromNumber = $from
            ?: $device?->phone_number
            ?: $this->settings->get('httpsms_default_from');

        $isScheduled = $scheduledAt && $scheduledAt->isFuture();

        $messages = DB::transaction(function () use ($recipients, $content, $device, $fromNumber, $scheduledAt, $isScheduled, $user, $company, $source) {
            return $recipients->map(function (array $recipient) use ($content, $device, $fromNumber, $scheduledAt, $isScheduled, $user, $company, $source) {
                return Message::create([
                    'user_id' => $user?->id,
                    'company_id' => $company?->id,
                    'source' => $source,
                    'device_id' => $device?->id,
                    'contact_id' => $recipient['contact_id'] ?? null,
                    'direction' => Message::DIRECTION_OUTBOUND,
                    'from_number' => $fromNumber,
                    'to_number' => $this->normalizeNumber($recipient['to']),
                    'content' => $content,
                    'status' => $isScheduled ? Message::STATUS_SCHEDULED : Message::STATUS_QUEUED,
                    'scheduled_at' => $isScheduled ? $scheduledAt : null,
                ]);
            });
        });

        // Mensagens agendadas ficam em estado "scheduled" e são despachadas
        // pelo comando sms:dispatch-scheduled quando a hora chega (mais fiável
        // do que jobs com delay, que se perdem se a fila for reiniciada/limpa).
        if (! $isScheduled) {
            foreach ($messages as $message) {
                $this->queueForSending($message);
            }
        }

        return $messages;
    }

    /**
     * Marca a mensagem como "queued" e coloca o job de envio na fila.
     * Usado tanto para envio imediato como para mensagens agendadas que venceram.
     */
    public function queueForSending(Message $message): void
    {
        $queue = $this->settings->get('queue_name', 'default');

        if ($message->status !== Message::STATUS_QUEUED) {
            $message->update(['status' => Message::STATUS_QUEUED]);
        }

        dispatch((new SendSmsJob($message->id))->onQueue($queue));
    }

    /**
     * Executa o envio real de uma mensagem através da API httpSMS.
     * Chamado de dentro do SendSmsJob.
     */
    public function send(Message $message): void
    {
        if (blank($message->from_number)) {
            $message->update([
                'status' => Message::STATUS_FAILED,
                'error' => 'Número de origem (dispositivo) não definido.',
                'failed_at' => now(),
            ]);

            return;
        }

        $message->update(['status' => Message::STATUS_SENDING]);

        $data = $this->clientFor($message)->sendMessage(
            from: $message->from_number,
            to: $message->to_number,
            content: $message->content,
            requestId: $message->uuid,
        );

        $message->update([
            'httpsms_id' => $data['id'] ?? null,
            'status' => $this->mapStatus($data['status'] ?? 'pending'),
            'sent_at' => now(),
            'error' => null,
            'meta' => array_merge($message->meta ?? [], ['send_response' => $data]),
        ]);
    }

    /**
     * Modelo centralizado: todos os SMS (web ou API de empresas) saem pela
     * conta httpSMS da PLATAFORMA. As empresas apenas escolhem o número.
     */
    private function clientFor(Message $message): HttpSmsClient
    {
        return $this->client;
    }

    /**
     * Consulta o estado de uma mensagem na API e atualiza-a localmente.
     * Usa as credenciais corretas (empresa ou plataforma).
     */
    public function pollStatus(Message $message): void
    {
        if (blank($message->httpsms_id)) {
            return;
        }

        $data = $this->clientFor($message)->getMessage($message->httpsms_id);
        $status = $this->mapStatus($data['status'] ?? 'sent');

        $attributes = ['status' => $status];
        if ($status === Message::STATUS_DELIVERED) {
            $attributes['delivered_at'] = now();
        } elseif ($status === Message::STATUS_FAILED) {
            $attributes['failed_at'] = now();
            $attributes['error'] = $data['error'] ?? 'Entrega falhou.';
        }

        $message->update($attributes);
    }

    public function markFailed(Message $message, string $reason): void
    {
        $message->update([
            'status' => Message::STATUS_FAILED,
            'error' => $reason,
            'failed_at' => now(),
        ]);
    }

    /**
     * Limpa o número: remove espaços, traços e parênteses, mantendo o "+".
     * O httpSMS exige formato E.164 (ex.: +258840000000).
     */
    public function normalizeNumber(string $number): string
    {
        $number = trim($number);
        $plus = str_starts_with($number, '+');
        $digits = preg_replace('/\D/', '', $number);

        return $plus ? '+'.$digits : $digits;
    }

    /**
     * Mapeia o estado devolvido pelo httpSMS para o estado interno.
     */
    public function mapStatus(string $apiStatus): string
    {
        return match (strtolower($apiStatus)) {
            'pending', 'scheduled' => Message::STATUS_SENDING,
            'sending' => Message::STATUS_SENDING,
            'sent' => Message::STATUS_SENT,
            'delivered' => Message::STATUS_DELIVERED,
            'failed' => Message::STATUS_FAILED,
            'expired' => Message::STATUS_EXPIRED,
            default => Message::STATUS_SENT,
        };
    }
}
