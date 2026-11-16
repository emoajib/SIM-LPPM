<?php

/**
 * Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
 */

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

/**
 * Dump database MySQL dengan pola aman (F8).
 *
 * - Kredensial dikirim lewat defaults-file 0600, bukan `-p{password}`, agar
 *   password tidak terlihat di daftar proses (`ps`).
 * - Output diarahkan langsung ke file, bukan ditahan di memori Process.
 * - stderr ditampung ke file `.err` agar pesan kegagalan bisa dilaporkan.
 *
 * Catatan: `App\Livewire\Settings\BackupData` punya varian serupa dengan UX
 * Livewire sendiri. Migrasi ke service ini menyusul agar perilaku halaman
 * Settings tidak berubah di rilis yang sama.
 */
class MysqlDumpService
{
    /**
     * Lokasi mysqldump yang dikenal, termasuk jalur khusus cPanel.
     *
     * @var list<string>
     */
    private const CANDIDATES = [
        '/usr/bin/mysqldump',
        '/usr/local/bin/mysqldump',
        '/opt/cpanel/ea-mysql*/bin/mysqldump',
        'mysqldump',
    ];

    public function findBinary(): ?string
    {
        foreach (self::CANDIDATES as $candidate) {
            if (str_contains($candidate, '*')) {
                foreach (glob($candidate) ?: [] as $path) {
                    if (is_executable($path)) {
                        return $path;
                    }
                }

                continue;
            }

            if (str_starts_with($candidate, '/') && is_executable($candidate)) {
                return $candidate;
            }

            $which = Process::run(['which', $candidate]);
            if ($which->successful() && trim($which->output()) !== '') {
                return trim($which->output());
            }
        }

        return null;
    }

    /**
     * Dump seluruh database ke $path (SQL polos, tanpa kompresi).
     *
     * @return array{0: bool, 1: string} [berhasil, pesan error]
     */
    public function dumpTo(string $path): array
    {
        $binary = $this->findBinary();

        if ($binary === null) {
            return [false, 'perintah mysqldump tidak tersedia di server ini'];
        }

        $driver = DB::getDriverName();

        if ($driver !== 'mysql') {
            return [false, sprintf('driver didukung: mysql (aktif: %s)', $driver)];
        }

        $connection = (string) config('database.default');

        /** @var array<string, mixed> $config */
        $config = config("database.connections.{$connection}") ?? [];

        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '3306');

        if ($database === '') {
            return [false, 'nama database kosong pada config database'];
        }

        // `localhost` pada MySQL berarti socket; paksa TCP agar tidak gagal
        // "Can't connect to local server through socket".
        if ($host === '' || $host === 'localhost') {
            $host = '127.0.0.1';
        }

        $defaultsFile = tempnam(sys_get_temp_dir(), 'sim-lppm-mycnf-');

        if ($defaultsFile === false) {
            return [false, 'gagal membuat file konfigurasi sementara'];
        }

        $quote = static fn (string $value): string => '"'.str_replace(
            ['\\', '"'],
            ['\\\\', '\\"'],
            $value,
        ).'"';

        file_put_contents($defaultsFile, '[client]'."\n"
            .'user='.$quote($username)."\n"
            .'password='.$quote($password)."\n"
            .'host='.$quote($host)."\n"
            .'port='.$quote($port)."\n");
        @chmod($defaultsFile, 0600);

        $errorPath = $path.'.err';

        $command = escapeshellarg($binary)
            .' --defaults-extra-file='.escapeshellarg($defaultsFile)
            .' --protocol=tcp --single-transaction --quick --complete-insert --lock-tables=false'
            .' '.escapeshellarg($database)
            .' > '.escapeshellarg($path)
            .' 2> '.escapeshellarg($errorPath);

        $result = Process::timeout(1800)->run($command);

        @unlink($defaultsFile);

        $error = trim((string) @file_get_contents($errorPath));
        @unlink($errorPath);

        if (! $result->successful() || ! is_file($path) || (int) @filesize($path) === 0) {
            @unlink($path);

            return [false, $error !== '' ? $error : 'dump kosong atau proses gagal'];
        }

        return [true, ''];
    }
}
