# Frontend Architecture

| | |
|---|---|
| **Document ID** | ARCH-0003 |
| **Governing documents** | [ADR-0001 §6](../adr/0001-system-architecture.md), [ARCH-0001](0001-authentication-architecture.md), [ARCH-0002](0002-po-plo-matrix-engine.md) |
| **Implementation** | `frontend/` — fully implemented, not aspirational |

## 0. Stack and non-negotiables

React 19 + TypeScript + Tailwind CSS 4, scaffolded with Vite. **The frontend communicates only through REST APIs** — every network call in this codebase goes through `services/apiClient.ts` to `/api/v1/*`; nothing talks to Filament, nothing calls Eloquent, nothing simulates data. This is enforced structurally, not just by convention: there is exactly one Axios instance in the whole app, and every `features/*/xxxService.ts` module builds on it.

## 1. Folder Structure

```
src/
├── components/
│   ├── ui/          Button, Input, Select, FormField, Modal, Table, Badge, Spinner
│   └── layout/       AppLayout (header nav + <Outlet/>)
├── pages/            Route-level components (LoginPage, DashboardPage, UsersPage, ...)
├── features/         Domain-oriented modules, one per backend module built so far
│   ├── auth/
│   ├── users/
│   ├── program-objectives/
│   ├── learning-outcomes/
│   └── matrix/
│       ├── {feature}Service.ts    One function per real backend endpoint
│       ├── hooks/                 React Query hooks wrapping the service
│       └── components/            Feature-specific UI (tables, form modals)
├── services/         Cross-cutting: apiClient.ts (axios), queryClient.ts (React Query singleton)
├── hooks/            Shared, cross-feature hooks (useAuth)
├── types/            TypeScript types mirroring backend API Resources/DTOs exactly
├── utils/            Pure helpers (cn, toApiError)
├── contexts/         AuthContext / authContextDefinition (session state)
└── routes/           AppRouter, ProtectedRoute, RequireRole
```

**Dependency direction** (ADR-0001 §6, enforced by convention — nothing automated blocks a violation, but every file in this codebase follows it): `pages → features → components/hooks/services/types/utils`. `components/` never imports from `features/`. A feature's `hooks/` and `components/` may import that feature's own service, shared `components/ui`, `hooks/`, `services/`, `types/`, `utils/` — never another feature's internals.

## 2. Components

`components/ui/*` are the only styled primitives in the app — plain Tailwind utility classes, no component library (Headless UI/Radix/MUI were deliberately not added; ADR-0001 named React + TypeScript + Tailwind, nothing else). `Modal` is hand-rolled (Escape-to-close, backdrop-click-to-close) rather than pulling in a dialog library, since the app's modal needs (confirmation-style forms) don't need more than that.

`components/layout/AppLayout.tsx` is the authenticated shell (header, role-aware nav links, logout button, `<Outlet/>`). Nav link visibility mirrors backend authorization (e.g. "Users" only renders for `admin`) — this is a UX nicety, **not** the security boundary; the boundary is always the backend Policy, and every request this app makes is independently rejected server-side if reached without permission regardless of what the UI shows.

## 3. Pages

One page per route (`pages/LoginPage.tsx`, `DashboardPage.tsx`, `UsersPage.tsx`, `ProgramObjectivesPage.tsx`, `LearningOutcomesPage.tsx`, `MatrixPage.tsx`, `NotFoundPage.tsx`). Pages compose feature components and hooks; they hold no service-calling logic of their own beyond wiring feature hooks to feature components.

**Known gap, stated plainly**: there is no `GET /api/v1/programs` (list) endpoint on the backend yet — `Program` is only reachable through nested routes (`/programs/{id}/objectives`, `.../learning-outcomes`, `.../matrix`) or the admin-only Filament panel. `ProgramObjectivesPage`, `LearningOutcomesPage`, and `MatrixPage` all take `programId` from the route URL rather than offering an in-app program picker, because that picker has no endpoint to call. Fabricating a fake picker (hardcoded/local list) would violate the "REST APIs only" rule this document opened with. Adding that listing endpoint is a small, separate backend task.

## 4. Features

Each `features/{name}/` is a vertical slice: service (API calls) + hooks (React Query wrappers) + components (forms/tables specific to that domain). Two features are fully built against real, already-implemented backend endpoints:

