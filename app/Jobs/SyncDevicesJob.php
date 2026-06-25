<?php

namespace App\Jobs;

use App\Services\DeviceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncDevicesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $backoff = 60;

    public function handle(DeviceService $devices): void
    {
        $devices->sync();
    }
}
