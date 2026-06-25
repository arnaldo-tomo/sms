<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $contactId = $this->route('contact')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => [
                'required', 'string', 'max:32',
                Rule::unique('contacts', 'phone_number')->ignore($contactId),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'list_ids' => ['nullable', 'array'],
            'list_ids.*' => ['integer', 'exists:contact_lists,id'],
        ];
    }
}
