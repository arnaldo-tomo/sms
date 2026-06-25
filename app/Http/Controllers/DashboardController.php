<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Device;
use App\Repositories\MessageRepository;
use App\Services\HttpSms\HttpSmsClient;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly MessageRepository $messages)
    {
    }

    public function __invoke(HttpSmsClient $client): Response
    {
        $devices = Device::query()->orderByDesc('last_heartbeat_at')->get();
        $onlineDevices = $devices->filter->is_online->count();

        return Inertia::render('Dashboard', [
            'stats' => $this->messages->statusCounts(),
            'contactsCount' => Contact::count(),
            'devices' => [
                'total' => $devices->count(),
                'online' => $onlineDevices,
                'offline' => $devices->count() - $onlineDevices,
                'list' => $devices->take(5)->map(fn (Device $d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'phone_number' => $d->phone_number,
                    'is_online' => $d->is_online,
                    'battery_level' => $d->battery_level,
                    'signal_strength' => $d->signal_strength,
                    'last_heartbeat_human' => $d->last_heartbeat_at?->diffForHumans(),
                ]),
            ],
            'dailyUsage' => $this->messages->dailyUsage(14),
            'httpsmsConfigured' => $client->isConfigured(),
        ]);
    }
}
