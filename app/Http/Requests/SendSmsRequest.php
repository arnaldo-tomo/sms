<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sms.send');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:1600'],
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
            'from' => ['nullable', 'string', 'max:32'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],

            // destinos: pelo menos um dos três
            'recipients' => ['nullable', 'array'],
            'recipients.*' => ['string', 'max:32'],
            'contact_ids' => ['nullable', 'array'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
            'list_ids' => ['nullable', 'array'],
            'list_ids.*' => ['integer', 'exists:contact_lists,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasRecipients = filled($this->input('recipients'))
                || filled($this->input('contact_ids'))
                || filled($this->input('list_ids'));

            if (! $hasRecipients) {
                $validator->errors()->add('recipients', 'Indica pelo menos um destinatário, contacto ou grupo.');
            }
        });
    }
}
