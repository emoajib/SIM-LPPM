# SIM LPPM ITSNU - Agent Guidelines

## 1. Project Summary & Stack
Research & Community Service Management System for Institut Teknologi dan Sains Nahdlatul Ulama (ITSNU) Pekalongan.
- **Stack:** PHP 8.4, Laravel 12, Livewire v4,  Tailwind v4, Tabler + Bootstrap 5, Pest v4, Pint.
- **Key Models:** Polymorphic `Proposal` (morphs to `Research` or `CommunityService`), `User` (Spatie roles), `ProposalReviewer`.
- **Architecture:** Controller-less (mostly Livewire), Form Objects for state, Traits for composable behavior, Abstract classes for shared logic.

## 2. Core Commands
- **Lint & Format:** `vendor/bin/pint --dirty` (REQUIRED before commit)
- **Run Tests:** `php artisan test --filter=name` (Pest v4)
- **Asset Build:** `bun run build` (Builds Vite assets)
- **Queue:** `php artisan queue:listen` (Handles notifications)
- **Logs:** `php artisan pail` (Real-time log monitoring)
- **Cache:** `php artisan optimize:clear`

## 3. Code Style & PHP Guidelines
- **Modern PHP:** Use constructor property promotion, explicit return types, and parameter type hints.
- **Control Structures:** Always use curly braces `{}` even for single-line statements.
- **Early Returns:** Prefer guard clauses: `if (!$can) abort(403);` instead of nested if-blocks.
- **Naming:**
    - Classes/Enums: `PascalCase`
    - Methods/Variables: `camelCase`
    - DB Fields/Tables: `snake_case`
    - Enum Keys: `TitleCase` (e.g., `ProposalStatus::Approved`)
- **Config:** Use `config('app.name')`, NEVER use `env()` outside of config files.
- **Models:** Use `protected function casts(): array` method instead of `$casts` property.

## 4. Livewire v4 Patterns
- **Root Element:** Every component MUST have exactly one root HTML element.
- **Performance:** Use `wire:key` in loops to prevent DOM diffing issues.
- **Binding:** `wire:model.live` for real-time; `wire:model` syncs on action requests by default.
- **Form Objects:** Move complex validation/state to `Livewire\Form` classes in `app/Livewire/Forms/`.
- **Events:** Use `$this->dispatch('event-name')` (Livewire v4) not `emit()`.
- **Computed:** Use `#[Computed]` for reactive/cached data (e.g., `$this->proposals`).

## 5. Database & Authorization
- **UUIDs:** Use `HasUuids` trait for primary keys on relevant models (Proposal, etc.).
- **N+1 Prevention:** REQUIRED eager loading `with(['relation'])` for all collections.
- **Eloquent Only:** Avoid `DB::` facade; use `Model::query()`. Use relationships for joins.
- **Authorization:** Use Spatie `hasRole()`/`can()`. Use Policies for complex logic.
- **Scoping:** `Dekan` role is faculty-scoped; `Dosen` is own-proposal scoped.

## 6. Proposal Workflow & Status Transitions

### 6.1 ProposalStatus Enum (`app/Enums/ProposalStatus.php`)
| Status             | Label (ID)                  | Description                                |
| ------------------ | --------------------------- | ------------------------------------------ |
| `DRAFT`            | Draft                       | Proposal sedang disusun                    |
| `SUBMITTED`        | Diajukan                    | Menunggu persetujuan Dekan                 |
| `NEED_ASSIGNMENT`  | Perlu Persetujuan Anggota   | Anggota tim belum menerima undangan        |
| `APPROVED`         | Disetujui Dekan             | Menunggu persetujuan Kepala LPPM           |
| `WAITING_REVIEWER` | Menunggu Penugasan Reviewer | Admin LPPM perlu menugaskan reviewer       |
| `UNDER_REVIEW`     | Sedang Direview             | Reviewer sedang melakukan review           |
| `REVIEWED`         | Review Selesai              | Semua reviewer selesai, menunggu keputusan |
| `REVISION_NEEDED`  | Perlu Revisi                | Proposal dikembalikan untuk perbaikan      |
| `COMPLETED`        | Selesai                     | Proposal disetujui (terminal)              |
| `REJECTED`         | Ditolak                     | Proposal ditolak (terminal)                |

