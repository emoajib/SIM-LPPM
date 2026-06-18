<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;

class SyncEnumsTest extends TestCase
{
    public function test_enum_sync_command_exists()
    {
        $this->artisan('enum:sync --check')
            ->assertExitCode(1);
    }

    public function test_schema_drift_command_exists()
    {
        $this->artisan('schema:drift')
            ->assertExitCode(1);
    }
}