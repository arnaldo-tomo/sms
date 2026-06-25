<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpSmsException;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DeviceController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Device::class);

        return Inertia::render('Devices/Index', [
            'devices' => DeviceResource::collection(
                Device::orderByDesc('last_heartbeat_at')->get()
            ),
        ]);
    }

    public function sync(DeviceService $devices): RedirectResponse
    {
        $this->authorize('manage', Device::class);

        try {
            $count = $devices->sync();

            return back()->with('success', "{$count} dispositivo(s) sincronizado(s).");
        } catch (HttpSmsException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
