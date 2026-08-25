<?php

namespace App\Http\Requests\Concerns;

use App\Services\IdeaClassificationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

trait ValidatesIdeaClassifications
{
    public function validateIdeaClassifications(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        try {
            app(IdeaClassificationService::class)->normalize(
                $this->input('classifications', []),
                $this->integer('category_id'),
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        }
    }
}
