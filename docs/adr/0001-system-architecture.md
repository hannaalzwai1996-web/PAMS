# ADR-0001: System Architecture — Academic Program Specification and Quality Assurance Management System

| | |
|---|---|
| **Status** | Accepted |
| **Date** | 2026-07-27 |
| **Owner** | Senior Software Architect |
| **Scope** | Binding for all future development. Any conflicting request must be flagged and discussed before implementation. |

## 0. Purpose and Authority

This document is the **single source of truth** for architectural, structural, and naming decisions in this project. It governs how the system is built, not just how it currently looks. All code, PRs, and future feature work must conform to it.

Rules marked **[IMMUTABLE]** in Section 12 must never change without a new ADR that explicitly supersedes this one and is agreed with the project owner. Everything else may evolve via a follow-up ADR, but never silently.

If a future request conflicts with this ADR, the conflict must be explained **before** any code is changed.

---

## 1. Architecture Overview

### 1.1 Style

The system follows **Clean Architecture**, **SOLID principles**, strict **separation of concerns**, and a **modular-by-domain** structure so each academic domain (Programs, Learning Outcomes, Course Mappings, Accreditation, Quality Reports) can evolve independently.

### 1.2 High-Level Components

```
┌─────────────────────────────┐        ┌──────────────────────────────┐
│   React + TypeScript SPA    │        │   FilamentPHP Admin Panel     │
│   (Tailwind CSS)             │        │   (server-rendered, Laravel)  │
│   QA Officers / Coordinators │        │   System Administrator        │
└──────────────┬───────────────┘        └───────────────┬────────────────┘
               │ REST/JSON (Sanctum token)               │ direct in-process calls
               ▼                                          ▼
        ┌─────────────────────────────────────────────────────┐
        │                  Laravel REST API                     │
        │  Controllers → Form Requests → Services → Repositories│
        │  → Eloquent Models → MySQL                             │
        │  (Filament Resources call the same Services/Repos)     │
        └──────────────────────────┬──────────────────────────┘
                                     ▼
                              ┌────────────┐
                              │   MySQL    │
                              └────────────┘
```

### 1.3 Layer Responsibilities

| Layer | Responsibility | Must NOT do |
|---|---|---|
| **Frontend (React/TS)** | Presentation, client-side state, UX for QA Officer & Program Coordinator workflows, calling the REST API | Business rules, direct DB access |
| **Admin Panel (Filament)** | Internal CRUD/admin operations for System Administrator, built on Filament Resources/Pages/Widgets | Business logic (delegates to Services) |
| **API Layer (Controllers)** | HTTP request/response handling, routing, delegating to Services | Business logic, DB queries |
| **Service Layer** | All business rules, orchestration, transactions | HTTP concerns, direct Eloquent query building beyond calling repositories |
| **Repository Layer** | Data access abstraction over Eloquent | Business rules |
| **Model Layer (Eloquent)** | Persistence mapping, relationships, casts, scopes | Business rules, HTTP concerns |
| **Database (MySQL)** | Durable storage, referential integrity, constraints | — |

This ensures the **same Service/Repository layer is reused by both the REST API and Filament**, so business logic exists in exactly one place.

---

## 2. Backend Architecture Decisions

### 2.1 Pattern Stack

Repository Pattern + Service Layer + DTOs + Dependency Injection + Form Requests + API Resources.

### 2.2 Request Flow

```
HTTP Request
  → Route
  → FormRequest (validation + authorization)
  → Controller (thin — delegates only)
  → Service (business logic, wraps DTO)
  → Repository Interface (contract)
  → Repository Implementation (Eloquent)
  → Model
  → MySQL
  → Model
  → Service (maps to DTO / domain result)
  → API Resource (response transformation)
  → JSON Response
```

Controllers never call Repositories or Models directly. Services never touch `$request` or return HTTP responses.

### 2.3 Folder Structure

```
app/
├── Domain/
│   ├── Program/
│   │   ├── Models/
│   │   │   └── Program.php
│   │   ├── DTOs/
│   │   │   └── ProgramDTO.php
│   │   ├── Services/
│   │   │   └── ProgramService.php
│   │   ├── Repositories/
│   │   │   ├── Contracts/
│   │   │   │   └── ProgramRepositoryInterface.php
│   │   │   └── ProgramRepository.php
│   │   └── Policies/
│   │       └── ProgramPolicy.php
│   ├── LearningOutcome/
│   ├── CourseMapping/
│   ├── Accreditation/
│   └── QualityReport/
│
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           └── ProgramController.php
│   ├── Requests/
│   │   └── Program/
│   │       ├── StoreProgramRequest.php
│   │       └── UpdateProgramRequest.php
│   ├── Resources/
│   │   └── Program/
│   │       ├── ProgramResource.php
│   │       └── ProgramCollection.php
│   └── Middleware/
│
├── Filament/
│   ├── Resources/
│   │   └── ProgramResource.php
│   ├── Pages/
│   └── Widgets/
│
├── Providers/
│   └── RepositoryServiceProvider.php   (binds Interfaces → Implementations)
│
└── Support/
    ├── Enums/
    ├── Exceptions/
    └── Traits/
```

