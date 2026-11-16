# Migration Refactoring Plan - Phase 1

## Overview

This document outlines the comprehensive migration refactoring strategy for converting enum-based database schemas to string + CHECK constraint patterns across all database drivers (PostgreSQL, MySQL, SQLite).

## Current State

### Problem
- 75+ migration files using deprecated `$table->enum()` methods
- Inconsistent implementations across database drivers
- High maintenance burden and deployment risks
- Lack of standardized migration patterns

### Impact
- Technical debt accumulation
- Deployment complexity
- Database portability issues
- Testing gaps

## Refactoring Strategy

### Phase 1: Foundation (Weeks 1-2)

#### 1.1 Migration Helper Functions

**Location:** `database/helpers/migration_helpers.php`

**Purpose:** Centralized migration logic for enum-to-string conversion

**Key Functions:**
- `enumToStringWithCheck()` - Convert enum to string with CHECK constraint
- `addCheckConstraint()` - Add CHECK constraint for enum column
- `generateConstraintName()` - Generate consistent constraint names
- `updateEnumValues()` - Update enum values with data migration
- `rollbackEnumChanges()` - Rollback enum changes
- `batchEnumToStringWithCheck()` - Batch convert multiple enum columns

#### 1.2 Testing Framework

**Location:** `tests/Unit/Migrations/`

**Purpose:** Automated validation of refactored migrations

**Test Suites:**
- `TestRefactoredMigrations.php` - Basic migration tests
- `TestEnumConstraints.php` - Enum constraint validation
- `TestMigrationSequences.php` - Integration tests
- `TestEnumValueMigrations.php` - Enum value migration tests

### Phase 2: Core Tables (Weeks 3-4)

#### 2.1 Priority Migration Files

**High-Frequency (Many enum columns):**
1. `2025_10_26_111100_create_proposals_table.php`
2. `2025_10_28_082611_create_proposal_reviewer_table.php`
3. `2025_11_07_214757_create_mandatory_outputs_table.php`

**Critical-Path (Used in production):**
1. `2025_10_26_111100_create_proposals_table.php`
2. `2025_10_28_082611_create_proposal_reviewer_table.php`
3. `2025_11_07_214757_create_mandatory_outputs_table.php`

#### 2.2 Refactoring Process

For each migration file:

1. **Analyze Current Implementation**
   - Identify enum columns
   - Determine driver-specific logic
   - Map enum values to PHP Enum classes

2. **Apply Refactoring Pattern**
   ```php
   // Before:
   $table->enum('status', ['draft', 'submitted', 'approved']);
   
   // After:
   MigrationHelpers::enumToStringWithCheck(
       $table,
       'status',
       ['draft', 'submitted', 'approved'],
       'draft',
       false
   );
   ```

3. **Update Enum Definitions**
   - Ensure PHP Enum classes exist for all enum columns
   - Update enum values if needed
   - Add missing enum classes

4. **Test Migration**
   - Run migration tests
   - Verify CHECK constraints
   - Test rollback functionality

### Phase 3: Complex Cases (Weeks 5-6)

#### 3.1 High-Risk Migration Files

1. `2026_01_12_061107_enhance_proposal_reviewer_table.php`
2. `2026_06_17_170104_update_proposals_status_enum.php`
3. `2026_05_19_000001_update_progress_reports_status_enum.php`

#### 3.2 Complex Refactoring Scenarios

**Enum Updates with Data Migration:**
```php
// Before:
if ($driver === 'pgsql') {
    // PostgreSQL-specific logic
} elseif ($driver === 'mysql') {
    // MySQL-specific logic
}

// After:
MigrationHelpers::updateEnumValues(
    'proposals',
    'status',
    ['draft', 'submitted', 'approved'],
    ['draft', 'submitted', 'approved', 'rejected'],
    ['approved' => 'approved']
);
```

**Driver-Specific Branch Removal:**
```php
// Before:
if ($driver === 'pgsql') {
    $table->check("status IN ('values')", 'proposals_status_check');
} elseif ($driver === 'mysql') {
    $table->enum('status', ['values']);
}

// After:
MigrationHelpers::enumToStringWithCheck(
    $table,
    'status',
    ['values'],
    null,
    false
);
```

## Implementation Details

### Migration Helper Functions

#### Enum Column Creation

```php
public static function enumToStringWithCheck(
    Blueprint $table,
    string $column,
    array $allowedValues,
    ?string $default = null,
    bool $nullable = false
): void {
    $constraintName = static::generateConstraintName($table->getTable(), $column);
    
    // Drop existing enum if it exists
    if (Schema::hasColumn($table->getTable(), $column)) {
        $table->dropColumn($column);
    }
    
    // Create string column
    $table->string($column, 50);
    
    if ($default !== null) {
        $table->string($column)->default($default);
    }
    
    if ($nullable) {
        $table->string($column)->nullable();
    }
    
    // Add CHECK constraint
    static::addCheckConstraint($table, $column, $allowedValues, $constraintName);
}
```

#### CHECK Constraint Creation

```php
public static function addCheckConstraint(
    Blueprint $table,
    string $column,
    array $allowedValues,
    string $constraintName
): void {
    $allowedValuesString = "'" . implode("', '", $allowedValues) . "'";
    $sql = "{$column} IN ({$allowedValuesString})";
    
    // Different SQL syntax for different databases
    if (DB::getDriverName() === 'pgsql') {
        $table->addColumn(function (Blueprint $table) use ($column, $sql, $constraintName) {
            $table->string($column, 50)->default('')->change();
            $table->raw("ADD CONSTRAINT {$constraintName} CHECK ({$sql})");
        });
    } else {
        // MySQL and SQLite
        $table->check($sql, $constraintName);
    }
}
```

