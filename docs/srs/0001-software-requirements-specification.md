# Software Requirements Specification

## Academic Program Specification and Quality Assurance Management System (PAMS)

Prepared in accordance with **IEEE Std 830** SRS conventions.

| | |
|---|---|
| **Document ID** | SRS-0001 |
| **Version** | 1.0 |
| **Status** | Draft for stakeholder review |
| **Date** | 2026-07-27 |
| **Governing documents** | [ADR-0001: System Architecture](../adr/0001-system-architecture.md), [Requirements Analysis 0001](../analysis/0001-requirements-analysis.md) |

This SRS **must not conflict** with ADR-0001. Where a requirement below implies a technology, pattern, or structure, it defers to ADR-0001 as authoritative. Any future requirement that would conflict with the ADR must be flagged and resolved before implementation, per project standing instruction.

---

## Table of Contents

1. Introduction
2. Purpose
3. Scope
4. System Overview
5. User Classes and Characteristics
6. Functional Requirements
7. Non-Functional Requirements
8. External Interface Requirements
9. Security Requirements
10. Database Requirements
11. Use Cases
12. Constraints
Appendix A — Definitions, Acronyms, Abbreviations
Appendix B — Requirements Traceability Summary
Appendix C — References

---

## 1. Introduction

### 1.1 Overview
This Software Requirements Specification (SRS) defines the functional and non-functional requirements for the **Academic Program Specification and Quality Assurance Management System (PAMS)** — a system for Libyan academic institutions to manage program specifications, learning outcomes, course mappings, accreditation requirements, and quality reports.

### 1.2 Intended Audience
- Development team (backend/frontend engineers) implementing against ADR-0001
- QA/testing team deriving test cases
- Project stakeholders (System Administrator, QA Officer, Program Coordinator representatives) validating scope
- Future maintainers requiring a stable requirements baseline

### 1.3 Document Conventions
- Requirement IDs are prefixed by category and module, e.g. `FR-PROG-01`, `NFR-PERF-01`, `SEC-03`, `DBR-04`.
- "Shall" denotes a mandatory requirement; "should" denotes a recommended but non-binding requirement.
- Role identifiers match ADR-0001 Section 8: `admin`, `qa_officer`, `program_coordinator`.

### 1.4 References
See Appendix C.

---

## 2. Purpose

The purpose of PAMS is to provide Libyan academic institutions with a centralized, auditable system to:

- Author and version-control Program Specifications against national academic standards.
- Define and track Program Learning Outcomes (PLOs) and their mapping to courses.
- Manage a catalog of accreditation requirements and evidence linkage per program.
- Manage the lifecycle of periodic Quality Reports (draft → submitted → approved/rejected).
- Give institutional leadership visibility into program-level and institution-wide QA/accreditation status.

The purpose of **this document** is to define what the system shall do (and shall not do) precisely enough to design, build, test, and accept it, while remaining structurally consistent with ADR-0001.

---

## 3. Scope

### 3.1 In Scope
- Web-based application: React/TypeScript SPA (primary users) + FilamentPHP internal admin panel (System Administrator).
- Laravel REST API backend, MySQL persistence, Sanctum authentication.
- Program Specification lifecycle management (create, edit, submit, review, approve/reject, version).
- Learning Outcomes definition and classification.
- Course-to-Outcome mapping and curriculum mapping matrix generation.
- Accreditation requirements catalog and evidence linkage.
- Quality Report authoring, submission, and review lifecycle.
- Role-based access control for three defined roles.
- PDF export of reports/summaries for external submission.
- Bilingual UI support (Arabic RTL primary, English secondary).

### 3.2 Out of Scope (this phase)
- Multi-institution / multi-tenant deployment (single institution per deployment — see ADR-0001 §1, Assumption A-2 in Requirements Analysis).
- Integration with external LMS/Student Information Systems.
- Public-facing (unauthenticated) program catalog or student self-service.
- Mobile native applications (responsive web only).
- Roles beyond the three defined; any addition requires an ADR amendment.

