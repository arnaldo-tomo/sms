<?php

namespace App\Http\Resources\Api;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Device
 */
class ApiNumberResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'phone_number' => $this->phone_number,
            'name' => $this->name,
            'status' => $this->is_online ? 'online' : 'offline',
            'last_seen_at' => $this->last_heartbeat_at?->toIso8601String(),
        ];
    }
}
