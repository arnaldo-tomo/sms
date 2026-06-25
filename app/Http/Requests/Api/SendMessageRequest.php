<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autenticação tratada pelo middleware company.api
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // "to" pode ser uma string (um número) ou um array (envio em série)
            'to' => ['required'],
            'to.*' => ['string', 'max:32'],
            'content' => ['required', 'string', 'max:1600'],
            'from' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        $to = $this->input('to');

        return collect(is_array($to) ? $to : [$to])
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'error' => 'validation_failed',
            'message' => 'Os dados enviados são inválidos.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
