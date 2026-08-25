<?php

namespace App\Http\Requests\Idea;

use Illuminate\Foundation\Http\FormRequest;

class RequestIdeaPublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $idea = $this->route('idea');

        return $idea && $this->user()?->can('requestPublication', $idea);
    }

    public function rules(): array
    {
        return [];
    }
}