### 6.2 Complete Workflow Diagram
```
DRAFT
  ↓ (Dosen submits, all team members accepted)
SUBMITTED
  ↓ (Dekan approves)          ← NEED_ASSIGNMENT (if team member rejects)
APPROVED
  ↓ (Kepala LPPM approves)
WAITING_REVIEWER
  ↓ (Admin LPPM assigns reviewer)
UNDER_REVIEW
  ↓ (All reviewers complete)
REVIEWED
  ├── COMPLETED (Kepala LPPM approves) ← TERMINAL
  ├── REJECTED (Kepala LPPM rejects)   ← TERMINAL
  └── REVISION_NEEDED
        ↓ (Dosen revises & resubmits)
        SUBMITTED
          ↓ [Full cycle repeats]
          ↓ [If has existing reviewers: RequestReReviewAction triggered]
```

### 6.3 Team Member Flow
- All invited members must `accepted` before submission
- If any member rejects → Status changes to `NEED_ASSIGNMENT`
- Dosen can reinvite or remove rejected members

## 7. Reviewer Workflow & Management

### 7.1 ReviewStatus Enum (`app/Enums/ReviewStatus.php`)
| Status                | Label (ID)         | Description                           |
| --------------------- | ------------------ | ------------------------------------- |
| `PENDING`             | Menunggu Review    | Review belum dimulai                  |
| `IN_PROGRESS`         | Sedang Direview    | Reviewer membuka form review          |
| `COMPLETED`           | Review Selesai     | Review telah disubmit                 |
| `RE_REVIEW_REQUESTED` | Perlu Review Ulang | Proposal direvisi, perlu review ulang |

### 7.2 ProposalReviewer Model Fields
| Field            | Type         | Description                               |
| ---------------- | ------------ | ----------------------------------------- |
| `proposal_id`    | UUID         | FK to proposals                           |
| `user_id`        | UUID         | FK to users (reviewer)                    |
| `status`         | ReviewStatus | Current review status                     |
| `review_notes`   | text         | Reviewer's feedback                       |
| `recommendation` | enum         | `approved`, `rejected`, `revision_needed` |
| `round`          | int          | Review cycle number (1, 2, 3...)          |
| `assigned_at`    | datetime     | When reviewer was assigned                |
| `deadline_at`    | datetime     | Review deadline                           |
| `started_at`     | datetime     | When reviewer opened form                 |
| `completed_at`   | datetime     | When review was submitted                 |

### 7.3 Reviewer Assignment Flow
1. **Admin LPPM** assigns reviewers when status = `WAITING_REVIEWER`
2. First assignment → Status transitions to `UNDER_REVIEW`
3. Default deadline: 14 days from assignment
4. Reviewer receives `ReviewerAssigned` notification

### 7.4 Review Completion Flow
1. Reviewer opens proposal → `started_at` set, status → `IN_PROGRESS`
2. Reviewer submits review with notes + recommendation
3. Status → `COMPLETED`, `completed_at` set
4. When ALL reviewers complete → Proposal status → `REVIEWED`
5. Kepala LPPM makes final decision

### 7.5 Re-Review Workflow (After Revision)
When proposal is resubmitted after `REVISION_NEEDED`:
1. `RequestReReviewAction` is triggered
2. All existing reviewers' status → `RE_REVIEW_REQUESTED`
3. `round` is incremented (e.g., 1 → 2)
4. `review_notes` and `recommendation` are cleared
5. New deadline set (14 days)
6. Reviewers receive `ProposalRevised` notification

### 7.6 Key Reviewer Actions (`app/Livewire/Actions/`)
| Action                  | Purpose                            |
| ----------------------- | ---------------------------------- |
| `AssignReviewersAction` | Admin assigns reviewer to proposal |
| `CompleteReviewAction`  | Reviewer submits their review      |
| `RequestReReviewAction` | Triggers re-review after revision  |

### 7.7 Reviewer Dashboard Features
- **Stats:** Assigned, Completed, Pending, Re-Review counts
- **Overdue Reviews:** Past deadline, not completed
- **Due Soon:** Within 3 days of deadline
- **Re-Review Needed:** Proposals with `RE_REVIEW_REQUESTED` status

### 7.8 Reviewer Routes (`routes/web.php`)
```php
Route::middleware(['role:reviewer'])->prefix('review')->name('review.')->group(function () {
    Route::get('research', ReviewResearch::class)->name('research');
    Route::get('community-service', ReviewCommunityService::class)->name('community-service');
    Route::get('riwayat-review', ReviewHistory::class)->name('review-history');
});
```