**Rationale:** domain-first (`app/Domain/*`) grouping over Laravel's default type-first grouping, because this system has clearly bounded academic domains that should be independently testable and extensible without cross-domain coupling. Http/ and Filament/ remain type-first since they are delivery mechanisms, not domains.

### 2.4 Dependency Injection Rule

Every Service depends on a Repository **interface**, never a concrete class. Bindings live in `RepositoryServiceProvider`. This keeps Services testable via mocked interfaces and keeps Eloquent swappable in theory (even if never actually swapped).

---

## 3. Filament Architecture Decision

Filament is **internal-admin-only**, used by the System Administrator.

- **Resources**: thin — form/table schema definitions and calls into Services/Repositories for anything beyond simple CRUD (e.g., bulk actions that touch business rules must call a Service method, not write logic inline).
- **Pages**: custom pages (e.g., dashboards, settings) call Services for data; no query building or rule evaluation in the Page class.
- **Widgets**: read-only, pull data via Repositories/Services — never raw `DB::` or ad-hoc Eloquent chains.
- **Actions**: custom Filament Actions (e.g., "Approve Quality Report") must invoke a Service method (`QualityReportService::approve()`), never implement the approval logic inline in the Action's closure.

**[IMMUTABLE] Filament must not contain business logic.** If a Filament Resource needs a rule (e.g., "a Program cannot be published without at least one Learning Outcome mapped"), that rule is implemented once in `ProgramService` and called identically from both the API Controller and the Filament Resource/Action.

---

## 4. Database Standards

| Aspect | Rule |
|---|---|
| **Table names** | plural, snake_case — `programs`, `learning_outcomes`, `course_mappings` |
| **Column names** | snake_case — `program_id`, `created_at`, `is_active` |
| **Primary key** | **UUID** (`ulid` column type via `HasUuids`/ULID) for all domain tables. Rationale: multi-institution data, safe for external accreditation exports/APIs, avoids sequential-ID enumeration on a compliance-sensitive system. Pivot/lookup tables may use auto-increment `id` where UUID adds no value. |
| **Foreign keys** | `{singular_table}_id`, e.g., `program_id`, `course_id`; always with explicit `->constrained()->cascadeOnDelete()` or `->restrictOnDelete()` decided per relationship, never silent default |
| **Timestamps** | `created_at`, `updated_at` on every table; `deleted_at` (SoftDeletes) on domain entities that require audit history (Programs, Quality Reports, Accreditation Requirements) |
| **Booleans** | prefixed `is_`/`has_`, e.g., `is_active`, `has_accreditation` |
| **Enums** | stored as string-backed PHP enums, column type `string` (not MySQL native `ENUM`) for migration flexibility |

### Relationship Rules

- **One-to-One**: foreign key on the dependent table, e.g., `program_specifications.program_id`.
- **One-to-Many**: FK on the "many" side, e.g., `learning_outcomes.program_id`.
- **Many-to-Many**: dedicated pivot table named by joining both singular names alphabetically, e.g., `course_learning_outcome` (course_id, learning_outcome_id), with its own `id` (auto-increment) and timestamps when the mapping itself needs metadata (e.g., mapping strength/level).

---

## 5. Naming Conventions **[IMMUTABLE]**

| Artifact | Convention | Example |
|---|---|---|
| Model | Singular PascalCase | `Program` |
| Controller | Singular + `Controller` | `ProgramController` |
| Service | Singular + `Service` | `ProgramService` |
| Repository | Singular + `Repository` | `ProgramRepository` |
| Repository Interface | Singular + `RepositoryInterface` | `ProgramRepositoryInterface` |
| DTO | Singular + `DTO` | `ProgramDTO` |
| Form Request | Verb + Singular + `Request` | `StoreProgramRequest`, `UpdateProgramRequest` |
| API Resource | Singular + `Resource` | `ProgramResource` |
| Filament Resource | Singular + `Resource` (in `Filament/Resources`) | `ProgramResource` |
| Policy | Singular + `Policy` | `ProgramPolicy` |
| Database table | Plural snake_case | `programs` |
| Migration | Laravel default timestamped, descriptive | `create_programs_table` |
| React component | PascalCase | `ProgramList.tsx` |
| React hook | `use` + PascalCase | `useProgram.ts` |
| TS type/interface | PascalCase, domain-suffixed | `Program`, `ProgramDTO` (mirrors backend) |

---

## 6. Frontend Architecture