- **auth** — login/logout, backed by `POST /auth/login`, `POST /auth/logout`, `GET /auth/me`.
- **users** — full admin CRUD, role assignment, activate/deactivate — backed by `routes/api/v1/admin.php`. (Direct permission management, `PUT /admin/users/{id}/permissions`, has a working service method but no UI yet — role assignment covers the primary "manage users" need; the permissions modal is a natural next addition, not built now to keep scope honest about what's actually wired up.)
- **program-objectives**, **learning-outcomes**, **matrix** — full CRUD/generate/review/edit/export UI, backed by `routes/api/v1/program-objectives.php`, `learning-outcomes.php`, `matrix.php` respectively. The Matrix UI implements all four ARCH-0002 capabilities: generate (with `force`), review (grid + auto/manual/unmapped summary), manual per-cell edit, and CSV export (fetched as a blob through `apiClient`, not a bare `<a href>`, so an auth failure surfaces in-app instead of a raw browser error page).

## 5. Hooks

`hooks/useAuth.ts` is the one cross-feature hook — every other hook (`useUsers`, `useProgramObjectives`, `useMatrix`, ...) is feature-scoped and lives inside that feature's `hooks/` folder, per ADR-0001 §6's stated distinction between shared and feature-specific hooks.

## 6. Services & API Integration

`services/apiClient.ts` is the single Axios instance every feature service uses:

- `baseURL: {VITE_API_URL}/api/v1`, `withCredentials: true`, `withXSRFToken: true` — Sanctum SPA cookie authentication (ARCH-0001 §1), not a bearer token stored in JS anywhere. `withXSRFToken` is required (not axios's default) because the SPA (Vite dev server, port 5173) and the API (Laravel, port 8000) are different origins by browser same-origin rules; axios only auto-attaches the XSRF header same-origin unless told otherwise.
- `ensureCsrfCookie()` hits `/sanctum/csrf-cookie` (outside `/api/v1`) — called once before the first login attempt of a session, exactly matching the flow already verified against the live backend during this build (CSRF cookie → login → session persists across requests → protected endpoint succeeds).
- A response interceptor watches for 401s from *any* request (not just the initial session check) and clears the cached session, so a mid-session revocation (e.g. an admin deactivating the account — FR-USR-03) reflects in the UI without a manual refresh.

`utils/apiError.ts` normalizes every failure (Axios error or otherwise) into one `ApiError` shape with `.message`, `.status`, and `.errors` (field-level validation messages) — mirroring the backend's one error envelope (`ApiExceptionRenderer`) so every feature handles errors identically instead of each guessing at Axios's shape.

`types/*.ts` mirror backend API Resources field-for-field (`User`, `ProgramObjective`, `LearningOutcome`, `MatrixGrid`, ...) — verified against live responses from the running backend, not guessed from the PHP source alone.

## 7. State Management

**Decision**: [TanStack Query](https://tanstack.com/query) for all server state (anything that comes from the API), React Context for the one piece of genuinely global client state (the authenticated session). No Redux/Zustand/Jotai. ADR-0001 §6 named `contexts/` as a folder but didn't mandate a server-state library — this is a real decision, stated here rather than left implicit:

- Almost everything this app renders **is** server state (users, objectives, outcomes, matrix cells) — a REST-API-first SPA's actual state-management problem is caching/invalidating/refetching server data, not client-only UI state. React Query solves exactly that (cache keys, background refetch, mutation-triggered invalidation) with far less code than hand-rolling it in Context/Redux.
- The one piece of true global client state — "who is logged in" — is itself sourced from the API (`GET /auth/me`) and is *also* just a React Query cache entry (`AuthContext` wraps a `useQuery`/`useMutation` trio, it doesn't duplicate that state in `useState`). This is why the 401 interceptor can fix the session by writing to the query cache directly — there's only one place the "current user" value lives.
- Local component state (`useState`) still handles pure UI concerns — form field values, modal open/closed, which row is selected — exactly where it belongs, un-globalized.

## 8. Build & Verification

`npm run build` (`tsc -b && vite build`) and `npm run lint` (oxlint) both pass clean. The full auth flow (CSRF cookie → login → session persistence → protected-endpoint access) was verified against the live backend during this build, not just assumed from reading the API contract.
