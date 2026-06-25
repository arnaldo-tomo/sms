<?php

namespace App\Observers;

use App\Jobs\StatusCallbackJob;
use App\Models\Message;

class MessageObserver
{
    /**
     * Estados que justificam notificar a empresa.
     *
     * @var array<int, string>
     */
    private array $notify = [
        Message::STATUS_SENT,
        Message::STATUS_DELIVERED,
        Message::STATUS_FAILED,
        Message::STATUS_EXPIRED,
    ];

    public function updated(Message $message): void
    {
        if (! $message->wasChanged('status')) {
            return;
        }

        if (! in_array($message->status, $this->notify, true)) {
            return;
        }

        if ($message->company_id === null) {
            return;
        }

        StatusCallbackJob::dispatch($message->id);
    }
}
