<?php

namespace App\Support\Enums;

/**
 * The fixed PLO categorization scheme for PAMS. Letter values (not
 * spelled-out words) match the accreditation-standard convention this was
 * specified against.
 */
enum LearningOutcomeCategory: string
{
    case Knowledge = 'A';
    case CognitiveSkills = 'B';
    case PracticalSkills = 'C';
    case GeneralSkills = 'D';

    public function label(): string
    {
        return match ($this) {
            self::Knowledge => 'Knowledge',
            self::CognitiveSkills => 'Cognitive Skills',
            self::PracticalSkills => 'Practical Skills',
            self::GeneralSkills => 'General Skills',
        };
    }
}
