<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ActivateInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'organizational_unit_id' => [
                'required',
                'integer',
                Rule::exists('regionals', 'id')->where('is_active', true),
            ],
            'bio' => ['nullable', 'string', 'max:500'],
        ];
    }
}
