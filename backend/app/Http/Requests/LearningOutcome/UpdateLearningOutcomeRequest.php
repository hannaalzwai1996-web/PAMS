<?php

namespace App\Http\Requests\LearningOutcome;

use App\Support\Enums\LearningOutcomeCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLearningOutcomeRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:update,outcome` route
     * middleware (LearningOutcomePolicy) — not here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:20'],
            'statement' => ['sometimes', 'string'],
            'category' => ['sometimes', Rule::enum(LearningOutcomeCategory::class)],
        ];
    }
}
