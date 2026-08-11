<?php

namespace App\Http\Requests\ProgramObjective;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramObjectiveRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:update,objective` route
     * middleware (ProgramObjectivePolicy) — not here.
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
        ];
    }
}
