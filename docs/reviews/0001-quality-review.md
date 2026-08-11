# Pre-Deployment Software Quality Review

| | |
|---|---|
| **Document ID** | REVIEW-0001 |
| **Scope** | Full stack: `backend/` (Laravel + Filament) and `frontend/` (React) |
| **Method** | Direct code audit against ADR-0001 and its own stated rules, not a generic checklist — every finding below cites a real file. Confirmed findings were fixed and covered with a regression test in the same pass, not just written up. |

## 0. Headline numbers

| | Before this review | After |
|---|---|---|
| Backend tests | 105 (all Feature-level, hitting a real DB) | **113** — added a genuine Unit layer (mocked repositories, no DB, sub-50ms) |
| Frontend tests | **0** | **25** (18 component, 7 integration) |
| Confirmed bugs found | — | **6** (2 security/robustness, 1 performance, 1 architecture, 1 code-quality, 1 latent frontend bug) — all fixed, all covered by a new test |

---

## 1. Backend Tests

### 1.1 Unit Tests (new)

`tests/Unit/Domain/LearningOutcome/PoPloMatrixServiceTest.php`, `tests/Unit/Domain/User/UserServiceGuardsTest.php` — true isolated unit tests: every dependency is either Mockery-mocked (Repository interfaces) or a real, facade-free pure class (`LexicalCorrelationScorer`). No database, no HTTP, no Laravel bootstrap. They run in **~40–80ms total**, versus the Feature suite's ~6s, and prove the Service's *own decision logic* — e.g. "`generate()` never overwrites a `manual` cell, even with `force`" — independent of infrastructure.

This is a genuinely new layer, not a relabeling: everything that existed before this review was Feature-level (real DB, real HTTP kernel). The unit/Feature split now mirrors what each is actually good at — units for business-rule branching, Feature tests for "does the whole stack actually work."

### 1.2 Feature Tests (existing, 105 tests)

Cover every module built this session: Auth, User Management, Program Objectives, Learning Outcomes, PO-PLO Matrix, Reporting, and Filament (Users/Departments/Programs resources, Dashboard, System Health). These already exercise the full HTTP kernel → middleware → Controller → Service → Repository → DB → Resource path for every endpoint.

### 1.3 "API Tests"

Not a separate layer here, by design: this backend is API-only (no server-rendered web app besides Filament, which has its own dedicated Feature tests). The Feature suite *is* the API test suite — every test asserts on real HTTP status codes, the `{data,meta,links}`/`{message,errors}` envelope, and authorization outcomes through actual routes. Building a third, parallel "API test" layer that re-asserts the same HTTP contract would be duplication, not additional coverage.

---

## 2. Frontend Tests (new — zero before this review)

### 2.1 Infrastructure

Vitest + React Testing Library + jsdom, configured in `vite.config.ts`'s `test` key (one config file, not a second parallel one). `src/test/renderWithProviders.tsx` wraps `QueryClientProvider` + `MemoryRouter` + (optional) `AuthProvider` — the same provider tree `App.tsx` uses — so tests exercise real wiring, not a stub. `vi.mock()` targets the `services/*.ts` boundary specifically, which is the actual payoff of the service-layer isolation described in ARCH-0003 §6: nothing needs a real backend or a fetch-interception library to test the UI.

### 2.2 Component Tests (18)

`Button`, `FormField`, `Badge`, `Modal` (shared UI primitives — rendering, interaction, disabled states) and `UsersTable` (a feature component, tested via props alone: row rendering, self-action disabling, callback wiring).

### 2.3 Integration Tests (7)

- **Login flow** (`login-flow.test.tsx`) — real `AuthProvider` + `LoginForm` + `LoginPage`, mocked `authService`; drives an actual form submit and asserts the credentials sent and the error message rendered on failure.
- **Protected routing** (`protected-routing.test.tsx`) — real `ProtectedRoute`, three session states (loading, unauthenticated → redirect, authenticated → renders).
- **Users create flow** (`users-create-flow.test.tsx`) — the *only* way a PAMS account is ever created (ARCH-0001 §0: no public registration): full `UsersPage` render, form fill, submit, list refetch after create, and a field-validation-error path.

This test **found a real bug**, not just exercised existing code — see §5.6.

---

## 3. SOLID

