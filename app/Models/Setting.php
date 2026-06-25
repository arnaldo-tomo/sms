<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Devolve o valor já convertido para o tipo correto.
     */
    public function castedValue(): mixed
    {
        $value = $this->is_encrypted && $this->value
            ? decrypt($this->value)
            : $this->value;

        return match ($this->type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => $value === null ? null : (int) $value,
            'json' => $value === null ? null : json_decode($value, true),
            default => $value,
        };
    }
}
