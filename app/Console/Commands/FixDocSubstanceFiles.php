<?php

namespace App\Console\Commands;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Models\Proposal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FixDocSubstanceFiles extends Command
{
    protected $signature = 'proposals:list-doc-files
                            {--download : Generate signed download URLs}
                            {--replace= : Proposal ID to replace substance file (requires --pdf)}
                            {--pdf= : Absolute path to the PDF file to upload as replacement}';

    protected $description = 'List proposals with non-PDF substance files and optionally replace them with PDF';

    public function handle(): int
    {
        // Mode 2: Replace a specific proposal's substance file
        if ($this->option('replace')) {
            return $this->replaceSubstanceFile();
        }

        // Mode 1: List all non-PDF substance files
        return $this->listDocFiles();
    }

    /**
     * Replace substance file for a specific proposal (bypasses status/policy restrictions).
     */
    private function replaceSubstanceFile(): int
    {
        $proposalId = $this->option('replace');
        $pdfPath = $this->option('pdf');

        if (! $pdfPath) {
            $this->error('❌ Harus menyertakan --pdf=/path/ke/file.pdf');

            return self::FAILURE;
        }

        if (! file_exists($pdfPath)) {
            $this->error("❌ File tidak ditemukan: {$pdfPath}");

            return self::FAILURE;
        }

        $mimeType = mime_content_type($pdfPath);
        if ($mimeType !== 'application/pdf') {
            $this->error("❌ File bukan PDF (mime: {$mimeType}). Pastikan file sudah dikonversi ke PDF.");

            return self::FAILURE;
        }

        $proposal = Proposal::with(['detailable', 'submitter'])->find($proposalId);
        if (! $proposal) {
            $this->error("❌ Proposal tidak ditemukan: {$proposalId}");

            return self::FAILURE;
        }

        $detailable = $proposal->detailable;
        if (! $detailable) {
            $this->error('❌ Proposal tidak memiliki data detailable (Research/CommunityService).');

            return self::FAILURE;
        }

        if (! $detailable instanceof HasMedia) {
            $this->error('❌ Detailable tidak mendukung media.');

            return self::FAILURE;
        }

        $this->info("📄 Proposal  : {$proposal->title}");
        $this->info("👤 Dosen     : {$proposal->submitter->name}");
        $this->info("📊 Status    : {$proposal->status->value}");
        $this->info("📁 PDF baru  : {$pdfPath}");
        $this->newLine();

        if (! $this->confirm('Lanjutkan mengganti file substance? (PDF lama akan dihapus)')) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        try {
            // Delete old substance files
            $oldMedia = $detailable->getMedia('substance_file');
            $oldCount = $oldMedia->count();
            $detailable->clearMediaCollection('substance_file');
            $this->line("  🗑️  Hapus {$oldCount} file substance lama...");

            // Upload new PDF
            $fileName = basename($pdfPath, '.pdf').'.pdf';
            $detailable
                ->addMedia($pdfPath)
                ->usingName($fileName)
                ->usingFileName($fileName)
                ->withCustomProperties([
                    'replaced_by' => 'admin',
                    'replaced_at' => now()->toIso8601String(),
                    'original_format' => 'doc',
                ])
                ->toMediaCollection('substance_file');

            $this->info('  ✅ File PDF berhasil diupload!');

            // Clear PDF cache for this proposal so it regenerates with new file
            $cacheDir = storage_path('app/pdf_cache/proposals');
            $oldPdfs = glob($cacheDir.DIRECTORY_SEPARATOR."*proposal_{$proposal->id}_*.pdf");
            $oldPdfs = array_merge(
                $oldPdfs ?: [],
                glob($cacheDir.DIRECTORY_SEPARATOR."preview_proposal_{$proposal->id}_*.pdf") ?: []
            );
            foreach ($oldPdfs as $oldPdf) {
                @unlink($oldPdf);
            }
            $this->info('  🧹 PDF cache dihapus ('.count($oldPdfs).' file) → akan regenerate otomatis.');

            $this->newLine();
            $this->info('✅ Selesai! Buka PDF proposal untuk verifikasi lampiran sudah terlampir.');
            $this->line('   → '.url("/research/proposal/{$proposal->id}"));

        } catch (\Exception $e) {
            $this->error("❌ Gagal: {$e->getMessage()}");
            \Log::error("FixDocSubstanceFiles replace failed for proposal {$proposalId}: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * List all proposals with non-PDF substance files.
     */
    private function listDocFiles(): int
    {
        $nonPdfMedia = Media::where('collection_name', 'substance_file')
            ->where('mime_type', '!=', 'application/pdf')
            ->get();

        if ($nonPdfMedia->isEmpty()) {
            $this->info('✅ Tidak ada file substance .doc yang ditemukan. Semua sudah PDF!');

            return self::SUCCESS;
        }

        $this->warn("⚠️  Ditemukan {$nonPdfMedia->count()} file substance bukan PDF:");
        $this->newLine();

        $rows = [];

        foreach ($nonPdfMedia as $media) {
            $modelClass = $media->model_type;
            $model = $modelClass::find($media->model_id);

            if (! $model) {
                continue;
            }

            $proposal = Proposal::where('detailable_id', $model->id)
                ->where('detailable_type', $modelClass)
                ->with('submitter')
                ->first();

            if (! $proposal) {
                continue;
            }

            $downloadUrl = $this->option('download')
                ? URL::temporarySignedRoute(
                    'media.download',
                    now()->addHours(24),
                    ['media' => $media->uuid]
                )
                : '(gunakan --download untuk generate URL)';

            $type = str_contains($modelClass, 'Research') ? 'Penelitian' : 'Pengabdian';

            $rows[] = [
                'proposal_id' => $proposal->id,
                'type' => $type,
                'submitter' => $proposal->submitter->name ?? '-',
                'email' => $proposal->submitter->email ?? '-',
                'status' => $proposal->status->value,
                'file' => $media->file_name,
                'file_path' => $media->getPath(),
                'download_url' => $downloadUrl,
            ];
        }

        if (empty($rows)) {
            $this->info('Tidak ada proposal yang bisa diidentifikasi.');

            return self::SUCCESS;
        }

        $this->table(
            ['Proposal ID', 'Tipe', 'Dosen', 'Status', 'File', 'File Path'],
            collect($rows)->map(fn ($r) => [
                substr($r['proposal_id'], 0, 8).'...',
                $r['type'],
                $r['submitter'],
                $r['status'],
                $r['file'],
                $r['file_path'],
            ])->toArray()
        );

        if ($this->option('download')) {
            $this->newLine();
            $this->info('📥 Signed Download URLs (valid 24 jam):');
            foreach ($rows as $row) {
                $this->line("  [{$row['submitter']}] {$row['file']}");
                $this->line("  → {$row['download_url']}");
                $this->newLine();
            }
        }

        $this->newLine();
        $this->line('📋 <comment>Langkah remediasi per proposal:</comment>');
        $this->line('  1. Jalankan: <info>php artisan proposals:list-doc-files --download</info>');
        $this->line('  2. Download file .doc via URL yang dihasilkan');
        $this->line('  3. Convert ke PDF (Word → Save as PDF)');
        $this->line('  4. Upload file PDF ke server: <info>scp file.pdf user@server:/tmp/</info>');
        $this->line('  5. Jalankan replace: <info>php artisan proposals:list-doc-files --replace=PROPOSAL_ID --pdf=/tmp/file.pdf</info>');
        $this->line('  6. Verifikasi di browser → PDF proposal otomatis ter-regenerate dengan lampiran');

        return self::SUCCESS;
    }
}
