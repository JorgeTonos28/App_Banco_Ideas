<?php

namespace App\Http\Requests\Idea;

use Illuminate\Foundation\Http\FormRequest;

class CancelIdeaPublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $idea = $this->route('idea');

        return $idea && $this->user()?->can('cancelPublication', $idea);
    }

    public function rules(): array
    {
        return [];
    }
}
