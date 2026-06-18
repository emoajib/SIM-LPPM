<?php

namespace Tests\Unit\Commands;

use PHPUnit\Framework\ExpectationFailedException;
use Tests\TestCase;

class SyncEnumsTest extends TestCase
{
    public function test_enum_sync_command_exists()
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        // This test verifies the command is registered and runnable.
        // Exit code 0 = no mismatches found (expected for pgsql with varchar+CHECK).
        // Exit code 1 = mismatches found (expected for sqlite/mysql in dev).
        // We accept both as valid — the command existing and running is what we're testing.
        try {
            $this->artisan('enum:sync --check')->assertExitCode(0);
        } catch (ExpectationFailedException $e) {
            // Exit code 1 is also valid (mismatches found in sqlite/mysql dev env)
            $this->artisan('enum:sync --check')->assertExitCode(1);
        }
    }

    public function test_schema_drift_command_exists()
    {
        // Accepts either 0 (clean) or 1 (drift found or warn-only mode)
        try {
            $this->artisan('schema:drift')->assertExitCode(0);
        } catch (ExpectationFailedException $e) {
            $this->artisan('schema:drift')->assertExitCode(1);
        }
    }
}
