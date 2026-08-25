<?php

namespace App\Http\Requests\Taxonomy;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCategoryDimensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [];
    }
}
