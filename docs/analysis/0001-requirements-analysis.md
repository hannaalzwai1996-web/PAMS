# Requirements Analysis: Academic Program Specification and Quality Assurance Management System (PAMS)

| | |
|---|---|
| **Prepared as** | Senior Business Analyst deliverable |
| **Based on** | [ADR-0001: System Architecture](../adr/0001-system-architecture.md) |
| **Date** | 2026-07-27 |
| **Status** | Draft for stakeholder review |

This document analyzes functional and non-functional scope for PAMS. It is constrained by ADR-0001 — every requirement below must be implementable within Clean Architecture / Laravel + React + Filament + MySQL, and must respect the three defined roles (`admin`, `qa_officer`, `program_coordinator`). No code is included.

---

## 1. Stakeholders

| Stakeholder | Type | Interest |
|---|---|---|
| System Administrator | Internal, direct user | System configuration, user/role management, data integrity, uptime |
| Quality Assurance Officer | Internal, direct user | Program specification quality, accreditation readiness, reporting accuracy |
| Program Coordinator | Internal, direct user | Efficient authoring/maintenance of their program's specification, outcomes, and course mappings |
| University Deanship / Academic Management | Internal, indirect | Institution-wide QA status visibility, accreditation outcomes |
| National Quality Assurance & Accreditation body (Libyan standards authority) | External, indirect | Compliance of program specifications and reports with national academic standards |
| Faculty / Course Instructors | Internal, indirect | Accuracy of course-to-outcome mappings that reference their courses |
| IT / Infrastructure team | Internal, operational | Hosting, backups, security patching, Sanctum/session management |
| Students (ultimate beneficiary) | External, indirect | Quality of programs governed by outcomes this system manages |

---

## 2. User Roles

Per ADR Section 8, three roles exist. Permissions below are the BA-level intent; enforcement is via Policies as mandated by the ADR.

| Role | Scope | Key Permissions |
|---|---|---|
| **System Administrator** (`admin`) | Full system, via Filament + API | Manage users/roles, manage institutional master data (departments, standards catalog), configure system settings, full read access to all programs/reports, cannot bypass QA approval workflow |
| **Quality Assurance Officer** (`qa_officer`) | Cross-program QA oversight | Review/approve/reject program specifications, review/approve quality reports, manage accreditation requirement definitions, view all programs, cannot create/delete users |
| **Program Coordinator** (`program_coordinator`) | Own assigned program(s) only | Create/edit their program specification, define learning outcomes, create course mappings, submit quality reports for review; **cannot** approve their own submissions; no access to other coordinators' programs |

---

## 3. Functional Requirements

### 3.1 Authentication & User Management
- FR-1: Users authenticate via Sanctum-backed login (email + password).
- FR-2: Admin can create, edit, deactivate, and assign roles to users.
- FR-3: System enforces one active role set per user (per Spatie permission model in ADR).

### 3.2 Program Specification Management
- FR-4: Program Coordinator can create a Program Specification (name, code, level, department, description, duration).
- FR-5: Program Coordinator can edit a Program Specification only while in `draft` status.
- FR-6: Program Coordinator can submit a Program Specification for QA review.
- FR-7: QA Officer can approve or reject a submitted Program Specification, with mandatory rejection comments.
- FR-8: System maintains version history of Program Specification changes.

### 3.3 Learning Outcomes Management
- FR-9: Program Coordinator can define Program Learning Outcomes (PLOs) linked to a Program Specification.
- FR-10: Each Learning Outcome must be classified against a Libyan national standard category (knowledge, skills, competencies — configurable by Admin/QA).
- FR-11: System prevents Program submission if zero Learning Outcomes are defined.

### 3.4 Course Mapping
- FR-12: Program Coordinator can map Courses to Learning Outcomes with a mapping strength/level (e.g., Introduced, Reinforced, Mastered).
- FR-13: System can generate a Curriculum Mapping Matrix (Courses × Learning Outcomes) for a given program.
- FR-14: System flags Learning Outcomes with no course mapping as incomplete.

