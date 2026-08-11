<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\LearningOutcome\Models\ProgramObjective;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProgramObjective
 */
class ProgramObjectiveResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'code' => $this->code,
            'statement' => $this->statement,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
