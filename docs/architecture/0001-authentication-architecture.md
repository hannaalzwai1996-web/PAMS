# Authentication & Authorization Architecture

| | |
|---|---|
| **Document ID** | ARCH-0001 |
| **Governing documents** | [ADR-0001](../adr/0001-system-architecture.md), [SRS-0001 §8–9](../srs/0001-software-requirements-specification.md), [Requirements Analysis 0001](../analysis/0001-requirements-analysis.md) |
| **Implementation** | `backend/` (this design is fully implemented, not aspirational) |

## 0. Conflict flagged before implementation

"Registration" in the prompt is implemented as **admin-only user provisioning**, not public self-signup. This follows directly from prior artifacts in this project, not a new assumption:

- SRS **FR-USR-01**: "The system shall allow a user with role `admin` to create user accounts."
- Requirements Analysis **Assumption A-3**: "Program Coordinators are assigned to one or more specific programs by the Admin; they do not self-register into programs."

A public `/register` endpoint would let an anonymous caller create an account and pick their own role — which breaks BR-3/BR-5 (role-scoped authorization) at the root. If a public self-signup flow is actually wanted, that's a scope change to the SRS, not just this module, and should be raised explicitly.

## 1. Login

**Mechanism**: Sanctum SPA cookie authentication (ADR-0001 §8), not token issuance. The React app:
1. `GET /sanctum/csrf-cookie` (auto-registered by Sanctum).
2. `POST /api/v1/auth/login` with `{ email, password }`, `Origin` header present (real browser requests always send this).
3. Laravel sets an encrypted, `httpOnly`, `SameSite=Lax` session cookie; subsequent `/api/v1/*` requests are authenticated by that cookie via the `statefulApi()` middleware.

**Endpoint**: `POST /api/v1/auth/login` → `AuthController::login()` (unchanged from the foundation build). On success: session regenerated (session-fixation protection), returns `UserResource` (id, name, email, roles, is_active). On failure or `is_active = false`: `ValidationException` → 422 in the standard `{message, errors}` envelope.

## 2. Logout

`POST /api/v1/auth/logout` (protected by `auth.api`) → invalidates the session and rotates the CSRF token. Does **not** touch other devices/sessions belonging to the same user — that's a distinct concern (see §4, deactivation).

## 3. Registration (admin-only provisioning)

`POST /api/v1/admin/users` → `Admin\UserController::store()`, gated by `can:create,App\Models\User::class` → `UserPolicy::create()` → requires the `users.manage` permission (held only by `admin`, see §5).

Flow: `StoreUserRequest` (name, email unique, password via `Password::defaults()`, role — validated against the fixed `Role` enum) → `UserDTO::fromRequest()` → `UserService::register()` → `UserRepository` → `User` model. The Service hashes the password and assigns exactly one role via `syncRoles()`, enforcing SRS **NFR/FR-USR "one active role set per user."**

Also exposed, all gated the same way (`users.manage` via the matching `UserPolicy` ability):

| Endpoint | Ability | Purpose |
|---|---|---|
| `GET /api/v1/admin/users` | `viewAny` | Paginated list |
| `PATCH /api/v1/admin/users/{user}` | `update` | Profile fields only: name, email, `is_active` |
| `DELETE /api/v1/admin/users/{user}` | `delete` | Soft-delete (`User` uses `SoftDeletes` — the row, and the audit trail of e.g. who approved a report, survives) |
| `POST /api/v1/admin/users/{user}/role` | `assignRole` | Replaces the user's single role via `syncRoles()` |
| `PUT /api/v1/admin/users/{user}/permissions` | `managePermissions` | Replaces the user's *direct* permissions (independent of role) — see §5 |

Role assignment and permission management are deliberately split out of the generic `update` endpoint into their own DTOs/requests/Service methods — each is a distinct business operation with its own authorization ability, not a side effect of a generic "edit profile" call.

`UserService` enforces two self-service guards, both as `BusinessRuleException` (422), because the admin *is* authorized to act on their own record — these are business rules, not authorization failures:
- **cannot deactivate their own account** (`update` with `is_active: false`)
- **cannot delete their own account** (`destroy`)

## 4. Middleware

| Middleware / alias | Registered as | Purpose |
|---|---|---|
| `auth:sanctum` | Laravel default | Resolves the authenticated user from the session cookie (or bearer token, for future non-SPA clients) |
| `active` | `App\Http\Middleware\EnsureUserIsActive` | New. Rejects the request (403) if `auth()->user()->is_active === false`. Without this, a session opened *before* an admin deactivates the account would keep working until it expired — FR-USR-03 requires deactivation to take effect immediately |
| `auth.api` | Middleware **group** = `[auth:sanctum, active]` | The one alias every protected route uses, so "authenticated" and "not deactivated" can never be applied inconsistently |
| `role`, `permission` | Spatie's `RoleMiddleware`/`PermissionMiddleware` | Registered as aliases for completeness/Filament use; **not** used on REST routes (see §6 for why) |
| `can:{ability},{Model}` | Laravel default | The actual authorization mechanism for REST routes — resolves to a Policy method |