| Principle | Finding |
|---|---|
| **SRP** | Services stay cohesive: `UserService` covers account lifecycle (register/update/role/permissions/delete/stats) — six methods, one responsibility ("manage a user account"), not six. No Service found doing two unrelated things. |
| **OCP** | Fixed sets (`Role`, `ProgramStatus`, `LearningOutcomeCategory`, `MatrixEntrySource`) are enums, not strings — adding a value is additive, changing behavior per-value is a `match` away from the data. |
| **LSP** | Every domain Repository extends `EloquentRepository<T>` and satisfies `RepositoryInterface` without narrowing or throwing "not supported" — no substitutability violations found. |
| **ISP** | One conscious tension, not a defect: `ProgramRepositoryInterface` currently has zero domain-specific methods (inherits the generic CRUD contract) because no full Program CRUD Service exists yet — deliberately documented in the interface's own docblock as "the full Program workflow module will add its own methods to this same interface" rather than replacing it. Worth revisiting if that module lands and the generic contract turns out to be the wrong shape, not urgent now. |
| **DIP** | Every Service depends on a Repository *interface*, bound once in `RepositoryServiceProvider` — confirmed no Service anywhere constructs or type-hints a concrete Repository class. |

## 4. Clean Architecture

- **Confirmed violation, fixed** (§5.5): `AuthController` contained a business rule (deactivated users can't log in) directly in the Controller — contradicting the project's own immutable rule. Extracted to `AuthenticationService`.
- **Filament**: re-audited `UserResource`, `DepartmentResource`, `ProgramResource` — zero business conditionals found; every mutation routes through the same Services the REST API uses (`HandlesDomainExceptions` trait confirmed to be pure UI-error-translation, no decisions).
- **No raw SQL anywhere** (`grep -rn "DB::statement\|DB::raw\|whereRaw\|selectRaw" app/` → zero hits) — every query goes through Eloquent/query-builder parameter binding.
- **Controllers spot-checked** (`ReportController`, `ProgramMatrixController`, `Admin/UserController`, domain controllers) — the only conditionals present are delivery-mechanism branches (PDF vs Excel, format selection), not business rules.

## 5. Security

| # | Finding | Status |
|---|---|---|
| 5.1 | SRS **SEC-09** ("rate-limit authentication attempts") was specified but never implemented — `POST /auth/login` had no throttle. | **Fixed**: named rate limiter (`RateLimiter::for('login', ...)`), keyed by `email+IP` (not IP alone, so one throttled account can't be used to lock out others behind a shared NAT, and an attacker can't lock out a victim by spraying IPs at one account). `throttle:login` applied to the route. Regression test confirms a 429 after 5 failed attempts. |
| 5.2 | Unauthenticated requests to `api/*` without an explicit `Accept: application/json` header hit Laravel's default guest-redirect path, which tried to resolve a route literally named `login` — never registered (ours are `api.v1.auth.login` / `filament.admin.auth.login`) — and 500'd instead of cleanly 401ing. | **Fixed** (found and fixed during the Reporting Module task, carried forward here since it's exactly the kind of gap this review exists to catch): `redirectGuestsTo()` in `bootstrap/app.php` returns `null` for any `api/*` request. |
| 5.3 | Mass assignment, XSS, SQL injection | No findings. `is_active`'s fillable status is deliberately scoped (see the model's own comment) and only reachable through Policy-gated Services; all Blade output uses `{{ }}` (no `{!! !!}` anywhere in `resources/views/`); Eloquent-only queries throughout. |
| 5.4 | CORS / CSRF | `config/cors.php` restricts `allowed_origins` to the configured frontend origin (not `*`) with `supports_credentials: true` — correct for cookie-based SPA auth. Sanctum's CSRF-cookie flow verified end-to-end against a live server earlier this session. |
| 5.5 | Business rule embedded in a Controller (`AuthController`'s `is_active` check) | **Fixed** — see §4. Not itself an exploitable vulnerability, but exactly the kind of drift that produces one later (a second entry point to login logic that forgets the check). |
| 5.6 | Frontend: `toApiError()` was not idempotent — calling it on an already-`ApiError` value silently dropped `.status`/`.errors`, falling through to the generic `Error` branch (`ApiError extends Error`). Currently unreachable in production (no service constructs an `ApiError` itself; only Axios errors flow through it), but a real latent gap a future refactor could trip over — and *was* tripped by a legitimate way of writing a test (mocking a rejection as an `ApiError` directly). | **Fixed**: `instanceof ApiError` checked first, returned as-is. |
| 5.7 | Deployment-environment items (not code) | `.env` currently has `APP_DEBUG=true`, `LOG_LEVEL=debug` (correct for local dev) — must be `false`/`error`-or-higher in production; `SESSION_SECURE_COOKIE` is unset (defaults falsy) and must be `true` once served over HTTPS. Listed in §8, not fixed here since these are environment config, not code. |
| 5.8 | Audit logging | `docs/database/0001-database-design.md` designed an `audit_logs` table (SEC-10/NFR-AUDIT-01); it was never implemented. Every state-changing action *is* still attributable after the fact via `updated_at`/`reviewed_by`-style columns on individual tables, but there's no unified, queryable action log. Not a blocker for this deployment scope, but flagged for the QualityReport module's eventual build-out. |

## 6. Performance

| # | Finding | Status |
|---|---|---|
| 6.1 | **N+1 in `GET /admin/users`**: `UserResource::toArray()` calls `getRoleNames()`, `getDirectPermissions()`, and `getAllPermissions()` for every user in a paginated collection; `UserRepository::paginate()` eager-loaded nothing. At the default page size (15), that's up to ~45 extra queries per request. | **Fixed**: `UserRepository::paginate()` now eager-loads `roles.permissions` and `permissions` — `roles.permissions` specifically, not just `roles`, because Spatie's `getAllPermissions()` internally walks each role's own permissions to compute the effective set and would otherwise lazy-load that nested relation regardless of the top-level `roles` eager load. Regression test creates two batches of users and asserts the query-count delta stays flat (`< 5`), not proportional to row count. |
| 6.2 | `PoPloMatrixService::review()`/`generate()` | No N+1: three queries total regardless of matrix size (`objectives->forProgram`, `outcomes->forProgram`, `matrix->gridForProgram`), then pure in-memory iteration. Confirmed by re-reading, not just assumed. |
| 6.3 | Report generation (PDF/Excel) has no row-count cap | At PAMS's realistic scale (a program's objectives/outcomes number in the tens, not thousands) this is a non-issue; flagged as a low-priority scalability note, not a blocker — see §8. |

## 7. Database Optimization

| # | Finding | Status |
|---|---|---|
| 7.1 | `program_coordinator_assignments` had `UNIQUE(program_id, user_id)` and nothing else. A composite index only serves lookups filtering on its **leftmost column first** — any query filtering by `user_id` alone (e.g. a future "programs I coordinate" listing, or `Program::hasCoordinator()` invoked the other way round) would force a full scan. | **Fixed**: new migration adds a dedicated index on `user_id`. |
| 7.2 | Every other FK relationship checked (`program_objectives.program_id`, `learning_outcomes.program_id`, `objective_outcome_matrix.*`) already has an index via its own unique constraint or FK definition. No further gaps found. |

## 8. Code Quality

- **Fixed**: `ROLE_OPTIONS` was duplicated verbatim in `CreateUserModal.tsx` and `EditUserModal.tsx` — extracted to `features/users/roleOptions.ts`. Small, but exactly the kind of drift-risk (the two forms silently diverging on the role list) worth killing on sight.
- No `console.log`/`TODO`/`FIXME` left in the frontend (`grep` came back empty).
- No hardcoded credentials anywhere in `app/`/`database/seeders/` (`grep` came back empty).
- Every backend file passes `pint --test`; every frontend file passes `oxlint` and `tsc -b` with zero errors or suppressions.

---

## 9. Before Deployment — Prioritized Checklist

**Must do (environment/config, not code — nothing here needed a code change):**
1. Set `APP_DEBUG=false` and `LOG_LEVEL` to `error`-or-higher in production `.env` — `true`/`debug` leaks stack traces.
2. Switch `DB_CONNECTION` from the local `sqlite` fallback to `mysql` per `.env.example`, against a real MySQL instance (ADR-0001 §1 target).
3. Set `SESSION_SECURE_COOKIE=true` once served over HTTPS (it's currently unset/falsy, correct only for local HTTP dev).
4. Set `FRONTEND_URL`/`SANCTUM_STATEFUL_DOMAINS`/`VITE_API_URL` to the real production origins — CORS and cookie auth both depend on these matching exactly.
5. Run `php artisan migrate --force` (includes the two new migrations from this review) and `php artisan db:seed --class=RoleSeeder --class=PermissionSeeder` (or the full seeder) against production.

**Should do soon (real gaps, not blocking this specific deployment):**
6. Implement the `audit_logs` table (§5.8) before this system is relied on for actual accreditation evidence — SEC-10/NFR-AUDIT-01 were specified for a reason.
7. Add a CI pipeline (GitHub Actions: `pest`, `pint --test`, `npm run build`, `npm run test`, `npm run lint`) — none exists yet; every check in this review was run manually and would silently regress without one.
8. Revisit `GET /api/v1/programs` (listing endpoint) — flagged in ARCH-0003 as a known gap; Program Coordinators currently need a direct link with the program ID baked in.

**Worth knowing, not urgent:**
9. No row-count cap on PDF/Excel report generation (§6.3) — fine at current scale, revisit if program sizes grow substantially.
10. The `ProgramRepositoryInterface` ISP tension (§3) — revisit once the full Program workflow module is built.
