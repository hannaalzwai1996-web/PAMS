import type { MatrixEntrySource } from './program';

/** Mirrors the grid shape built by PoPloMatrixService::review() (backend). */
export interface MatrixCell {
  learning_outcome_id: string;
  code: string;
  correlation_level: number | null;
  source: MatrixEntrySource | null;
}

export interface MatrixRow {
  objective: { id: string; code: string };
  cells: MatrixCell[];
}

export interface MatrixSummary {
  auto: number;
  manual: number;
  unmapped: number;
  total_pairs: number;
}

export interface MatrixGrid {
  objectives: Array<{ id: string; code: string }>;
  outcomes: Array<{ id: string; code: string; category: string }>;
  rows: MatrixRow[];
  summary: MatrixSummary;
}

export interface MatrixBulkEditEntry {
  objective_id: string;
  learning_outcome_id: string;
  correlation_level: 1 | 2 | 3;
}
