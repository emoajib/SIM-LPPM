<?php

/**
 * Regression: BC-01 — kontrak paritas filter modern vs klasik.
 *
 * Test ini membaca sumber komponen React dan view Blade sebagai kontrak statis.
 * Tujuannya menangkap regresi "modern kehilangan filter" tanpa perlu browser:
 * kalau seseorang menghapus parameter filter dari ModuleSubpageIndex, test gagal.
 */
it('module subpage index sends all parity filter params to the API', function () {
    $source = file_get_contents(resource_path('js/modern/components/ModuleSubpageIndex.jsx'));

    expect($source)
        ->toContain('roleFilter')
        ->toContain('academic_year')
        ->toContain('scheme_id')
        ->toContain('faculty_id')
        ->toContain('study_program_id')
        ->toContain('submodule');
});

it('module subpage index uses module-aware api base instead of hardcoded research', function () {
    $source = file_get_contents(resource_path('js/modern/components/ModuleSubpageIndex.jsx'));

    expect($source)
        ->toContain('const apiBase = module === "communityService" ? "/community-service" : "/research"')
        ->toContain('${apiBase}/api/v1/proposals')
        ->toContain('${apiBase}/api/v1/filters');
});

it('module subpage index renders final report status cards from api statistics', function () {
    $source = file_get_contents(resource_path('js/modern/components/ModuleSubpageIndex.jsx'));

    expect($source)
        ->toContain('meta.stats')
        ->toContain('belum_laporan')
        ->toContain('in_review');
});

it('classic final report index still exposes role tabs and status filter cards', function () {
    $source = file_get_contents(resource_path('views/livewire/research/final-report/index.blade.php'));

    expect($source)
        ->toContain("roleFilter === 'ketua'")
        ->toContain("roleFilter === 'anggota'")
        ->toContain('filterByStatus')
        ->toContain('belum_laporan');
});