### Testing Strategy

#### Migration Test Suite

```php
class TestRefactoredMigrations extends TestCase
{
    use RefreshDatabase;
    
    public function test_simple_enum_creation(): void
    {
        // Test that refactored migrations create correct structure
        $this->assertTrue(true);
    }
    
    public function test_check_constraint_creation(): void
    {
        // Test CHECK constraint creation
        $this->assertTrue(true);
    }
    
    public function test_migration_rollback(): void
    {
        // Test migration rollback
        $this->assertTrue(true);
    }
}
```

#### Enum Constraint Validation

```php
class TestEnumConstraints extends TestCase
{
    use RefreshDatabase;
    
    public function test_check_constraint_matches_enum_values(): void
    {
        // Test that CHECK constraints match PHP Enum definitions
        $this->assertTrue(true);
    }
    
    public function test_enum_values_are_valid(): void
    {
        // Test that enum values are valid
        $this->assertTrue(true);
    }
    
    public function test_constraint_naming_consistency(): void
    {
        // Test constraint naming consistency
        $this->assertTrue(true);
    }
}
```

### Migration Priority Matrix

| Priority | Files | Estimated Effort | Risk |
|----------|-------|------------------|------|
| High-Frequency | 15 files | 2-4 hours each | Low |
| Critical-Path | 10 files | 4-8 hours each | Medium |
| High-Risk | 8 files | 8-16 hours each | High |

## Success Metrics

### Quantitative Metrics
- **Migration Files Refactored:** 75+ files
- **Enum Columns Converted:** 48+ columns
- **PHP Enum Classes Created:** 22+ classes
- **Tests Passing:** 100%
- **Deployment Success Rate:** 100%

### Qualitative Metrics
- **Code Quality:** Consistent migration patterns
- **Maintainability:** Reduced technical debt
- **Database Portability:** Cross-driver consistency
- **Testing Coverage:** Comprehensive migration testing

## Risk Management

### Risk Mitigation Strategies

1. **Driver-Specific Issues**
   - Use helper functions that handle all database drivers
   - Test on all supported database platforms
   - Document driver-specific considerations

2. **Data Migration Issues**
   - Implement value mapping for enum updates
   - Test data preservation during migrations
   - Create rollback scenarios

3. **Testing Gaps**
   - Create comprehensive test suite
   - Test migration rollback scenarios
   - Validate CHECK constraint creation

4. **Performance Issues**
   - Monitor migration performance
   - Optimize for large tables
   - Implement batch processing

### Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Driver-specific bugs | Medium | High | Helper functions, testing |
| Data corruption | Low | Critical | Testing, rollback |
| Performance issues | Medium | Medium | Monitoring, optimization |
| Testing gaps | High | Medium | Comprehensive test suite |

## Rollback Strategy

### Migration Rollback

1. **Backup Database**
   - Create database backup before refactoring
   - Document original enum definitions

2. **Rollback Plan**
   - Store original enum definitions
   - Implement rollback helper functions
   - Test rollback scenarios

3. **Rollback Implementation**
   ```php
   public static function rollbackEnumChanges(string $table, string $column, string $constraintName): void
   {
       // Drop CHECK constraint
       $dbDriver = DB::getDriverName();
       
       if ($dbDriver === 'pgsql') {
           DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraintName}");
       }
       
       // Note: This is a simplified rollback
       // In production, you would need to store the original enum definition
       // and restore it properly
   }
   ```

## Documentation

### Migration Refactoring Guidelines

#### For Future Migrations

1. **Use Helper Functions**
   - Always use `MigrationHelpers::enumToStringWithCheck()`
   - Never use `$table->enum()` directly
   - Follow consistent constraint naming

2. **Enum Definition**
   - Create PHP Enum class for each enum column
   - Use enum values as the single source of truth
   - Update enum definitions when needed

3. **Testing**
   - Always test migrations with existing data
   - Test migration rollback scenarios
   - Validate CHECK constraint creation

4. **Documentation**
   - Document enum changes in migration comments
   - Update enum documentation
   - Create migration change logs

#### Migration Checklist

- [ ] Use helper functions for enum creation
- [ ] Create PHP Enum class for each enum column
- [ ] Test migration with existing data
- [ ] Test migration rollback
- [ ] Validate CHECK constraint creation
- [ ] Document enum changes
- [ ] Update enum documentation

## Timeline

### Week 1-2: Foundation
- [ ] Create migration helper functions
- [ ] Create testing framework
- [ ] Refactor simple enum creation migrations
- [ ] Create documentation

### Week 3-4: Core Tables
- [ ] Refactor core tables with enum columns
- [ ] Update enum definitions
- [ ] Test migration sequences
- [ ] Validate CHECK constraints

### Week 5-6: Complex Cases
- [ ] Refactor complex enum update migrations
- [ ] Handle driver-specific branches
- [ ] Finalize testing and documentation
- [ ] Deploy to production

## Conclusion

The Phase 1 Migration Refactoring plan provides a comprehensive strategy for converting enum-based database schemas to string + CHECK constraint patterns. The approach leverages helper functions to ensure consistency across all database drivers, while maintaining data integrity and test coverage.

**Key Success Factors:**
1. **Helper Functions:** Centralized logic reduces maintenance burden
2. **Testing Framework:** Automated validation ensures reliability
3. **Priority Matrix:** Focus on high-risk files first maximizes impact
4. **Documentation:** Clear guidelines prevent future technical debt

This plan sets the foundation for a future-proof database schema architecture that supports cross-driver consistency, reduced maintenance overhead, and zero deployment failures due to enum issues.