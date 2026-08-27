<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $features = array_keys(config('ai.features'));
        $rules = [
            'provider_enabled' => ['nullable', 'boolean'],
            'api_key' => ['nullable', 'string', 'min:20', 'max:500'],
            'features' => ['required', 'array', 'size:'.count($features)],
        ];

        foreach (config('ai.features') as $feature => $defaults) {
            $rules["features.{$feature}"] = ['required', 'array'];
            $rules["features.{$feature}.enabled"] = ['nullable', 'boolean'];
            $rules["features.{$feature}.model"] = ['required', 'string', Rule::in($defaults['allowed_models'])];
            $rules["features.{$feature}.reasoning_effort"] = ['nullable', Rule::in(['none', 'low', 'medium', 'high', 'xhigh', 'max'])];
            $rules["features.{$feature}.fallback_model"] = ['nullable', 'string', Rule::in($defaults['allowed_models'])];
            $rules["features.{$feature}.fallback_reasoning_effort"] = ['nullable', Rule::in(['none', 'low', 'medium', 'high', 'xhigh', 'max'])];
            $rules["features.{$feature}.ambiguity_threshold"] = ['nullable', 'numeric', 'between:0.5,0.95'];
        }

        return $rules;
    }
}