---

## 4. System Overview

PAMS follows the Clean Architecture defined in ADR-0001:

- **Presentation**: React + TypeScript + Tailwind CSS SPA for QA Officers and Program Coordinators; FilamentPHP admin panel for System Administrators.
- **API**: Laravel REST API, versioned at `/api/v1`, Controllers → Form Requests → Services → Repositories → Eloquent Models.
- **Business logic**: Centralized in the Service layer; Filament and Controllers both call the same Services — business rules exist in exactly one place (ADR-0001 §3, immutable).
- **Persistence**: MySQL, UUID primary keys on domain tables, snake_case naming (ADR-0001 §4).
- **Auth**: Laravel Sanctum + role/permission model (ADR-0001 §8).

This SRS's functional requirements map directly onto the ADR's five domain modules: **Program**, **LearningOutcome**, **CourseMapping**, **Accreditation**, **QualityReport**, plus a cross-cutting **UserManagement** module.

---

## 5. User Classes and Characteristics

| User Class | Technical Proficiency | Frequency of Use | Primary Interface | Notes |
|---|---|---|---|---|
| **System Administrator** (`admin`) | High | Occasional (setup/maintenance-driven) | Filament admin panel | Manages users, roles, master data; no business-rule bypass |
| **Quality Assurance Officer** (`qa_officer`) | Medium | Frequent (review cycles) | React SPA | Cross-program oversight, approval authority |
| **Program Coordinator** (`program_coordinator`) | Low–Medium (academic staff, not necessarily technical) | Frequent (authoring/editing) | React SPA | Scoped to assigned program(s) only; UI must be low-friction |

Accessibility note: given the Program Coordinator persona may include non-technical academic staff, the UI (NFR-USE-01, §7) must minimize training burden — clear status labels (Draft/Submitted/Approved/Rejected), inline validation, no jargon.

---

## 6. Functional Requirements

Each requirement is independently testable and traces to the Requirements Analysis document's FR/BR/UC IDs where applicable.

### 6.1 User & Access Management (Module: UserManagement)

| ID | Requirement |
|---|---|
| FR-USR-01 | The system shall allow a user with role `admin` to create user accounts with a unique email and initial password. |
| FR-USR-02 | The system shall allow `admin` to assign exactly one of the three defined roles to a user. |
| FR-USR-03 | The system shall allow `admin` to deactivate a user account, immediately revoking active sessions/tokens. |
| FR-USR-04 | The system shall authenticate users via Laravel Sanctum using email and password. |
| FR-USR-05 | The system shall restrict Filament panel access to users with role `admin` only. |

### 6.2 Program Specification (Module: Program)

| ID | Requirement |
|---|---|
| FR-PROG-01 | The system shall allow `program_coordinator` to create a Program Specification with fields: name, code, level, department, description, duration. |
| FR-PROG-02 | The system shall allow editing of a Program Specification only while its status is `draft`. |
| FR-PROG-03 | The system shall allow `program_coordinator` to submit a Program Specification for review, transitioning status from `draft` to `submitted`. |
| FR-PROG-04 | The system shall reject a submission (FR-PROG-03) if the Program Specification has zero associated Learning Outcomes, returning a descriptive validation error. |
| FR-PROG-05 | The system shall allow `qa_officer` to approve a `submitted` Program Specification, transitioning status to `approved`. |
| FR-PROG-06 | The system shall allow `qa_officer` to reject a `submitted` Program Specification, requiring a non-empty rejection reason, transitioning status to `draft`. |
| FR-PROG-07 | The system shall prevent a user with role `program_coordinator` from approving or rejecting any Program Specification, including their own. |
| FR-PROG-08 | The system shall retain a version history record each time an `approved` Program Specification is subsequently modified. |
| FR-PROG-09 | The system shall restrict a `program_coordinator` to viewing/editing only Program Specifications to which they are assigned. |

### 6.3 Learning Outcomes (Module: LearningOutcome)

