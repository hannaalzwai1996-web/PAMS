# Reporting Module

| | |
|---|---|
| **Document ID** | ARCH-0004 |
| **Governing documents** | [ADR-0001](../adr/0001-system-architecture.md), [ARCH-0002](0002-po-plo-matrix-engine.md) |
| **Implementation** | `backend/` — fully implemented, not aspirational |

## Scope

Four report types, each exportable as PDF and Excel:

| Report | Data source |
|---|---|
| Program Specification Report | `Program` + its objectives, outcomes, and matrix summary — one composite document |
| PO Report | Program Objectives (existing `ProgramObjectiveRepositoryInterface`) |
| PLO Report | Program Learning Outcomes (existing `LearningOutcomeRepositoryInterface`) |
| PO-PLO Matrix Report | The matrix grid — reuses `PoPloMatrixService::exportRows()` (ARCH-0002 §3.2), not a third re-implementation of the flattening logic |

## New dependencies

`barryvdh/laravel-dompdf` (pure-PHP HTML→PDF, no headless-browser/Node dependency) and `maatwebsite/excel` (wraps PhpSpreadsheet) — both installed clean, no security advisories.

## Where the logic lives (ADR-0001 §2/§12)

- **`App\Domain\Reporting\Services\ProgramReportService`** — the only place report *content* is decided. Composes existing repositories/services; owns no data itself; returns plain arrays (`title`, `headings`, `rows` for tabular reports; a structured payload for the Specification report) — same "Service returns data, Controller decides delivery" split as the Matrix engine.
- **`App\Support\Reporting\Exports\*`** — `ArrayExport` (generic tabular sheet, reused for PO/PLO/Matrix and as each sheet inside the multi-sheet Specification workbook) and `ProgramSpecificationExport` (`WithMultipleSheets`). Pure rendering, no business logic.
- **`resources/views/reports/{tabular,specification}.blade.php`** — PDF templates. `tabular` is shared by PO, PLO, and Matrix reports (all three are `[headings, rows]` shaped); `specification` is the one bespoke document-style view.
- **`ReportController`** — four thin methods, each: fetch report data → pick PDF or Excel renderer. No report content computed here.

## Authorization

Reports are read-only, program-scoped exports — the same access rule already used for viewing objectives/outcomes/matrix (`admin`/`qa_officer` any program, `program_coordinator` only assigned programs). Rather than duplicate that check a fourth time, it was extracted into `AuthorizesProgramAccess` (a trait now shared by `ProgramObjectivePolicy`, `LearningOutcomePolicy`, `MatrixPolicy`, and the new minimal `ProgramPolicy`), and a genuine `ProgramPolicy::view()` ability was added — the first real ability on `Program` itself, auto-discovered, registered against `Program::class` (a slot `MatrixPolicy`'s docblock had already reserved for exactly this). Routes use `can:view,program` directly.

## Routes

```
GET /api/v1/programs/{program}/reports/specification/{format}
GET /api/v1/programs/{program}/reports/objectives/{format}
GET /api/v1/programs/{program}/reports/learning-outcomes/{format}
GET /api/v1/programs/{program}/reports/matrix/{format}
```

`{format}` constrained to `pdf|excel` via a route-group `where()`. No status restriction (reports are read-only, same as review/export in ARCH-0002 §1 BR-MTX-8).

## Two real bugs caught during verification (not just style nits)

1. `[$headings, ...$rows] = $this->matrix->exportRows($program);` — PHP does not support the spread operator in a destructuring *assignment target* (only in array-literal construction). This is a compile-time fatal, so it silently broke every test that merely loaded `ProgramReportService`, regardless of which method was under test — replaced with `array_shift()`.
2. Feature tests that hit a protected route with plain `->get()` (no `Accept: application/json`) triggered Laravel's default guest-redirect path, which tried to resolve a route literally named `login` — one was never registered (`api.v1.auth.login` and `filament.admin.auth.login` are the real names) — producing a 500 instead of a clean 401. Fixed at the root with `$middleware->redirectGuestsTo(...)` in `bootstrap/app.php`, returning `null` for any `api/*` request so an unauthenticated API call always 401s via `AuthenticationException`, regardless of what `Accept` header the caller sends. This was a real, if narrow, robustness gap — not just a test-writing mistake — since nothing outside this app's own Pest helpers guarantees that header is set.