`EnsureUserIsActive` only checks; it doesn't revoke anything itself. Revocation happens once, at the moment an admin flips `is_active` to `false` (§3/§5), in the Service layer — not scattered across middleware.

## 5. Permissions

Spatie permissions, seeded by `PermissionSeeder`, one row per `module.action` slug, synced to roles (`Role::syncPermissions()`). Spatie registers every permission as a Laravel Gate ability automatically (`permission.register_permission_check_method = true`), so Policies can call `$user->hasPermissionTo('slug')` or, equivalently, `$user->can('slug')`.

| Permission | admin | qa_officer | program_coordinator | Consumed by |
|---|---|---|---|---|
| `users.manage` | ✅ | | | `UserPolicy` (implemented now) |
| `programs.create` / `.edit` / `.submit` | | | ✅ | Future `ProgramPolicy` |
| `programs.review` | | ✅ | | Future `ProgramPolicy` (BR-3: coordinator can never hold this) |
| `programs.view-any` | ✅ | ✅ | | Future `ProgramPolicy` |
| `learning-outcomes.manage` | | | ✅ | Future `LearningOutcomePolicy` |
| `course-mappings.manage` | | | ✅ | Future `CourseMappingPolicy` |
| `accreditation.manage` | ✅ | ✅ | | Future `AccreditationPolicy` |
| `quality-reports.create` / `.submit` | | | ✅ | Future `QualityReportPolicy` |
| `quality-reports.review` | | ✅ | | Future `QualityReportPolicy` |
| `reports.export` | | ✅ | ✅ | Future `QualityReportPolicy` |
| `dashboard.view` | ✅ | ✅ | | Filament / future dashboard endpoint |

Only `users.manage` is enforced by a route today — the rest are seeded now so the permission model exists as fixed reference data before each domain module lands, rather than being invented ad hoc per module later.

## 6. Policies

**Rule (ADR-0001 §12, immutable): authorization is enforced via Policies, never inline role-string checks in a controller.** `UserPolicy` is the first implementation of this and the template every future domain Policy follows:

```php
class UserPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermissionTo('users.manage'); }
    public function create(User $user): bool { return $user->hasPermissionTo('users.manage'); }
    public function update(User $user, User $target): bool { return $user->hasPermissionTo('users.manage'); }
    // delete, assignRole, managePermissions follow the same shape.
}
```

Why `can:` route middleware over Spatie's `role:`/`permission:` middleware for REST routes: `can:` resolves through the Policy, so the authorization *decision* always lives in one place per model (SRS §8: "Role/permission checks via route middleware (`can:...`) mapped to Policy methods"). `role:`/`permission:` middleware would check membership directly at the route layer, bypassing the Policy — two authorization paths that can drift apart. The Spatie aliases stay registered (harmless, useful for Filament's `canAccessPanel()` and similar non-REST contexts) but REST routes standardize on `can:`.

**Pattern for future domain Policies**, using rules already fixed in the Requirements Analysis so implementers don't re-derive them:

```php
class ProgramPolicy
{
    public function update(User $user, Program $program): bool
    {
        // BR-5: a coordinator may only touch programs they're assigned to.
        return $program->coordinators->contains($user);
    }

    public function approve(User $user, Program $program): bool
    {
        // BR-3: only qa_officer approves — and never the submitting coordinator,
        // even if they also happen to hold qa_officer (roles are exclusive per FR-USR-02,
        // so this second clause is defense-in-depth, not reachable today).
        return $user->hasPermissionTo('programs.review')
            && $program->submitted_by !== $user->id;
    }
}
```

## 7. Protected Routes

Three tiers, all under `/api/v1`:

| Tier | Middleware | Routes |
|---|---|---|
| Public | *(none)* | `GET /health`, `POST /auth/login` |
| Authenticated, any role | `auth.api` | `GET /auth/me`, `POST /auth/logout` |
| Authenticated + Policy-gated | `auth.api` + `can:{ability},{Model}` | `GET/POST /admin/users`, `PATCH`/`DELETE /admin/users/{user}`, `POST /admin/users/{user}/role`, `PUT /admin/users/{user}/permissions` (all require `users.manage`, i.e. `admin` only) |

Every future domain route (`/api/v1/programs/*`, `/api/v1/quality-reports/*`, ...) follows the same shape: `auth.api` for "must be a real, active session," then `can:{ability},{Model}` naming the exact Policy method for that action — never a bare role check.
