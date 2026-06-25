<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'direction' => $this->direction,
            'from_number' => $this->from_number,
            'to_number' => $this->to_number,
            'content' => $this->content,
            'segments' => $this->segments,
            'status' => $this->status,
            'error' => $this->error,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'device' => $this->whenLoaded('device', fn () => [
                'id' => $this->device?->id,
                'name' => $this->device?->name,
                'phone_number' => $this->device?->phone_number,
            ]),
            'contact' => $this->whenLoaded('contact', fn () => [
                'id' => $this->contact?->id,
                'name' => $this->contact?->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
        ];
    }
}