| ID | Requirement |
|---|---|
| FR-LO-01 | The system shall allow `program_coordinator` to create Program Learning Outcomes (PLOs) linked to a Program Specification. |
| FR-LO-02 | The system shall require each Learning Outcome to be classified under a standard category (e.g., knowledge, skill, competency) drawn from a configurable catalog. |
| FR-LO-03 | The system shall mark a Learning Outcome as "incomplete" if it has no associated Course Mapping. |

### 6.4 Course Mapping (Module: CourseMapping)

| ID | Requirement |
|---|---|
| FR-CM-01 | The system shall allow `program_coordinator` to map a Course to a Learning Outcome with a specified mapping level (e.g., Introduced, Reinforced, Mastered). |
| FR-CM-02 | The system shall generate a Curriculum Mapping Matrix (Courses × Learning Outcomes) for a given Program Specification, viewable by `program_coordinator` (own program) and `qa_officer` (any program). |
| FR-CM-03 | The system shall prevent duplicate mapping entries for the same Course–Learning Outcome pair. |

### 6.5 Accreditation (Module: Accreditation)

| ID | Requirement |
|---|---|
| FR-ACC-01 | The system shall allow `qa_officer` and `admin` to maintain a catalog of accreditation requirements (per Libyan academic standards). |
| FR-ACC-02 | The system shall allow `qa_officer` to link Program Specification evidence (outcomes, mappings, reports) to one or more accreditation requirements. |
| FR-ACC-03 | The system shall compute and display an accreditation-readiness status per program (e.g., percentage of requirements with satisfying evidence). |
| FR-ACC-04 | The system shall not allow accreditation-readiness to be marked "Ready" while any linked requirement lacks evidence. |

### 6.6 Quality Reports (Module: QualityReport)

| ID | Requirement |
|---|---|
| FR-QR-01 | The system shall allow `program_coordinator` to create a Quality Report for an `approved` Program Specification. |
| FR-QR-02 | The system shall prevent creation of a new Quality Report for a program/period combination while an open (`draft` or `submitted`) report already exists for that period. |
| FR-QR-03 | The system shall allow `program_coordinator` to submit a Quality Report, transitioning status from `draft` to `submitted`. |
| FR-QR-04 | The system shall allow `qa_officer` to approve or reject a `submitted` Quality Report, with rejection requiring a stated reason. |
| FR-QR-05 | The system shall maintain a chronological history of Quality Reports per program. |
| FR-QR-06 | The system shall generate a PDF export of an `approved` Quality Report or accreditation summary. |

### 6.7 Administration Dashboard (Module: Admin/Filament)

| ID | Requirement |
|---|---|
| FR-ADM-01 | The system shall provide `admin` a Filament dashboard widget summarizing program counts by status (draft/submitted/approved). |
| FR-ADM-02 | The system shall ensure all Filament Resources/Pages/Widgets/Actions invoke existing Services/Repositories for any rule-bearing operation, per ADR-0001 §3 (immutable — Filament contains no business logic). |

---

## 7. Non-Functional Requirements

| ID | Category | Requirement |
|---|---|---|
| NFR-PERF-01 | Performance | Standard CRUD API responses shall complete in < 500ms under normal load (≤ 100 concurrent users). |
| NFR-PERF-02 | Performance | Curriculum Mapping Matrix / accreditation-readiness generation shall complete in < 3s for a program with up to 200 outcome–course mappings. |
| NFR-SCAL-01 | Scalability | The modular domain structure (ADR-0001 §2.3) shall allow new domains/modules to be added without modifying unrelated modules. |
| NFR-AVAIL-01 | Availability | The system shall target 99.5% uptime during active academic term periods, excluding scheduled maintenance. |
| NFR-USE-01 | Usability | The UI shall present workflow status (Draft/Submitted/Approved/Rejected) using consistent, unambiguous visual indicators across all modules. |
| NFR-USE-02 | Usability | Form validation errors shall be displayed inline, field-adjacent, in the user's selected language. |
| NFR-L10N-01 | Localization | The UI and generated PDF reports shall support Arabic (RTL) and English. |
| NFR-AUDIT-01 | Auditability | Every status transition (submit/approve/reject) shall be logged with actor ID, timestamp, and reason (where applicable). |
| NFR-MAINT-01 | Maintainability | Business rules shall reside exclusively in the Service layer, unit-testable via mocked Repository interfaces (ADR-0001 §2.4, §12). |
| NFR-REL-01 | Reliability | Data integrity shall be enforced via database-level foreign key constraints (ADR-0001 §4), not application logic alone. |
| NFR-PORT-01 | Portability | The frontend shall run on evergreen versions of Chrome, Firefox, Safari, and Edge (last 2 major versions). |

