<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmOfflineChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string'],
            'activation_id' => ['required', 'string', 'max:255'],
            'challenge' => ['required', 'string'],
        ];
    }
}
