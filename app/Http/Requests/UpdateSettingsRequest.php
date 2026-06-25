<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'httpsms_base_url' => ['required', 'url', 'max:255'],
            'httpsms_api_key' => ['nullable', 'string', 'max:255'],
            'httpsms_default_from' => ['nullable', 'string', 'max:32'],
            'httpsms_webhook_secret' => ['nullable', 'string', 'max:255'],
            'queue_connection' => ['nullable', 'string', 'max:50'],
            'queue_name' => ['nullable', 'string', 'max:50'],
            'status_poll_enabled' => ['boolean'],
        ];
    }
}
