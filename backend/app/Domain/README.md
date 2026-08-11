# Domain Modules

Domain-first structure per [ADR-0001 §2.3](../../../docs/adr/0001-system-architecture.md). Each subfolder here is a bounded academic domain and follows the same internal layout:

```
{Domain}/
├── Models/                    Eloquent models for this domain
├── DTOs/                      Data Transfer Objects passed between Controller/Filament ↔ Service
├── Services/                  All business logic and orchestration
├── Repositories/
│   ├── Contracts/             Repository interfaces (Services depend on these, never on the Eloquent implementation)
│   └── *.php                  Eloquent-backed implementations, bound in AppServiceProvider/RepositoryServiceProvider
└── Policies/                  Authorization rules, registered against the domain's Models
```

**Rules (ADR-0001 §12, immutable):**
- Controllers and Filament Resources/Pages/Widgets/Actions call **Services** only — never Repositories or Models directly.
- Services depend on **Repository interfaces** (`Repositories/Contracts/*`), never concrete Eloquent repository classes.
- Business rules live exclusively in Services. Filament contains none.

## Modules

| Module | Scope |
|---|---|
| `Program/` | Program Specification lifecycle (draft/submitted/approved), versioning |
| `LearningOutcome/` | Program Learning Outcomes (PLOs) and Program Objectives (PEOs), including the PO-PLO correlation matrix |
| `CourseMapping/` | Course catalog and the Course × Learning Outcome curriculum mapping matrix |
| `Accreditation/` | Accreditation requirements catalog and program evidence linkage |
| `QualityReport/` | Periodic Quality Report lifecycle and feedback |

Each module is currently scaffolded (empty) — no business features have been implemented yet. Implementation follows the request flow defined in ADR-0001 §2.2:

```
Route → FormRequest → Controller → Service → Repository Interface → Repository → Model → MySQL
```
