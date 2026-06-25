<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Services\SmsService;
use Illuminate\Console\Command;

class DispatchScheduledMessages extends Command
{
    protected $signature = 'sms:dispatch-scheduled';

    protected $description = 'Despacha os SMS agendados cuja hora já chegou.';

    public function handle(SmsService $sms): int
    {
        $due = Message::query()
            ->where('status', Message::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit(500)
            ->get();

        foreach ($due as $message) {
            $sms->queueForSending($message);
        }

        if ($due->isNotEmpty()) {
            $this->info("Despachados {$due->count()} SMS agendados.");
        }

        return self::SUCCESS;
    }
}
