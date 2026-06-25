<?php

use App\Jobs\RefreshMessageStatusJob;
use App\Jobs\SyncDevicesJob;
use App\Services\SettingsService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Despacha os SMS agendados cuja hora já chegou (a cada minuto).
Schedule::command('sms:dispatch-scheduled')->everyMinute()->withoutOverlapping();

// Sincroniza dispositivos httpSMS a cada 5 minutos.
Schedule::job(new SyncDevicesJob)->everyFiveMinutes()->withoutOverlapping();

// Polling do estado de entrega (atualiza sending/sent → delivered/failed).
// Útil quando os webhooks não estão configurados (ex.: sem URL pública).
Schedule::job(new RefreshMessageStatusJob)
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => (bool) app(SettingsService::class)->get('status_poll_enabled', true));