Security-specific non-functional requirements are detailed separately in Section 9.

---

## 8. External Interface Requirements

### 8.1 User Interfaces
- **React SPA**: primary interface for `qa_officer` and `program_coordinator`, responsive (desktop-first, tablet-usable), Tailwind CSS design system, bilingual (Arabic RTL / English).
- **Filament Admin Panel**: server-rendered interface for `admin`, standard Filament UI conventions, English by default (institutional back-office use).

### 8.2 Hardware Interfaces
None. PAMS is a standard web application with no direct hardware dependency; deployment targets standard server infrastructure (cloud VM or on-premises institutional server).

### 8.3 Software Interfaces
| Interface | Purpose |
|---|---|
| MySQL (≥ 8.0) | System of record for all persistent domain data (ADR-0001 §4) |
| Laravel Sanctum | Session/token-based authentication between SPA and API |
| SMTP mail service | Notifications (e.g., submission rejected, review pending) — provider-agnostic, configured per deployment |
| PDF generation library (server-side, Laravel-integrated) | FR-QR-06 report/summary export |

### 8.4 Communication Interfaces
- All client–server communication over **HTTPS**.
- API is **RESTful JSON**, versioned at `/api/v1` (ADR-0001 §7).
- Response envelope: `{ "data": ..., "meta": ..., "links": ... }`; errors: `{ "message": ..., "errors": {...} }` with standard HTTP status codes.
- Filament panel communicates with the same backend in-process (no separate API hop), per ADR-0001 §1.3.

---

## 9. Security Requirements

| ID | Requirement |
|---|---|
| SEC-01 | The system shall authenticate all API requests (except login) via Sanctum; unauthenticated requests to protected endpoints shall return HTTP 401. |
| SEC-02 | The system shall authorize every mutating action via a Policy tied to the acting user's role, returning HTTP 403 on denial (ADR-0001 §8–9). |
| SEC-03 | The system shall validate all input server-side via Form Requests before it reaches the Service layer; client-side validation alone shall not be relied upon. |
| SEC-04 | The system shall use parameterized queries exclusively (Eloquent/Query Builder) — no raw, string-concatenated SQL, preventing SQL injection. |
| SEC-05 | The system shall escape/sanitize all user-supplied content rendered in the frontend to prevent XSS; `dangerouslySetInnerHTML`-equivalent rendering is prohibited without explicit sanitization. |
| SEC-06 | The system shall validate uploaded files (e.g., report attachments) against an allowlist of MIME types/extensions and enforce a maximum file size. |
| SEC-07 | The system shall store uploaded files outside the public webroot or serve them via signed, time-limited URLs. |
| SEC-08 | The system shall hash passwords using bcrypt or Argon2 (Laravel default) and never store or log plaintext passwords. |
| SEC-09 | The system shall rate-limit authentication attempts to mitigate brute-force attacks. |
| SEC-10 | The system shall log all approval/rejection/status-change actions with actor identity and timestamp for audit purposes (NFR-AUDIT-01). |
| SEC-11 | The system shall enforce role separation such that a `program_coordinator` cannot self-approve their own submissions (FR-PROG-07), verified at the Policy layer, not solely hidden in the UI. |
| SEC-12 | The Filament admin panel shall be reachable only by authenticated users with role `admin`; no anonymous or lower-privileged access shall be permitted. |

