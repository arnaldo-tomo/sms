<?php

namespace App\Models;

use App\Observers\MessageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(MessageObserver::class)]
class Message extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['to_number', 'status', 'content'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('message');
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_RECEIVED = 'received';

    public const DIRECTION_OUTBOUND = 'outbound';
    public const DIRECTION_INBOUND = 'inbound';

    protected $fillable = [
        'uuid',
        'user_id',
        'company_id',
        'device_id',
        'contact_id',
        'httpsms_id',
        'direction',
        'source',
        'from_number',
        'to_number',
        'content',
        'segments',
        'status',
        'error',
        'scheduled_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'meta',
    ];

    protected $casts = [
        'segments' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Message $message): void {
            $message->uuid ??= (string) Str::uuid();
            $message->segments = max(1, (int) ceil(mb_strlen((string) $message->content) / 153));
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @param  Builder<Message>  $query
     */
    public function scopeOutbound(Builder $query): void
    {
        $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    /**
     * @param  Builder<Message>  $query
     */
    public function scopeStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_FAILED,
            self::STATUS_EXPIRED,
        ], true);
    }
}
