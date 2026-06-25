<?php

namespace App\Http\Resources\Api;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma pública (estilo Twilio) de uma mensagem na API v1.
 *
 * @mixin Message
 */
class ApiMessageResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'status' => $this->status,
            'direction' => $this->direction,
            'from' => $this->from_number,
            'to' => $this->to_number,
            'content' => $this->content,
            'segments' => $this->segments,
            'error' => $this->error,
            'created_at' => $this->created_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
        ];
    }
}