---

## 10. Database Requirements

Per ADR-0001 §4, the following requirements govern the data layer.

| ID | Requirement |
|---|---|
| DBR-01 | All domain tables shall use UUID (ULID) primary keys; lookup/pivot tables without external-reference needs may use auto-increment integer keys. |
| DBR-02 | Table names shall be plural snake_case (e.g., `programs`, `learning_outcomes`, `course_mappings`, `quality_reports`, `accreditation_requirements`). |
| DBR-03 | Column names shall be snake_case; boolean columns shall be prefixed `is_`/`has_`. |
| DBR-04 | Foreign key columns shall follow `{singular_table}_id` naming and declare explicit `ON DELETE` behavior (`cascade` or `restrict`) per relationship semantics. |
| DBR-05 | All tables shall include `created_at`/`updated_at`; domain entities requiring audit history (`programs`, `quality_reports`, `accreditation_requirements`) shall additionally include `deleted_at` (soft deletes). |
| DBR-06 | Many-to-many relationships (e.g., Course ↔ Learning Outcome) shall use a dedicated pivot table with its own metadata (mapping level) where the relationship itself carries data. |
| DBR-07 | The schema shall support versioning of approved Program Specifications (FR-PROG-08) without loss of prior approved data. |
| DBR-08 | Referential integrity shall be enforced via foreign key constraints at the database level, not solely in application code (NFR-REL-01). |

### 10.1 Core Entities (indicative, not exhaustive)
`users`, `roles`/`permissions` (Spatie pivot tables), `departments`, `programs`, `program_versions`, `learning_outcomes`, `courses`, `course_learning_outcome` (pivot), `accreditation_requirements`, `program_accreditation_evidence`, `quality_reports`, `audit_logs`.

---

## 11. Use Cases

Use cases below are consistent with, and traceable to, the Requirements Analysis document (Section 8, UC-1…UC-12). Two representative use cases are fully specified below in IEEE-style template; the remainder are summarized in the table.

### 11.1 Use Case Summary Table

| ID | Name | Primary Actor | Trigger |
|---|---|---|---|
| UC-1 | Create Program Specification | Program Coordinator | Coordinator begins new program documentation |
| UC-2 | Define Learning Outcomes | Program Coordinator | Program Specification exists |
| UC-3 | Map Courses to Learning Outcomes | Program Coordinator | Learning Outcomes exist |
| UC-4 | Submit Program Specification for Review | Program Coordinator | Coordinator completes drafting |
| UC-5 | Review & Approve/Reject Program Specification | QA Officer | Program status = `submitted` |
| UC-6 | Define Accreditation Requirements Catalog | QA Officer / Admin | New standard published |
| UC-7 | Link Program Evidence to Accreditation Requirements | QA Officer | Program approved |
| UC-8 | Create & Submit Quality Report | Program Coordinator | Reporting period opens |
| UC-9 | Review & Approve/Reject Quality Report | QA Officer | Report status = `submitted` |
| UC-10 | Manage Users & Roles | System Administrator | Staffing change |
| UC-11 | View System-Wide QA Dashboard | Admin / QA Officer | Login to dashboard |
| UC-12 | Export Quality Report / Accreditation Summary | QA Officer / Coordinator | Report approved |

### 11.2 UC-4 — Submit Program Specification for Review (fully specified)

- **Actor**: Program Coordinator
- **Preconditions**: Program Specification exists in status `draft`; Coordinator is assigned to the program.
- **Postconditions (success)**: Status = `submitted`; QA Officer queue updated.
- **Main Flow**:
  1. Coordinator opens their Program Specification.
  2. System displays completeness indicators (Learning Outcomes count, mapping completeness).
  3. Coordinator selects "Submit for Review."
  4. System validates ≥1 Learning Outcome exists (FR-PROG-04).
  5. System transitions status to `submitted` and records timestamp/actor (SEC-10).
