<?php

namespace App\Http\Requests\Idea;

use Illuminate\Foundation\Http\FormRequest;

class StoreIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_active;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:10000'],
            'problem_opportunity' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'visibility' => ['required', 'in:public,draft'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,zip', 'max:10240'], // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título de la idea es obligatorio.',
            'title.min' => 'El título debe tener al menos 5 caracteres.',
            'description.required' => 'La descripción de la propuesta es obligatoria.',
            'description.min' => 'Describe tu idea con al menos 20 caracteres.',
            'category_id.required' => 'Debes seleccionar una categoría.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',
            'attachments.*.max' => 'Los archivos adjuntos no deben superar los 10 MB.',
            'attachments.*.mimes' => 'Formato de archivo no permitido. Solo se aceptan PDF, documentos de oficina e imágenes.',
        ];
    }
}