### 3.5 Accreditation Requirements
- FR-15: QA Officer (or Admin) can define/maintain the catalog of Libyan accreditation requirements/standards.
- FR-16: QA Officer can link a Program Specification's evidence (outcomes, mappings, reports) to specific accreditation requirements.
- FR-17: System can produce an accreditation-readiness summary per program (requirements met vs. outstanding).

### 3.6 Quality Reports
- FR-18: Program Coordinator can create and submit periodic Quality Reports for their program.
- FR-19: QA Officer can review, comment on, approve, or reject Quality Reports.
- FR-20: System stores Quality Report history per program with status (`draft`, `submitted`, `approved`, `rejected`).
- FR-21: System can export a Quality Report / accreditation summary (PDF) for external submission.

### 3.7 Administration (Filament)
- FR-22: Admin manages departments, standards catalog, and system-level lookups via Filament — no business-rule logic embedded in Filament (per ADR Section 3).
- FR-23: Admin can view dashboards/widgets summarizing system-wide QA status (programs by status, overdue reports).

---

## 4. Non-Functional Requirements

| Category | Requirement |
|---|---|
| **Performance** | API responses for standard CRUD < 500ms under normal load; report/matrix generation < 3s for a program with up to 200 mapped outcomes/courses |
| **Scalability** | Modular domain structure (per ADR) must allow adding new domains (e.g., new accreditation frameworks) without touching unrelated modules |
| **Security** | Sanctum auth, role-based Policies on every mutating endpoint, input validation via Form Requests, no raw SQL (ADR Section 9) |
| **Availability** | Target 99.5% uptime during academic term periods; scheduled maintenance windows communicated in advance |
| **Usability** | React/Tailwind UI must support the non-technical Program Coordinator persona with clear workflow status indicators (draft/submitted/approved/rejected) |
| **Localization** | UI and generated reports must support Arabic (primary, RTL) and English, given Libyan institutional context |
| **Auditability** | All approve/reject/status-change actions logged with actor, timestamp, and reason (supports accreditation evidence trail) |
| **Maintainability** | Clean Architecture layering (ADR Section 2) enforced so business rules remain in Services, testable in isolation |
| **Data Integrity** | Foreign-key constraints enforced at DB level per ADR Section 4; soft deletes on audit-relevant domain entities |
| **Compliance** | Data model and workflows must be traceable to Libyan national academic quality assurance standards |

---

## 5. Business Rules

- BR-1: A Program Specification cannot be submitted for QA review unless it has at least one Learning Outcome (FR-11).
- BR-2: A Learning Outcome cannot be marked "complete" unless mapped to at least one Course (FR-14).
- BR-3: Only a QA Officer (not the submitting Program Coordinator, not Admin acting as a shortcut) may approve or reject a Program Specification or Quality Report — enforced via Policy, not UI hiding alone.
- BR-4: A rejected Program Specification or Quality Report returns to `draft` status and requires a stated rejection reason before the Coordinator can resubmit.
- BR-5: A Program Coordinator may only view/edit programs to which they are explicitly assigned.
- BR-6: Accreditation-readiness status for a program can only be marked "Ready" when all linked accreditation requirements have satisfying evidence (FR-17).
- BR-7: Once a Program Specification is `approved`, edits require creating a new version (FR-8) rather than mutating the approved record, preserving audit history.
- BR-8: Quality Reports are periodic (e.g., per academic term) — the system must prevent duplicate open reports for the same program/period.

---

## 6. System Constraints

- SC-1: Technology stack is fixed by ADR-0001: Laravel REST API (backend), React + TypeScript + Tailwind (frontend), FilamentPHP (admin-only), MySQL.
- SC-2: Filament may never contain business logic (ADR Section 3, immutable) — all administrative workflows requiring rules must call Services.
- SC-3: Only three roles exist at this stage (`admin`, `qa_officer`, `program_coordinator`); any new role requires an ADR amendment.
- SC-4: All API traffic is versioned under `/api/v1` (ADR Section 7).
- SC-5: Primary keys are UUID for domain tables (ADR Section 4) — external accreditation exports must not assume sequential IDs.
- SC-6: System assumed to operate within a single institution's infrastructure (no explicit multi-tenancy defined in ADR) — see Assumption A-2.
- SC-7: Development must follow the folder structures, naming conventions, and layering defined in ADR Sections 2, 3, 5, 6 without exception.