- **Alternate Flow (A1 — validation failure)**: At step 4, if zero Learning Outcomes exist, system displays an error and remains in `draft`.
- **Exception Flow (E1)**: Coordinator not assigned to program → system returns 403 (FR-PROG-09).

### 11.3 UC-9 — Review & Approve/Reject Quality Report (fully specified)

- **Actor**: QA Officer
- **Preconditions**: Quality Report status = `submitted`.
- **Postconditions (success — approve)**: Status = `approved`; included in program compliance history (FR-QR-05).
- **Postconditions (success — reject)**: Status = `draft`; rejection reason stored; Coordinator notified.
- **Main Flow**:
  1. QA Officer opens submitted Quality Report.
  2. System displays report content and linked program context.
  3. QA Officer selects Approve or Reject.
  4. If Reject, system requires a non-empty reason (FR-QR-04).
  5. System updates status and logs the action (SEC-10).
- **Exception Flow (E1)**: Actor role is `program_coordinator` → system denies with 403 (SEC-02, SEC-11).

---

## 12. Constraints

### 12.1 Technology Constraints (fixed by ADR-0001, immutable per ADR §12)
- Backend: Laravel REST API using Repository, Service, DTO patterns, Dependency Injection, Form Requests, API Resources.
- Frontend: React + TypeScript + Tailwind CSS.
- Admin: FilamentPHP, admin-only, zero business logic.
- Database: MySQL, UUID primary keys, snake_case naming.
- Auth: Laravel Sanctum + role/permission model (three fixed roles).
- API versioned at `/api/v1`.

### 12.2 Regulatory/Compliance Constraints
- Program Specifications, Learning Outcomes, and Quality Reports must be structured to align with Libyan national academic quality assurance standards (exact catalog to be confirmed — see Requirements Analysis, Open Item 1).

### 12.3 Organizational Constraints
- Single-institution deployment assumption (no multi-tenancy) unless a future ADR revises scope.
- Exactly three user roles; new roles require an ADR amendment before implementation.

### 12.4 Design Constraints
- Filament must never contain business logic; Controllers must never bypass the Service layer (ADR-0001 §12, immutable).
- All naming conventions in ADR-0001 §5 are fixed and must be used verbatim across code, DB, and API.

### 12.5 Dependencies/Assumptions carried from Requirements Analysis
- Program Coordinators are pre-assigned to programs by Admin (not self-service).
- Quality Report cadence to be confirmed with QA department (assumed periodic/term-based pending confirmation).

---

## Appendix A — Definitions, Acronyms, Abbreviations

| Term | Definition |
|---|---|
| PAMS | Academic Program Specification and Quality Assurance Management System |
| PLO | Program Learning Outcome |
| QA | Quality Assurance |
| ADR | Architecture Decision Record |
| SRS | Software Requirements Specification |
| DTO | Data Transfer Object |
| RBAC | Role-Based Access Control |
| RTL | Right-to-Left (text direction, Arabic) |

## Appendix B — Requirements Traceability Summary

| Business Rule (Analysis doc) | Realized by |
|---|---|
| BR-1 (≥1 outcome to submit) | FR-PROG-04 |
| BR-2 (outcome needs mapping) | FR-LO-03 |
| BR-3 (QA-only approval) | FR-PROG-07, SEC-11 |
| BR-4 (rejection reason required) | FR-PROG-06, FR-QR-04 |
| BR-5 (coordinator scoped to own program) | FR-PROG-09 |
| BR-6 (readiness requires full evidence) | FR-ACC-04 |
| BR-7 (approved = new version on edit) | FR-PROG-08, DBR-07 |
| BR-8 (no duplicate open report per period) | FR-QR-02 |

## Appendix C — References

1. ADR-0001: System Architecture — `docs/adr/0001-system-architecture.md`
2. Requirements Analysis 0001 — `docs/analysis/0001-requirements-analysis.md`
3. IEEE Std 830-1998, *IEEE Recommended Practice for Software Requirements Specifications* (structural reference for this document's conventions)
