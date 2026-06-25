<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'httpsms_id',
        'name',
        'phone_number',
        'model',
        'status',
        'last_heartbeat_at',
        'battery_level',
        'charging',
        'signal_strength',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
        'battery_level' => 'integer',
        'signal_strength' => 'integer',
        'charging' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    protected $appends = ['is_online'];

    /**
     * Um dispositivo é considerado online se enviou heartbeat nos últimos 15 min.
     */
    public function getIsOnlineAttribute(): bool
    {
        if ($this->status === 'online') {
            return true;
        }

        return $this->last_heartbeat_at !== null
            && $this->last_heartbeat_at->gt(now()->subMinutes(15));
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
