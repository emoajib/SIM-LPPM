<?php

namespace App\Console\Commands;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Models\Proposal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;

class FixProposalSubstanceFile extends Command
{
    protected $signature = 'proposals:fix-substance-file
                            {proposal_id : The proposal ID to fix}
                            {--download : Generate signed download URLs}
                            {--pdf= : Absolute path to the PDF file to upload as replacement}';

    protected $description = 'Fix a specific proposal\'s substance file by listing non-PDF files and optionally replacing with PDF';

    public function handle(): int
    {
        $proposalId = $this->argument('proposal_id');

        // First, list non-PDF substance files for this specific proposal
        $this->line("🔍 Memeriksa proposal ID: {$proposalId}");
        $this->newLine();

        $result = $this->listProposalDocFiles($proposalId);

        if ($result === self::FAILURE) {
            return self::FAILURE;
        }

        // If --pdf option is provided, replace the file
        if ($this->option('pdf')) {
            return $this->replaceSubstanceFile($proposalId);
        }

        $this->newLine();
        $this->info('📋 Langkah selanjutnya:');
        $this->line('  1. Download file .doc yang tidak sesuai PDF:');
        $this->line('     php artisan proposals:fix-substance-file '.$proposalId.' --download');
        $this->line('  2. Convert ke PDF (Word → Save as PDF)');
        $this->line('  3. Upload file PDF ke server:');
        $this->line('     scp file.pdf user@server:/tmp/');
        $this->line('  4. Jalankan replace:');
        $this->line('     php artisan proposals:fix-substance-file '.$proposalId.' --pdf=/tmp/file.pdf');
        $this->line('  5. Verifikasi di browser → PDF proposal otomatis ter-regenerate dengan lampiran');

        return self::SUCCESS;
    }

    /**
     * List non-PDF substance files for a specific proposal.
     */
    private function listProposalDocFiles(string $proposalId): int
    {
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
        $this->info("🔗 ID        : {$proposal->id}");
        $this->newLine();

        $media = $detailable->getMedia('substance_file');

        if ($media->isEmpty()) {
            $this->info('✅ Tidak ada file substance yang ditemukan.');

            return self::SUCCESS;
        }

        $nonPdfMedia = $media->filter(function ($item) {
            return $item->mime_type !== 'application/pdf';
        });

        if ($nonPdfMedia->isEmpty()) {
            $this->info('✅ Semua file substance sudah dalam format PDF!');

            return self::SUCCESS;
        }

        $this->warn("⚠️  Ditemukan {$nonPdfMedia->count()} file substance bukan PDF:");
        $this->newLine();

        $rows = [];

        foreach ($nonPdfMedia as $item) {
            $downloadUrl = $this->option('download')
                ? URL::temporarySignedRoute(
                    'media.download',
                    now()->addHours(24),
                    ['media' => $item->uuid]
                )
                : '(gunakan --download untuk generate URL)';

            $type = str_contains(get_class($detailable), 'Research') ? 'Penelitian' : 'Pengabdian';

            $rows[] = [
                'file' => $item->file_name,
                'mime_type' => $item->mime_type,
                'size' => $item->size,
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                'download_url' => $downloadUrl,
                'type' => $type,
            ];
        }

        $this->table(
            ['File', 'Tipe', 'Ukuran', 'Dibuat', 'Download URL'],
            collect($rows)->map(fn ($r) => [
                $r['file'],
                $r['type'],
                $this->formatSize($r['size']),
                $r['created_at'],
                $r['download_url'],
            ])->toArray()
        );

        if ($this->option('download')) {
            $this->newLine();
            $this->info('📥 Signed Download URLs (valid 24 jam):');
            foreach ($rows as $row) {
                $this->line("  📄 {$row['file']} ({$row['size']} bytes)");
                $this->line("  → {$row['download_url']}");
                $this->newLine();
            }
        }

        return self::SUCCESS;
    }

    /**
     * Replace substance file for a specific proposal (bypasses status/policy restrictions).
     */
    private function replaceSubstanceFile(string $proposalId): int
    {
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
            \Log::error("FixProposalSubstanceFile replace failed for proposal {$proposalId}: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Format file size to human readable format.
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = $bytes > 0 ? min(floor(log($bytes, 1024)), count($units) - 1) : 0;

        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }
}