---

## 7. Assumptions

- A-1: "Libyan academic standards" refers to a definable, catalog-able set of requirements (national QA body standards) that can be modeled as structured data (Section 3.5) rather than unstructured documents only.
- A-2: The system serves a single academic institution per deployment (not a multi-university SaaS); multi-tenancy is out of scope unless a future ADR revises this.
- A-3: Program Coordinators are assigned to one or more specific programs by the Admin; they do not self-register into programs.
- A-4: A "Quality Report" cadence (e.g., per semester/year) will be confirmed with the QA department; assumed periodic for this analysis.
- A-5: PDF export (FR-21) uses server-side rendering from the Laravel backend, not a separate service.
- A-6: Arabic RTL support is required at UI level; this doesn't change ADR's technology choices, only frontend implementation detail.
- A-7: No external LMS/SIS integration is in scope for this phase; course data is managed within PAMS itself.

---

## 8. Use Cases

| ID | Use Case | Primary Actor | Preconditions | Outcome |
|---|---|---|---|---|
| UC-1 | Create Program Specification | Program Coordinator | Coordinator assigned to a program | Draft Program Specification created |
| UC-2 | Define Learning Outcomes | Program Coordinator | Program Specification exists | PLOs attached to program |
| UC-3 | Map Courses to Learning Outcomes | Program Coordinator | PLOs defined | Curriculum mapping matrix populated |
| UC-4 | Submit Program Specification for Review | Program Coordinator | ≥1 Learning Outcome exists (BR-1) | Status → `submitted` |
| UC-5 | Review & Approve/Reject Program Specification | QA Officer | Program in `submitted` status | Status → `approved` or `draft` (with reason) |
| UC-6 | Define Accreditation Requirements Catalog | QA Officer / Admin | — | Requirement catalog updated |
| UC-7 | Link Program Evidence to Accreditation Requirements | QA Officer | Program approved | Accreditation-readiness computed |
| UC-8 | Create & Submit Quality Report | Program Coordinator | Program approved, no open report for period (BR-8) | Report in `submitted` status |
| UC-9 | Review & Approve/Reject Quality Report | QA Officer | Report `submitted` | Report `approved`/`draft` |
| UC-10 | Manage Users & Roles | System Administrator | Admin authenticated | User created/updated with role |
| UC-11 | View System-Wide QA Dashboard | Admin / QA Officer | Authenticated | Dashboard/widgets rendered (Filament for Admin) |
| UC-12 | Export Quality Report / Accreditation Summary | QA Officer / Coordinator | Report approved | PDF generated |

**Detailed example — UC-5: Review & Approve/Reject Program Specification**

- **Actor**: QA Officer
- **Precondition**: Program Specification status = `submitted`.
- **Main flow**:
  1. QA Officer opens submitted Program Specification.
  2. System displays specification, learning outcomes, and course mapping completeness (BR-2).
  3. QA Officer approves → status becomes `approved`, version locked (BR-7).
  4. OR QA Officer rejects → must enter reason (BR-4) → status returns to `draft`, Coordinator notified.
- **Postcondition**: Status transition recorded in audit log (NFR: Auditability).
- **Alternate flow**: If Learning Outcomes exist but have unmapped items (BR-2), QA Officer is warned but may still proceed per institutional policy (configurable business rule — confirm with stakeholder).

---

## 9. User Stories

### Program Coordinator
- US-1: As a Program Coordinator, I want to create a Program Specification, so that I can begin documenting my program per national standards.
- US-2: As a Program Coordinator, I want to define Learning Outcomes for my program, so that I can align them with accreditation requirements.
- US-3: As a Program Coordinator, I want to map courses to learning outcomes, so that I can demonstrate curriculum coverage.
- US-4: As a Program Coordinator, I want to submit my program for QA review, so that it can move toward approval.
- US-5: As a Program Coordinator, I want to see why my submission was rejected, so that I can correct it and resubmit.
- US-6: As a Program Coordinator, I want to submit periodic Quality Reports, so that my program maintains accreditation compliance.