```
src/
├── components/     # Reusable, presentation-only UI (Button, Modal, Table, FormField)
├── pages/          # Route-level components; compose features, hold page layout
├── features/       # Domain-oriented feature modules (programs/, outcomes/, accreditation/)
│   └── programs/
│       ├── components/   # feature-specific UI
│       ├── hooks/        # feature-specific hooks (useProgramList)
│       └── api.ts         # feature-specific API calls
├── services/       # Cross-cutting API client (axios instance, interceptors, auth token handling)
├── hooks/          # Shared, cross-feature hooks (useAuth, usePagination, useDebounce)
├── types/          # Shared TS types/interfaces mirroring backend DTOs/Resources
├── utils/          # Pure helper functions (formatters, validators)
├── contexts/       # React Context providers (AuthContext, ThemeContext)
└── routes/         # Route definitions / router config
```

**Rule:** `components/` never imports from `features/`. `features/*` may use `components/`, `hooks/`, `services/`, `types/`, `utils/`. `pages/` composes `features/` and `components/`. This keeps dependency direction one-way: `pages → features → components/hooks/services/types/utils`.

---

## 7. API Standards

| Aspect | Rule |
|---|---|
| **Versioning** | URI-based: `/api/v1/...`. Breaking changes ship as `/api/v2/...`, old version kept until deprecation window closes |
| **Endpoint naming** | plural, kebab/snake-free nouns — `/api/v1/programs`, `/api/v1/programs/{program}/learning-outcomes` |
| **HTTP methods** | `GET` list/show, `POST` create, `PUT/PATCH` update, `DELETE` remove — no verbs in URLs |
| **Response structure** | `{ "data": {...}|[...], "meta": {...}, "links": {...} }` via Laravel API Resources |
| **Error format** | `{ "message": "...", "errors": { "field": ["..."] } }` with correct HTTP status (422 validation, 401/403 auth, 404 not found, 500 server) |
| **Pagination** | Laravel's built-in cursor/length-aware paginator, exposed as `meta.current_page`, `meta.last_page`, `meta.total`, `links.next`/`links.prev` |

---

## 8. Authentication and Authorization

- **Authentication**: Laravel Sanctum (SPA cookie-based auth for the React app; token-based for any external/API-only clients).
- **Authorization**: Spatie `laravel-permission` (roles + permissions), enforced via Policies called from Services/Controllers, **not** scattered `if` checks.

**Roles**: `admin`, `qa_officer`, `program_coordinator`.

**Middleware rules**:
- All `/api/v1/*` routes: `auth:sanctum`.
- Role/permission checks via route middleware (`can:...`) mapped to Policy methods, e.g., `Route::put('/programs/{program}', ...)->middleware('can:update,program')`.
- Filament panel: separate guard, restricted to users with `admin` role (`->canAccessPanel()` check).

---

## 9. Security Rules **[IMMUTABLE — practices, not implementation details]**

- **Validation**: all input validated via Form Requests; never trust raw `$request->all()` in a Service.
- **Authorization**: every mutating action checked via a Policy (defense in depth: middleware **and** Service-level check).
- **SQL injection**: Eloquent/Query Builder only — no raw string-concatenated SQL.
- **XSS**: React auto-escapes by default — no `dangerouslySetInnerHTML` without sanitization; API never returns unescaped HTML for direct render.
- **File uploads**: validate MIME + extension allowlist, store outside public root or via signed URLs, virus/size limits enforced server-side, never trust client-provided filename.
- **Passwords**: bcrypt/argon2 (Laravel default hashing), minimum complexity rules via Form Request, rate-limited login attempts.

---

## 10. Git Standards

- **Branch naming**: `feature/{ticket-or-short-desc}`, `fix/{short-desc}`, `chore/{short-desc}`, `hotfix/{short-desc}`.
- **Commits**: Conventional Commits — `feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:` + concise imperative summary.
- **Workflow**: trunk-based with short-lived feature branches → PR → review → squash-merge to `main`. No direct pushes to `main`.

---

## 11. Development Rules

- **Code style**: PSR-12 (PHP, enforced via Laravel Pint), Prettier + ESLint (TypeScript/React).
- **Testing**: Pest/PHPUnit feature tests per API endpoint, unit tests per Service; React components tested with Vitest/RTL for logic-bearing components. Services and Repositories must be unit-testable via interface mocks.
- **Error handling**: Services throw domain exceptions (`app/Support/Exceptions`), caught by a global exception handler mapping to consistent JSON error format (Section 7).
- **Documentation**: PHPDoc on Services/Repositories describing business intent (not restating the signature); README per module for anything non-obvious; OpenAPI/Swagger spec kept in sync with `Http/Resources`.

---

## 12. Rules That Must Never Change **[IMMUTABLE]**

1. Filament contains **zero** business logic — all rules live in Services.
2. Controllers never call Repositories or Models directly — only Services.
3. Services depend on Repository **interfaces**, never concrete Repository classes.
4. Naming conventions in Section 5 are fixed project-wide.
5. All domain tables use UUID primary keys; snake_case plural table names.
6. All API responses follow the `data/meta/links` envelope and versioned `/api/v1` prefix.
7. Authorization is enforced via Policies, never inline role-string checks in controllers.
8. No raw SQL string concatenation anywhere in the codebase.

Any future request that conflicts with these rules will be flagged with an explanation of the conflict before implementation proceeds.
