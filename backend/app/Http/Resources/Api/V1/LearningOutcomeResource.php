<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\LearningOutcome\Models\LearningOutcome;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LearningOutcome
 */
class LearningOutcomeResource extends JsonResource
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
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'objectives' => $this->whenLoaded('objectives', fn () => $this->objectives->map(fn ($objective) => [
                'id' => $objective->id,
                'code' => $objective->code,
                'correlation_level' => $objective->pivot->correlation_level,
                'source' => $objective->pivot->source->value,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
