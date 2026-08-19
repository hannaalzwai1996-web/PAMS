<?php

namespace App\Http\Requests\Program;

use App\Domain\Program\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:update,program` route
     * middleware (ProgramPolicy) — not here.
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
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'code' => ['sometimes', 'string', 'max:30', Rule::unique('programs', 'code')->ignore($this->route('program'))],
            'name' => ['sometimes', 'string', 'max:200'],
            'level' => ['sometimes', 'string', Rule::in(Program::LEVELS)],
            'description' => ['sometimes', 'nullable', 'string'],
            'duration_years' => ['sometimes', 'integer', 'between:1,20'],
        ];
    }
}