## 8. UI & Frontend Conventions
- **Language:** Indonesian for UI labels/messages; English for code and database identifiers.
- **Components:** Check `resources/views/components/tabler/` and `flux:*` before writing custom HTML.
- **Layouts:** Use `x-slot:title`, `x-slot:pageTitle`, `x-slot:pageActions` for standard pages.
- **Tom Select:** Add `x-data="tomSelect"` to `<select>` elements for searchable dropdowns.

## 9. System Roles (database/seeders/RoleSeeder.php)
| Role          | Description       | Key Permissions                    |
| ------------- | ----------------- | ---------------------------------- |
| `superadmin`  | IT / Developers   | Full access                        |
| `admin lppm`  | Operational staff | Assign reviewers, monitor progress |
| `kepala lppm` | LPPM Director     | Initial & final approval decisions |
| `dekan`       | Faculty Deans     | First-level approval               |
| `dosen`       | Lecturers         | Create & submit proposals          |
| `reviewer`    | Expert evaluators | Review assigned proposals          |
| `rektor`      | University Rector | Strategic oversight                |

## 10. Domain Vocabulary
- **Penelitian**: Research
- **PKM**: Community Service (Pengabdian Masyarakat)
- **TKT**: Technology Readiness Level (0-9)
- **SBK**: Output-based budget standard (Satuan Biaya Keluaran)
- **Putaran/Round**: Review cycle number after revisions

## 11. IDE & Agent Configuration
- **Cursor Rules:** Follow `.cursor/rules/laravel-boost.mdc` for boost-specific patterns.
- **Copilot:** Reference `.github/copilot-instructions.md` for quickstart patterns.
- **MCP Tools:**
    - `Serena`: Use for symbol navigation and pattern search.
    - `Laravel Boost`: Use `search-docs` for version-specific Laravel/Livewire help.
    - `Tinker`: Use for executing PHP or debugging Eloquent models.

## 12. Documentation Reference
For deeper understanding of system flows and architecture, refer to these documents in `docs/`:

| Document                    | Purpose                  | When to Use                               |
| --------------------------- | ------------------------ | ----------------------------------------- |
| `01-prd.md`                 | Product Requirements     | Understanding project vision & goals      |
| `02-workflow-lengkap.md`    | Business Workflow        | High-level process understanding          |
| `03-peran-dan-wewenang.md`  | Roles & Permissions      | RACI matrix, access control               |
| `04-struktur-data.md`       | Data Structure           | Research vs PKM differences               |
| `05-transisi-status.md`     | Status Transitions       | State machine validation rules            |
| `06-master-data.md`         | Master Data              | Taxonomy, schemes, references             |
| `07-notifikasi.md`          | Notification System      | Notification triggers & templates         |
| `08-erd.md`                 | ERD Diagram              | Database structure & relations            |
| `09-flow-detail-lengkap.md` | **Detailed Flow**        | Component, Action, Route mapping per role |
| `10-flow-visual-diagram.md` | **Visual Flow Diagrams** | ASCII diagrams for each workflow stage    |

### Key Documents for Development:
- **When implementing new features:** Start with `09-flow-detail-lengkap.md` for component/action mapping
- **When debugging workflow issues:** Use `10-flow-visual-diagram.md` for visual status transitions
- **When modifying status logic:** Reference `05-transisi-status.md` for valid transitions
- **When adding notifications:** Check `07-notifikasi.md` for existing patterns

## 13. Verification Checklist
- [ ] Reviewer actions set proper timestamps (assigned_at, completed_at).
- [ ] **Conflict of Interest (CoI)** validated: Submitter/Team cannot review own proposal.
- [ ] **Mandatory Scores**: Review cannot be completed without scores in `review_scores`.
- [ ] **Atomic Transactions**: All state-changing actions wrapped in `DB::transaction`.
- [ ] **Queued Notifications**: `NotificationService` targets collections via queued notifications.

## 14. Observability & Infrastructure Mandates
- **Observability:** Use Sentry for Error Tracking and Prometheus/Grafana for performance metrics.
- **Pipeline:** Mandatory Staging environment identical to Production via Docker Compose.
- **Rollback:** Every deployment must have a corresponding Docker tag (`GITHUB_SHA`) for instant rollback.
- **Security:** Zero Trust architecture; all assignment/review actions must verify ownership.

> **"Efficiency is the goal, but Integrity is the foundation."**
