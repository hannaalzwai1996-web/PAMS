/** Mirrors App\Support\Enums\ProgramStatus (backend). */
export type ProgramStatus = 'draft' | 'submitted' | 'approved';

/** Mirrors App\Support\Enums\LearningOutcomeCategory (backend). */
export type LearningOutcomeCategory = 'A' | 'B' | 'C' | 'D';

export type MatrixEntrySource = 'auto' | 'manual';

/** Mirrors App\Domain\Program\Models\Program::LEVELS (backend). */
export type ProgramLevel = 'diploma' | 'bachelor' | 'master' | 'phd';

/** A program's assigned coordinator, as embedded in ProgramResource (`whenLoaded` — see ProgramCoordinator note on `coordinators` below). */
export interface ProgramCoordinator {
  id: string;
  name: string;
  email: string;
}

/** Mirrors App\Http\Resources\Api\V1\ProgramResource. */
export interface Program {
  id: string;
  code: string;
  name: string;
  level: ProgramLevel;
  description: string | null;
  status: ProgramStatus;
  duration_years: number;
  department: {
    id: number;
    code: string;
    name: string;
  };
  /**
   * Only present when the backend eager-loaded it (`ProgramService::show()`
   * — the single-program GET, not the list). Absent, not `[]`, on
   * `GET /programs` responses: Laravel's `whenLoaded` omits the key
   * entirely rather than sending an empty array.
   */
  coordinators?: ProgramCoordinator[];
  objectives_count: number;
  learning_outcomes_count: number;
  created_at: string;
  updated_at: string;
}

/** Matches StoreProgramRequest/UpdateProgramRequest — the same shape is sent for both create and edit, mirroring ProgramObjectivePayload/LearningOutcomePayload. */
export interface ProgramPayload {
  department_id: number;
  code: string;
  name: string;
  level: ProgramLevel;
  description: string;
  duration_years: number;
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
