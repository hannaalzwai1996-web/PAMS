<?php

namespace App\Http\Requests\Program;

use Illuminate\Foundation\Http\FormRequest;

class AssignProgramCoordinatorRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:assignCoordinator,program`
     * route middleware (ProgramPolicy) — not here. Whether the selected
     * user actually holds the `program_coordinator` role is a business
     * rule, not a format concern, so it's enforced by ProgramService
     * (BusinessRuleException, 422), not validated here.
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
            'user_id' => ['required', 'string', 'exists:users,id'],
        ];
    }
}
