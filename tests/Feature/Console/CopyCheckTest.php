<?php

/**
 * Regression: BC-09 — artefak copy AI tidak boleh lolos ke produksi.
 */
function copyCheckTempDir(): string
{
    $dir = sys_get_temp_dir().'/copy-check-'.bin2hex(random_bytes(6));

    mkdir($dir, 0777, true);

    return $dir;
}

it('passes when no forbidden copy artifact exists', function () {
    $dir = copyCheckTempDir();

    try {
        file_put_contents($dir.'/clean.txt', '// Vetted by SOSIOMEN - Manual Review Required');

        $this->artisan('copy:check', ['--path' => $dir])
            ->assertSuccessful();
    } finally {
        @unlink($dir.'/clean.txt');
        @rmdir($dir);
    }
});

it('fails when a forbidden copy artifact exists', function () {
    $dir = copyCheckTempDir();
    $marker = 'Vetted by '.'AI';

    try {
        file_put_contents($dir.'/bad.txt', "// {$marker} - Manual Review Required");

        $this->artisan('copy:check', ['--path' => $dir])
            ->assertFailed();
    } finally {
        @unlink($dir.'/bad.txt');
        @rmdir($dir);
    }
});
