<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'contact_email',
        'httpsms_api_key',
        'httpsms_base_url',
        'status_callback_url',
        'callback_secret',
        'messages_per_minute',
        'price_per_segment',
        'currency',
        'is_active',
    ];

    protected $hidden = [
        'httpsms_api_key',
        'callback_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'messages_per_minute' => 'integer',
        'price_per_segment' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            $company->slug ??= Str::slug($company->name).'-'.Str::lower(Str::random(4));
        });
    }

    /**
     * Encripta a API key do httpSMS em repouso.
     */
    protected function httpsmsApiKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    protected function callbackSecret(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->httpsms_api_key) && filled($this->httpsms_base_url);
    }

    /**
     * @return HasMany<CompanyToken, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(CompanyToken::class);
    }

    /**
     * @return HasMany<Device, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
