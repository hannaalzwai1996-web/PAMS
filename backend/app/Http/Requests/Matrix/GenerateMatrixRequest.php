<?php

namespace App\Http\Requests\Matrix;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMatrixRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:generate,...` route middleware
     * (MatrixPolicy) — not here.
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
            'force' => ['sometimes', 'boolean'],
        ];
    }
}
