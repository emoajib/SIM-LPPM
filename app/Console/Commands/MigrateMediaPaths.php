<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MigrateMediaPaths extends Command
{
    protected $signature = 'media:migrate-paths
                            {--dry-run : Preview saja, tidak memindahkan file}
                            {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Migrasi file media dari path lama (modelslug-id8) ke path baru (NIDN-name). Aman dijalankan berulang kali.';

    public function handle(): int
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $isDryRun = $this->option('dry-run');
        $disk = Storage::disk('public');

        if ($isDryRun) {
            $this->warn('=== DRY RUN MODE — tidak ada file yang dipindahkan ===');
        } else {
            if (! $this->option('force') && ! $this->confirm('Migrasi akan memindahkan file secara fisik. Lanjutkan?')) {
                return self::FAILURE;
            }
        }

        $total = Media::count();
        $this->info("Memeriksa {$total} media records...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $moved     = 0;
        $skipped   = 0;
        $notFound  = 0;
        $errors    = 0;

        Media::chunk(50, function ($batch) use ($disk, $isDryRun, &$moved, &$skipped, &$notFound, &$errors, $bar) {
            foreach ($batch as $media) {
                $bar->advance();

                try {
                    $newRel = $media->getPathRelativeToRoot();

                    // Jika file sudah ada di path baru → skip
                    if ($disk->exists($newRel)) {
                        $skipped++;
                        continue;
                    }

                    // Cari di path lama: {collection}/{modelslug}-{id8}/{media_id}/{filename}
                    $modelSlug = Str::slug(class_basename($media->model_type));
                    $modelId8  = is_string($media->model_id) ? substr($media->model_id, 0, 8) : $media->model_id;
                    $oldRel    = $media->collection_name.'/'.$modelSlug.'-'.$modelId8.'/'.$media->id.'/'.$media->file_name;

                    if (! $disk->exists($oldRel)) {
                        $notFound++;
                        $this->newLine();
                        $this->warn("  NOT FOUND | ID:{$media->id} | {$media->collection_name}/{$media->file_name}");
                        continue;
                    }

                    if ($isDryRun) {
                        $this->newLine();
                        $this->line("  [DRY] MOVE: {$oldRel}");
                        $this->line("          TO: {$newRel}");
                        $moved++;
                        continue;
                    }

                    // Buat direktori tujuan
                    $newDir = dirname($newRel);
                    if (! $disk->exists($newDir)) {
                        $disk->makeDirectory($newDir);
                    }

                    // Salin file ke path baru
                    $oldAbs = $disk->path($oldRel);
                    $newAbs = $disk->path($newRel);

                    if (! copy($oldAbs, $newAbs)) {
                        $errors++;
                        $this->newLine();
                        $this->error("  COPY FAILED | {$oldRel}");
                        continue;
                    }

                    // Verifikasi integritas (ukuran sama)
                    if (filesize($oldAbs) !== filesize($newAbs)) {
                        @unlink($newAbs);
                        $errors++;
                        $this->newLine();
                        $this->error("  SIZE MISMATCH — rollback | {$oldRel}");
                        continue;
                    }

                    // Migrasi konversi (pdf_image, dll) jika ada
                    $oldConvDir = $media->collection_name.'/'.$modelSlug.'-'.$modelId8.'/'.$media->id.'/conversions/';
                    $newConvDir = dirname($newRel).'/conversions/';
                    if ($disk->exists($oldConvDir)) {
                        foreach ($disk->files($oldConvDir) as $convFile) {
                            $convFilename = basename($convFile);
                            $newConvPath  = $newConvDir.$convFilename;
                            if (! $disk->exists($newConvPath)) {
                                $disk->copy($convFile, $newConvPath);
                            }
                        }
                    }

                    // Hapus file lama setelah berhasil salin
                    @unlink($oldAbs);

                    $moved++;

                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("  ERROR | ID:{$media->id} | ".$e->getMessage());
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info('=== Hasil Migrasi ===');
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Sudah di path baru (skip)', $skipped],
                [($isDryRun ? 'Akan dipindah' : 'Berhasil dipindah'), $moved],
                ['Tidak ditemukan di manapun', $notFound],
                ['Error', $errors],
            ]
        );

        if (! $isDryRun && $moved > 0) {
            $this->info('Jalankan: php artisan optimize:clear');
        }

        return ($errors > 0) ? self::FAILURE : self::SUCCESS;
    }
}
