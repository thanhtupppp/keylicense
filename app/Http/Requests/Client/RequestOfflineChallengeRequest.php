<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class RequestOfflineChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string'],
            'product_code' => ['required', 'string'],
            'domain' => ['nullable', 'string', 'max:255'],
            'environment' => ['nullable', 'string', 'max:50'],
        ];
    }
}
