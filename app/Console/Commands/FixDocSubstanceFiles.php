<?php

namespace App\Console\Commands;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

use App\Models\Proposal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FixDocSubstanceFiles extends Command
{
    protected $signature = 'proposals:list-doc-files {--download : Generate signed download URLs}';

    protected $description = 'List proposals with non-PDF substance files (uploaded as .doc/.docx) and optionally generate download links for admin remediation';

    public function handle(): int
    {
        // Find all non-PDF substance files
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
            // Resolve the proposal via the model
            $modelClass = $media->model_type;
            $model = $modelClass::find($media->model_id);

            if (! $model) {
                continue;
            }

            // Get proposal from detailable (Research/CommunityService)
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
                'file' => $media->file_name,
                'mime' => $media->mime_type,
                'edit_url' => url("/{$this->getEditPrefix($modelClass)}/proposal/{$proposal->id}/edit"),
                'download_url' => $downloadUrl,
            ];
        }

        if (empty($rows)) {
            $this->info('Tidak ada proposal yang bisa diidentifikasi.');

            return self::SUCCESS;
        }

        $this->table(
            ['Proposal ID', 'Tipe', 'Dosen', 'Email', 'File', 'MIME', 'Edit URL'],
            collect($rows)->map(fn ($r) => [
                substr($r['proposal_id'], 0, 8).'...',
                $r['type'],
                $r['submitter'],
                $r['email'],
                $r['file'],
                $r['mime'],
                $r['edit_url'],
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
        $this->line('📋 <comment>Langkah remediasi:</comment>');
        $this->line('  1. Jalankan: <info>php artisan proposals:list-doc-files --download</info>');
        $this->line('  2. Download file .doc via URL yang dihasilkan');
        $this->line('  3. Convert ke PDF (Word → Save as PDF / Google Docs → Download as PDF)');
        $this->line('  4. Login sebagai admin, buka Edit URL di atas');
        $this->line('  5. Upload file PDF yang sudah diconvert di Step 2 Substansi');
        $this->line('  6. Simpan → PDF proposal akan otomatis ter-regenerate');

        return self::SUCCESS;
    }

    private function getEditPrefix(string $modelClass): string
    {
        return str_contains($modelClass, 'Research') ? 'research' : 'community-service';
    }
}
