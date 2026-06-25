<?php

namespace App\Services\HttpSms;

use App\Models\Contact;
use App\Models\Device;
use App\Models\Message;
use App\Services\DeviceService;
use Illuminate\Support\Facades\Log;

/**
 * Processa os eventos (CloudEvents) enviados pelo httpSMS via webhook.
 * Ver: https://docs.httpsms.com/webhooks
 */
class WebhookHandler
{
    public function __construct(private readonly DeviceService $devices)
    {
    }

    /**
     * @param  array<string, mixed>  $event
     */
    public function handle(array $event): void
    {
        $type = $event['type'] ?? '';
        $data = $event['data'] ?? [];

        match ($type) {
            'message.phone.sent' => $this->updateStatus($data, Message::STATUS_SENT, 'sent_at'),
            'message.phone.delivered' => $this->updateStatus($data, Message::STATUS_DELIVERED, 'delivered_at'),
            'message.phone.failed',
            'message.send.failed' => $this->markFailed($data),
            'message.phone.received' => $this->storeInbound($data),
            'phone.heartbeat.online',
            'phone.heartbeat.offline' => $this->updateHeartbeat($type, $data),
            default => Log::info('httpSMS webhook ignorado', ['type' => $type]),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findMessage(array $data): ?Message
    {
        $id = $data['message_id'] ?? ($data['id'] ?? null);

        return $id ? Message::where('httpsms_id', $id)->first() : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateStatus(array $data, string $status, string $timestampField): void
    {
        $message = $this->findMessage($data);
        if (! $message || $message->isFinal()) {
            return;
        }

        $message->update([
            'status' => $status,
            $timestampField => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function markFailed(array $data): void
    {
        $message = $this->findMessage($data);
        if (! $message) {
            return;
        }

        $message->update([
            'status' => Message::STATUS_FAILED,
            'failed_at' => now(),
            'error' => $data['error_message'] ?? ($data['reason'] ?? 'Entrega falhou no dispositivo.'),
        ]);
    }

    /**
     * Guarda um SMS recebido (inbound).
     *
     * @param  array<string, mixed>  $data
     */
    private function storeInbound(array $data): void
    {
        $from = $data['contact'] ?? ($data['from'] ?? null);
        $to = $data['owner'] ?? ($data['to'] ?? null);

        if (! $from || ! $to) {
            return;
        }

        $device = Device::where('phone_number', $to)->first();
        $contact = Contact::where('phone_number', $from)->first();

        Message::updateOrCreate(
            ['httpsms_id' => $data['id'] ?? ($data['message_id'] ?? uniqid('in_', true))],
            [
                'direction' => Message::DIRECTION_INBOUND,
                'device_id' => $device?->id,
                'contact_id' => $contact?->id,
                'from_number' => $from,
                'to_number' => $to,
                'content' => $data['content'] ?? '',
                'status' => Message::STATUS_RECEIVED,
                'sent_at' => now(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateHeartbeat(string $type, array $data): void
    {
        $owner = $data['owner'] ?? null;
        if (! $owner) {
            return;
        }

        if ($type === 'phone.heartbeat.offline') {
            Device::where('phone_number', $owner)->update(['status' => 'offline']);

            return;
        }

        $this->devices->updateFromHeartbeat($owner, $data);
    }
}
