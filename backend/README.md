# PAMS Backend

Laravel REST API for the Academic Program Specification and Quality Assurance Management System. Architecture is governed by [`docs/adr/0001-system-architecture.md`](../docs/adr/0001-system-architecture.md) — read that before adding anything here.

## Stack

Laravel 13 · Sanctum (SPA cookie auth) · spatie/laravel-permission (RBAC) · MySQL · Pest · Pint (PSR-12 / `laravel` preset).

## Setup

```bash
composer install
cp .env.example .env   # then set DB_* to a real MySQL instance
php artisan key:generate
php artisan migrate --seed   # seeds the 3 fixed roles: admin, qa_officer, program_coordinator
php artisan serve
```

The bundled `.env` defaults to SQLite for zero-config local runs; switch `DB_CONNECTION` to `mysql` per `.env.example` for anything matching the ADR's target environment.

## Structure

- `app/Domain/{Program,LearningOutcome,CourseMapping,Accreditation,QualityReport}/` — one folder per bounded academic domain (Models/DTOs/Services/Repositories/Policies). See `app/Domain/README.md`.
- `app/Support/` — cross-cutting base classes: `Repositories/` (generic repository contract + Eloquent base), `DTO/BaseDTO`, `Exceptions/` (domain exception hierarchy), `Http/Concerns/ApiResponses`.
- `app/Http/Controllers/Api/V1/` — thin controllers; base `Controller` wires in `ApiResponses` + `AuthorizesRequests`.
- `routes/api/v1/*.php` — one route file per module, required from `routes/api.php`.
- `app/Providers/RepositoryServiceProvider.php` — binds each domain's Repository interface to its Eloquent implementation.

No business features are implemented yet (Programs, Learning Outcomes, etc.) — this is foundation only: auth, routing, base classes, and exception handling.

## Testing

```bash
./vendor/bin/pest
./vendor/bin/pint --test
```
