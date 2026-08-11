<?php

namespace App\Http\Requests\Matrix;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMatrixRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:update,...` route middleware
     * (MatrixPolicy) — not here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * BR-MTX-6 (cross-program pairs rejected) is checked here at the
     * format-validation layer AND again inside PoPloMatrixService, since a
     * Service must not assume the request validated correctly.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $programId = $this->route('program')->id;

        return [
            'entries' => ['present', 'array'],
            'entries.*.objective_id' => [
                'required',
                'string',
                Rule::exists('program_objectives', 'id')
                    ->where('program_id', $programId)
                    ->whereNull('deleted_at'),
            ],
            'entries.*.learning_outcome_id' => [
                'required',
                'string',
                Rule::exists('learning_outcomes', 'id')
                    ->where('program_id', $programId)
                    ->whereNull('deleted_at'),
            ],
            'entries.*.correlation_level' => ['required', 'integer', 'between:1,3'],
        ];
    }
}
