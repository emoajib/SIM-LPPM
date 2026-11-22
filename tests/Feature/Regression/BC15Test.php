<?php

/**
 * Regression: BC-15 — Nomor urut tabel Daftar Usulan Terdaftar melompat (3,5,6,7,8)
 * setelah filter skema, dan tombol filter ikut menempel pada baris tabel.
 *
 * Penyebab:
 * 1. `filterProposalTable()` hanya menyembunyikan baris tanpa menomori ulang,
 *    sehingga nomor mengikuti indeks global (1,2,4,9,10) — bukan urutan data
 *    hasil filter.
 * 2. `renderAll()` memakai selector `[data-scheme]` yang juga mengenai
 *    `<tr data-scheme>` pada tabel, sehingga klik baris mengubah filter skema.
 *
 * Penjaga ini mengunci bentuk yang benar tanpa perlu browser.
 */
it('BC-15: tabel Daftar Usulan Terdaftar menomori ulang baris hasil filter', function () {
    $blade = (string) file_get_contents(
        resource_path('views/livewire/dashboard/main-dashboard.blade.php')
    );

    // Sel nomor punya penanda khusus agar bisa diperbarui oleh JS.
    expect($blade)->toContain('sli-row-no');

    // Footer memberi tahu jumlah baris yang sedang tampil + hint gulir.
    expect($blade)->toContain('sli-proposal-visible-count');
    expect($blade)->toContain('sli-proposal-scroll-hint');
});

it('BC-15: filter skema menomori ulang baris yang terlihat dan tidak memasang handler pada baris', function () {
    $js = (string) file_get_contents(
        resource_path('views/components/dashboard/modern-infographic.blade.php')
    );

    // Nomor baris diperbarui berdasarkan hitungan baris yang terlihat.
    expect($js)->toContain("row.querySelector('.sli-row-no')");
    expect($js)->toContain('noCell.textContent = String(visibleCount)');

    // Selector tombol filter dibatasi ke <button>, bukan semua [data-scheme].
    expect($js)->not->toContain("document.querySelectorAll('[data-scheme]')");
    expect($js)->toContain("document.querySelectorAll('button[data-scheme]')");
});

it('BC-15: tema klasik juga menampilkan kolom No berurutan dan tabelnya bisa digulir', function () {
    $views = [
        'admin-dashboard',
        'dosen-dashboard',
        'exec-dashboard',
        'kepala-lppm-dashboard',
        'default-dashboard',
        'reviewer-dashboard',
    ];

    foreach ($views as $view) {
        $blade = (string) file_get_contents(
            resource_path("views/livewire/dashboard/{$view}.blade.php")
        );

        expect($blade)->toContain('>No</th>');
        expect($blade)->toContain('{{ $loop->iteration }}');
        expect($blade)->toContain('max-height: 520px; overflow-y: auto;');
    }
});
