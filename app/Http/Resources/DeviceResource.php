<?php

namespace App\Http\Resources;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Device
 */
class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'model' => $this->model,
            'status' => $this->status,
            'is_online' => $this->is_online,
            'battery_level' => $this->battery_level,
            'charging' => $this->charging,
            'signal_strength' => $this->signal_strength,
            'is_active' => $this->is_active,
            'last_heartbeat_at' => $this->last_heartbeat_at?->toIso8601String(),
            'last_heartbeat_human' => $this->last_heartbeat_at?->diffForHumans(),
        ];
    }
}
