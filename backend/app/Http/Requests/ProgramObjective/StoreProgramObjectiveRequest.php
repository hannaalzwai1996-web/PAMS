<?php

namespace App\Http\Requests\ProgramObjective;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramObjectiveRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:create,...` route middleware
     * (ProgramObjectivePolicy) — not here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Per-program code uniqueness is a business rule enforced by
     * ProgramObjectiveService (ConflictException, 409), not a format
     * concern — so it isn't validated here.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20'],
            'statement' => ['required', 'string'],
        ];
    }
}
