<?php

namespace App\Http\Requests\LearningOutcome;

use App\Support\Enums\LearningOutcomeCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLearningOutcomeRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:create,...` route middleware
     * (LearningOutcomePolicy) — not here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Per-program code uniqueness is a business rule enforced by
     * LearningOutcomeService (ConflictException, 409), not a format
     * concern — so it isn't validated here.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20'],
            'statement' => ['required', 'string'],
            'category' => ['required', Rule::enum(LearningOutcomeCategory::class)],
        ];
    }
}
