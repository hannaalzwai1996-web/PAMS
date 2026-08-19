<?php

namespace App\Http\Requests\Program;

use App\Domain\Program\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:create,...` route middleware
     * (ProgramPolicy) — not here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `status`/`current_version_no` are intentionally not accepted here —
     * the lifecycle is never client-settable (P0.1 scope), so a new
     * Program always gets the schema's own default ('draft', version 1).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:30', 'unique:programs,code'],
            'name' => ['required', 'string', 'max:200'],
            'level' => ['required', 'string', Rule::in(Program::LEVELS)],
            'description' => ['nullable', 'string'],
            'duration_years' => ['required', 'integer', 'between:1,20'],
        ];
    }
}
