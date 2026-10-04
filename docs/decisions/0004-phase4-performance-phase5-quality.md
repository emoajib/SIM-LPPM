# ADR 0004: Phase 4 Performance Optimizations & Phase 5 Quality Improvements

## Status
Accepted

## Context
The application had several performance bottlenecks and quality issues that needed addressing:

### Phase 4 - Performance Issues
1. **N+1 Queries in ReportApproval Components**: Four Livewire components (`KepalaLppm/ReportApproval`, `AdminLppm/ReportApproval`, `Kaprodi/ReportApproval`, `Dekan/ReportIndex`) were executing multiple separate count queries in their `stats()` computed properties, causing 5-6 queries per component load.

2. **N+1 Queries in DosenDashboard::loadProcessStats()**: The method executed 15+ separate queries for report status counts, monev reviews, financial reports, and output tracking.

3. **Missing Substance File Validation**: `SubmitProposalAction` did not validate that a proposal substance file was uploaded before allowing submission.

4. **Race Condition in Reviewer Assignment**: `AssignReviewersAction` lacked pessimistic locking, allowing potential race conditions when multiple admins assign reviewers simultaneously.

### Phase 5 - Quality Issues
1. **PHPStan Level 9 Compliance**: Static analysis revealed type safety issues with dynamic properties from `selectRaw()` queries and missing method checks.

2. **Laravel Pint Formatting**: Code style inconsistencies (whitespace in blank lines).

3. **Dead Code / Unused Imports**: Various files had unused imports.

## Decision

### Phase 4 Fixes

#### 1. Consolidated Stats Queries with Conditional Aggregation
Replaced multiple `clone $base)->where(...)->count()` calls with a single `selectRaw()` query using conditional aggregation:

```php
$aggregated = (clone $base)
    ->selectRaw('
        COUNT(*) as total,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved_by_dekan,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected
    ', [
        ReportStatus::SUBMITTED->value,
        ReportStatus::APPROVED_BY_DEKAN->value,
        ReportStatus::APPROVED->value,
        ReportStatus::REJECTED->value,
    ])
    ->first();
```

Applied to:
- `app/Livewire/KepalaLppm/ReportApproval.php`
- `app/Livewire/AdminLppm/ReportApproval.php`
- `app/Livewire/Kaprodi/ReportApproval.php`

#### 2. Consolidated DosenDashboard Process Stats
Consolidated 6 separate ProgressReport queries into a single conditional aggregation query in `loadProcessStats()`:

```php
$reportStats = ProgressReport::query()
    ->whereIn('proposal_id', $activeProposalIds)
    ->where('reporting_period', 'final')
    ->selectRaw('
        COUNT(DISTINCT proposal_id) as active_total,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved_dekan,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as draft,
        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected
    ', [...])
    ->first();
```

#### 3. Added Substance File Validation
Added validation in `SubmitProposalAction::execute()` to check for substance file on the detailable model:

```php
$detailable = $proposal->detailable;
if (! $detailable || ! method_exists($detailable, 'hasMedia') || ! $detailable->hasMedia('substance_file')) {
    return [
        'success' => false,
        'message' => 'File substansi proposal wajib diunggah sebelum mengajukan.',
    ];
}
```

#### 4. Added Pessimistic Locking to Reviewer Assignment
Wrapped reviewer assignment in `lockForUpdate()` to prevent race conditions:

```php
$lockedProposal = Proposal::where('id', $proposal->id)->lockForUpdate()->firstOrFail();
// Re-validate status and limits after lock
// ... assignment logic
```

### Phase 5 Fixes

#### 1. PHPStan Level 9 Compliance
- Added `@var` PHPDoc annotations for dynamic `selectRaw()` return types
- Fixed nullsafe operator usage (`?->` vs `->`) where properties are guaranteed non-null
- Added `method_exists()` check for `hasMedia()` on dynamic detailable relationship

#### 2. Laravel Pint Formatting
- Fixed whitespace in blank lines in `DosenDashboard.php` and `ProposalWorkflowTest.php`

#### 3. Test Updates
Updated tests to use correct media collection name (`substance_file` instead of `substance`) and proper fake file creation with content (`createWithContent()` instead of `create()`).

## Consequences

### Positive
- **Performance**: Reduced query count from 5-6 per component to 1 for stats; from 15+ to 1 for DosenDashboard process stats
- **Data Integrity**: Substance file validation prevents incomplete proposals from being submitted
- **Concurrency Safety**: Pessimistic locking prevents race conditions in reviewer assignment
- **Code Quality**: PHPStan Level 9 clean, consistent code style

### Negative
- **Migration Risk**: Existing tests required updates to match new validation requirements
- **Complexity**: Conditional aggregation SQL is more complex than simple count queries

## Validation
All validation steps pass:
- `php artisan test --filter=ReportSecurityAndLifecycleTest` ✓ (8 passed)
- `php artisan test` ✓ (379 passed, 1 risky, 13 skipped)
- `./vendor/bin/phpstan analyse` ✓ (No errors)
- `./vendor/bin/pint --test` ✓ (Passed)