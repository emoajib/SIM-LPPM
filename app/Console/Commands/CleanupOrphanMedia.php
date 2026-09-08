<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CleanupOrphanMedia extends Command
{
    // Vetted by AI - Manual Review Required by Senior Engineer/Manager
    protected $signature = 'media:cleanup-orphans
                            {--ids= : ID media spesifik yang dipisahkan koma (contoh: --ids=97,99)}
                            {--include-trashed : Hapus juga media yang model induknya berstatus soft-deleted}
                            {--dry-run : Preview saja tanpa menghapus dari database atau disk}
                            {--force : Jalankan tanpa konfirmasi interaktif}';

    protected $description = 'Bersihkan record media yatim (orphan) yang model induknya sudah terhapus permanen atau via target ID spesifik';

    public function handle(): int
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $isDryRun = (bool) $this->option('dry-run');
        $includeTrashed = (bool) $this->option('include-trashed');
        $idsOption = $this->option('ids');
        $specificIds = is_string($idsOption) && trim($idsOption) !== ''
            ? array_filter(array_map('trim', explode(',', $idsOption)))
            : null;

        if ($isDryRun) {
            $this->warn('=== DRY RUN MODE — tidak ada data atau file yang dihapus ===');
        } else {
            if (! $this->option('force') && ! $this->confirm('Tindakan ini akan menghapus record media dan file fisiknya. Lanjutkan?')) {
                $this->info('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        $query = Media::query();
        if ($specificIds !== null) {
            $query->whereIn('id', $specificIds);
        }

        $records = $query->get();
        if ($records->isEmpty()) {
            $this->info('Tidak ada record media yang sesuai kriteria.');

            return self::SUCCESS;
        }

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($records as $media) {
            $isOrphan = false;
            $reason = '';

            if ($specificIds !== null) {
                $isOrphan = true;
                $reason = 'Target eksplisit via --ids';
            } else {
                $modelType = $media->model_type;
                if (! $modelType || ! class_exists($modelType)) {
                    $isOrphan = true;
                    $reason = "Class model {$modelType} tidak ditemukan";
                } else {
                    $hasSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelType), true);
                    $parentModel = $hasSoftDeletes
                        ? $modelType::withTrashed()->find($media->model_id)
                        : $modelType::find($media->model_id);

                    if (! $parentModel) {
                        $isOrphan = true;
                        $reason = "Model induk {$modelType} #{$media->model_id} tidak ada di database";
                    } elseif ($includeTrashed && $hasSoftDeletes && $parentModel->trashed()) {
                        $isOrphan = true;
                        $reason = "Model induk {$modelType} #{$media->model_id} telah di-soft-delete";
                    }
                }
            }

            if (! $isOrphan) {
                $skippedCount++;

                continue;
            }

            $disk = Storage::disk($media->disk ?: 'public');
            $relPath = $media->getPathRelativeToRoot();
            $fileExists = $disk->exists($relPath);

            $this->line(sprintf(
                '  [%s] ID:%d | %s/%s | Model: %s#%s | Fisik: %s (%s)',
                $isDryRun ? 'DRY-RUN' : 'HAPUS',
                $media->id,
                $media->collection_name,
                $media->file_name,
                class_basename($media->model_type),
                $media->model_id,
                $fileExists ? 'Ada' : 'Tidak Ada',
                $reason
            ));

            if (! $isDryRun) {
                $media->delete();
            }

            $deletedCount++;
        }

        $this->newLine();
        $this->info(sprintf(
            'Selesai. Total diproses: %d | %s: %d | Dilewati (aktif): %d',
            $records->count(),
            $isDryRun ? 'Akan dihapus' : 'Berhasil dihapus',
            $deletedCount,
            $skippedCount
        ));

        return self::SUCCESS;
    }
}
