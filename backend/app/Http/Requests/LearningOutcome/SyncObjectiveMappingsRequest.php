<?php

namespace App\Http\Requests\LearningOutcome;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncObjectiveMappingsRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:manageMappings,outcome` route
     * middleware (LearningOutcomePolicy) — not here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Each objective_id must exist AND belong to the same program as the
     * learning outcome being mapped — cross-program mappings are rejected
     * here at the validation layer (LearningOutcomeService repeats this
     * check too, since a Service must not assume the request was
     * validated correctly).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $programId = $this->route('program')->id;

        return [
            'mappings' => ['present', 'array'],
            'mappings.*.objective_id' => [
                'required',
                'string',
                Rule::exists('program_objectives', 'id')
                    ->where('program_id', $programId)
                    ->whereNull('deleted_at'),
            ],
            'mappings.*.correlation_level' => ['required', 'integer', 'between:1,3'],
        ];
    }
}