### Quality Assurance Officer
- US-7: As a QA Officer, I want to review submitted Program Specifications, so that I can ensure they meet Libyan academic standards before approval.
- US-8: As a QA Officer, I want to see which Learning Outcomes lack course mappings, so that I can flag incomplete submissions.
- US-9: As a QA Officer, I want to maintain the accreditation requirements catalog, so that all programs are evaluated against current standards.
- US-10: As a QA Officer, I want to approve or reject Quality Reports with comments, so that Coordinators receive actionable feedback.
- US-11: As a QA Officer, I want an accreditation-readiness summary per program, so that I can prioritize institutional accreditation efforts.

### System Administrator
- US-12: As a System Administrator, I want to create user accounts and assign roles, so that staff have appropriate system access.
- US-13: As a System Administrator, I want to manage departments and standards master data via the admin panel, so that lookups stay accurate without touching business logic.
- US-14: As a System Administrator, I want a dashboard of system-wide QA status, so that I can report to university leadership.

---

## 10. Acceptance Criteria

**US-1 — Create Program Specification**
- Given I am logged in as a Program Coordinator assigned to a program,
  When I submit the "Create Program Specification" form with required fields (name, code, level, department, description),
  Then a new Program Specification is created with status `draft`.
- Given required fields are missing, When I submit, Then validation errors are returned per field (ADR Section 7 error format) and no record is created.

**US-4 — Submit Program Specification for Review**
- Given a Program Specification in `draft` status with zero Learning Outcomes,
  When I attempt to submit it,
  Then the system rejects the action with a message referencing BR-1, and status remains `draft`.
- Given a Program Specification in `draft` status with ≥1 Learning Outcome,
  When I submit it,
  Then status changes to `submitted` and the QA Officer queue reflects the new item.

**US-7 / UC-5 — QA Review**
- Given a Program Specification with status `submitted`,
  When a QA Officer approves it,
  Then status becomes `approved`, the record is versioned/locked (BR-7), and the Coordinator is notified.
- Given a QA Officer rejects it without entering a reason,
  When they submit the rejection,
  Then the system blocks the action until a reason is provided (BR-4).
- Given a user with role `program_coordinator` attempts to approve their own submission,
  When the approval action is invoked,
  Then the system returns a 403 Forbidden (BR-3, enforced via Policy).

**US-8 — Incomplete Mapping Flag**
- Given a program with a Learning Outcome that has zero course mappings,
  When the QA Officer views the program's readiness view,
  Then that Learning Outcome is visibly flagged as "incomplete" (BR-2).

**US-10 — Quality Report Review**
- Given a Quality Report with status `submitted`,
  When the QA Officer approves it,
  Then status becomes `approved` and it is included in the program's compliance history.
- Given a program already has an open (`draft`/`submitted`) Quality Report for the current period,
  When a Coordinator tries to create another for the same period,
  Then the system blocks creation (BR-8).

**US-12 — User & Role Management**
- Given I am logged in as System Administrator,
  When I create a user and assign the `qa_officer` role,
  Then that user can authenticate and access QA-scoped endpoints/UI, and cannot access endpoints restricted to `admin` (e.g., user management).

**US-14 — Admin Dashboard**
- Given I am logged in as Admin and open the Filament panel,
  When the dashboard loads,
  Then widgets display counts of programs by status (draft/submitted/approved) sourced via read-only Repository/Service calls, with no business logic embedded in the Filament widget itself (ADR Section 3).

---

## Open Items for Stakeholder Confirmation

1. Exact structure/source of the "Libyan academic standards" catalog (A-1) — needs a real document or authority reference to model FR-15/16 precisely.
2. Quality Report cadence (A-4) — semester, annual, or accreditation-cycle-driven?
3. Whether Admin can override a QA rejection/approval in exceptional cases (currently disallowed per BR-3 — confirm this is intended).
4. Multi-institution/multi-tenancy need (A-2) — confirm out of scope.
