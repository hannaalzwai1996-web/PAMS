/** Mirrors App\Support\Enums\ProgramStatus (backend). */
export type ProgramStatus = 'draft' | 'submitted' | 'approved';

/** Mirrors App\Support\Enums\LearningOutcomeCategory (backend). */
export type LearningOutcomeCategory = 'A' | 'B' | 'C' | 'D';

export type MatrixEntrySource = 'auto' | 'manual';

/** Mirrors App\Http\Resources\Api\V1\ProgramResource. */
export interface Program {
  id: string;
  code: string;
  name: string;
  level: 'diploma' | 'bachelor' | 'master' | 'phd';
  status: ProgramStatus;
  duration_years: number;
  department: {
    id: number;
    code: string;
    name: string;
  };
  objectives_count: number;
  learning_outcomes_count: number;
  created_at: string;
  updated_at: string;
}

/** Mirrors App\Http\Resources\Api\V1\ProgramObjectiveResource. */
export interface ProgramObjective {
  id: string;
  program_id: string;
  code: string;
  statement: string;
  created_at: string;
  updated_at: string;
}

export interface ProgramObjectivePayload {
  code: string;
  statement: string;
}

export interface MappedObjective {
  id: string;
  code: string;
  correlation_level: number;
  source: MatrixEntrySource;
}

/** Mirrors App\Http\Resources\Api\V1\LearningOutcomeResource. */
export interface LearningOutcome {
  id: string;
  program_id: string;
  code: string;
  statement: string;
  category: LearningOutcomeCategory;
  category_label: string;
  objectives?: MappedObjective[];
  created_at: string;
  updated_at: string;
}

export interface LearningOutcomePayload {
  code: string;
  statement: string;
  category: LearningOutcomeCategory;
}

export interface ObjectiveMapping {
  objective_id: string;
  correlation_level: 1 | 2 | 3;
}
