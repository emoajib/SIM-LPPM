<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

/**
 * Regression: BC-03/BC-08 — `history:brief` adalah pintu masuk "engineering memory".
 *
 * Sebelum ada perintah ini, konteks area kerja (commit terakhir, kelas bug, test
 * penjaga) harus dicari manual. Kalau perintah ini rusak, agent/engineer bisa
 * mengubah kode tanpa tahu bug class yang pernah terjadi di area tersebut.
 */
class HistoryBriefTest extends TestCase
{
    public function test_prints_commits_bug_classes_and_guard_tests_for_an_area(): void
    {
        $this->artisan('history:brief', ['area' => 'modern', '--limit' => 3])
            ->expectsOutputToContain('Riwayat area: modern')
            ->expectsOutputToContain('Commit')
            ->expectsOutputToContain('Kelas bug')
            ->expectsOutputToContain('Test penjaga')
            ->assertExitCode(0);
    }

    public function test_lists_the_bug_class_registry_entries_for_the_area(): void
    {
        $this->artisan('history:brief', ['area' => 'modern'])
            ->expectsOutputToContain('BC-01')
            ->assertExitCode(0);
    }

    public function test_lists_guard_tests_matching_the_area(): void
    {
        $this->artisan('history:brief', ['area' => 'modern'])
            ->expectsOutputToContain('ModernPageAccessTest.php')
            ->assertExitCode(0);
    }

    public function test_reports_when_no_test_or_bug_class_matches_the_area(): void
    {
        $this->artisan('history:brief', ['area' => 'area-yang-tidak-ada-xyz'])
            ->expectsOutputToContain('(tidak ada)')
            ->assertExitCode(0);
    }
}
