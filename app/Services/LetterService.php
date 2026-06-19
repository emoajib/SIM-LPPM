<?php

namespace App\Services;

use App\Enums\LetterStatus;
use App\Enums\SignatureMode;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use App\Services\Validation\LetterValidationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class LetterService
{
    public function __construct(
        protected ?LetterValidationService $letterValidationService = null
    ) {
        $this->letterValidationService ??= app(LetterValidationService::class);
    }

    /**
     * Generate the next letter number for a given type.
     */
    public function generateNextNumber(LetterType $type): string
    {
        return DB::transaction(function () use ($type) {
            $year = date('Y');
            $month = (int) date('n');
            $romanMonth = $this->getRomanMonth($month);

            // Find the last sequence number for this year with row lock
            $letters = Letter::where('letter_type_id', $type->id)
                ->whereYear('created_at', $year)
                ->whereNotNull('letter_number')
                ->lockForUpdate()
                ->get();

            $nextSequence = 1;
            if ($letters->isNotEmpty()) {
                $maxSequence = $letters->map(function ($l) {
                    $parts = explode('/', (string) $l->letter_number);

                    return (int) ($parts[0] ?? 0);
                })->max();

                $nextSequence = $maxSequence + 1;
            }

            $formattedNumber = str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);

            // Replace placeholders in format
            $format = $type->numbering_format ?? '{NOMOR}/{CODE}/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}';

            return str_replace(
                ['{NOMOR}', '{CODE}', '{BULAN-ROMAWI}', '{TAHUN}'],
                [$formattedNumber, (string) $type->code, $romanMonth, $year],
                $format
            );
        });
    }

    /**
     * Get Roman numeral for a month number.
     */
    public function getRomanMonth(int $month): string
    {
        $map = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return $map[$month] ?? (string) $month;
    }

    /**
     * Generate PDF for a letter.
     */
    public function generatePdf(Letter $letter): string
    {
        $letter->load(['letterType', 'user']);

        /** @var LetterType|null $letterType */
        $letterType = $letter->letterType;

        if (! $letterType || empty($letterType->template_view)) {
            throw new \RuntimeException(
                'Template view untuk surat jenis "'.($letterType->name ?? 'unknown').'" tidak tersedia.'
            );
        }

        $metadata = array_merge([
            'signer_name' => get_institution_config('lppm_head_name'),
            'signer_position' => get_institution_config('lppm_head_position'),
            'signer_nidn' => get_institution_config('lppm_head_nidn'),
        ], $letter->metadata ?? []);

        $qrUrl = URL::signedRoute('letters.verify', ['letter' => $letter->id]);
        $qrDataUri = generate_qr_code_data_uri($qrUrl);

        $moduleKey = $this->resolveModuleKey($letterType->template_view ?? '');
        $pdfConfig = get_pdf_config('letter', $moduleKey);

        $data = [
            'letter' => $letter,
            'metadata' => $metadata,
            'team' => $letter->team_snapshot ?? [],
            'qrDataUri' => $qrDataUri,
            'pdfConfig' => $pdfConfig,
        ];

        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $pdf = Pdf::loadView((string) $letterType->template_view, $data);

        $filename = 'letters/'.$letterType->code.'-'.Str::slug($letter->letter_number ?? (string) $letter->id).'.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        Letter::where('id', $letter->id)->update(['file_path' => $filename]);

        return $filename;
    }

    /**
     * Resolve module key from template view name for PDF config overrides.
     * Maps e.g. 'pdf.letters.surat-tugas' → 'surat-tugas'
     * Falls back to 'letter' for backward compatibility.
     */
    private function resolveModuleKey(string $templateView): string
    {
        $map = collect(config('pdf-modules.list', []))->pluck('key', 'template');

        return $map[$templateView] ?? 'letter';
    }

    /**
     * Check if the lettering module is active.
     */
    public function isActive(): bool
    {
        return (bool) Setting::get('module_persuratan_active', false);
    }

    /**
     * Check if a duplicate letter type exists for the given proposal.
     */
    public function hasDuplicateLetter(Proposal $proposal, int $letterTypeId, ?string $teamSource = null): bool
    {
        $query = Letter::where('letter_type_id', $letterTypeId)
            ->where('reference_type', get_class($proposal))
            ->where('reference_id', $proposal->id)
            ->where('status', '!=', LetterStatus::REJECTED->value);

        if ($teamSource !== null) {
            $query->where('team_source', $teamSource);
        }

        return $query->exists();
    }

    private function shouldBypassApproval(): bool
    {
        return Setting::get('surat_signature_mode', SignatureMode::TTE->value) === SignatureMode::MANUAL->value
            && (bool) Setting::get('surat_wet_signature_bypass', false);
    }

    /**
     * Create a new letter request from a proposal.
     */
    public function requestLetter(Proposal $proposal, User $user, array $data): Letter
    {
        // Validate letter creation
        $validationErrors = $this->letterValidationService->validateLetterCreation($data, $user->id);
        if (! empty($validationErrors)) {
            throw new \DomainException(implode('\n', $validationErrors));
        }

        $metadata = [
            'activity_type' => $data['activityType'],
            'title' => $proposal->title,
            'date_string' => $data['dateString'],
            'time_string' => $data['timeString'],
            'location' => $data['location'],
            'destination_name' => $data['destinationName'] ?? null,
            'tembusan' => array_map('trim', explode("\n", $data['tembusan'] ?? '1. Arsip')),
            'signer_name' => get_institution_config('lppm_head_name'),
            'signer_position' => get_institution_config('lppm_head_position'),
            'signer_nidn' => get_institution_config('lppm_head_nidn'),
            'signer_address' => get_institution_config('lppm_head_address'),
        ];

        $bypass = $this->shouldBypassApproval();
        $status = $bypass ? LetterStatus::READY_TO_PRINT->value : LetterStatus::PENDING_APPROVAL->value;

        $letter = Letter::create([
            'letter_type_id' => $data['letterTypeId'],
            'user_id' => $user->id,
            'reference_type' => get_class($proposal),
            'reference_id' => $proposal->id,
            'source' => 'proposal',
            'team_source' => 'proposal',
            'signature_mode' => Setting::get('surat_signature_mode', SignatureMode::TTE->value),
            'status' => $status,
            'metadata' => $metadata,
            'team_snapshot' => TeamSnapshotBuilder::forProposal($proposal),
        ]);

        $letter->logs()->create([
            'user_id' => $user->id,
            'action' => $bypass ? 'auto_published' : 'submitted',
            'notes' => $bypass
                ? 'Diterbitkan otomatis (TTD Basah bypass)'
                : 'Diajukan ke Kepala LPPM.',
            'created_at' => now(),
        ]);

        if ($bypass) {
            /** @var LetterType $lType */
            $lType = $letter->letterType;
            $letter->update([
                'published_at' => now(),
                'letter_number' => $this->generateNextNumber($lType),
            ]);
            $this->generatePdf($letter->fresh());
        }

        return $letter;
    }

    /**
     * Create a new manual letter request (without proposal).
     */
    public function requestManualLetter(User $user, array $data): Letter
    {
        // Validate letter creation
        $validationErrors = $this->letterValidationService->validateLetterCreation($data, $user->id);
        if (! empty($validationErrors)) {
            throw new \DomainException(implode('\n', $validationErrors));
        }

        return DB::transaction(function () use ($user, $data) {
            $referenceType = $data['reference_type'] ?? null;
            $referenceId = $data['reference_id'] ?? null;

            // Lock to prevent duplicate submissions
            $exists = Letter::where('letter_type_id', $data['letterTypeId'])
                ->where('user_id', $user->id)
                ->when($referenceId, fn ($q) => $q->where('reference_type', $referenceType)->where('reference_id', $referenceId))
                ->when(! $referenceId, fn ($q) => $q->whereNull('reference_id')->where('source', 'manual'))
                ->where('status', '!=', LetterStatus::CANCELLED->value)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                $msg = $referenceId
                    ? 'Anda sudah mengajukan surat jenis ini untuk proposal tersebut.'
                    : 'Anda sudah mengajukan surat jenis ini yang sedang diproses.';
                throw new \DomainException($msg);
            }

            $metadata = [
                'title' => $data['title'],
                'activity_type' => $data['activityType'],
                'date_string' => $data['dateString'],
                'time_string' => $data['timeString'],
                'location' => $data['location'],
                'destination_name' => $data['destinationName'] ?? null,
                'tembusan' => array_map('trim', explode("\n", $data['tembusan'] ?? '1. Arsip')),
                'signer_name' => get_institution_config('lppm_head_name'),
                'signer_position' => get_institution_config('lppm_head_position'),
                'signer_nidn' => get_institution_config('lppm_head_nidn'),
                'signer_address' => get_institution_config('lppm_head_address'),
            ];

            $referenceType = $data['reference_type'] ?? null;
            $referenceId = $data['reference_id'] ?? null;
            $source = $referenceId ? 'proposal' : 'manual';
            $teamSource = $referenceId ? 'manual' : 'manual';

            $bypass = $this->shouldBypassApproval();
            $status = $bypass ? LetterStatus::READY_TO_PRINT->value : LetterStatus::PENDING_APPROVAL->value;

            $letter = Letter::create([
                'letter_type_id' => $data['letterTypeId'],
                'user_id' => $user->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'source' => $source,
                'team_source' => $teamSource,
                'signature_mode' => Setting::get('surat_signature_mode', SignatureMode::TTE->value),
                'status' => $status,
                'metadata' => $metadata,
                'team_snapshot' => TeamSnapshotBuilder::forManual($data['team'] ?? [], $user),
            ]);

            $letter->logs()->create([
                'user_id' => $user->id,
                'action' => $bypass ? 'auto_published' : 'submitted',
                'notes' => $bypass
                    ? 'Diterbitkan otomatis (TTD Basah bypass)'
                    : 'Diajukan ke Kepala LPPM.',
                'created_at' => now(),
            ]);

            if ($bypass) {
                /** @var LetterType $lType */
                $lType = $letter->letterType;
                $letter->update([
                    'published_at' => now(),
                    'letter_number' => $this->generateNextNumber($lType),
                ]);
                $this->generatePdf($letter->fresh());
            }

            return $letter;
        });
    }

    /**
     * Approve a letter: generate number, set status, create PDF, log.
     */
    public function approveLetter(Letter $letter): string
    {
        // Validate letter approval
        $validationErrors = $this->letterValidationService->validateLetterApproval($letter, auth()->id());
        if (! empty($validationErrors)) {
            throw new \DomainException(implode('\n', $validationErrors));
        }

        return DB::transaction(function () use ($letter) {
            // Lock the letter row to prevent double-approve
            $lockedLetter = Letter::where('id', $letter->id)
                ->where('status', LetterStatus::PENDING_APPROVAL->value)
                ->lockForUpdate()
                ->first();

            if (! $lockedLetter) {
                throw new \DomainException('Surat sudah diproses atau tidak ditemukan.');
            }

            /** @var LetterType $letterType */
            $letterType = $lockedLetter->letterType;

            $lockedLetter->update([
                'letter_number' => $this->generateNextNumber($letterType),
                'published_at' => now(),
                'status' => $lockedLetter->signature_mode === SignatureMode::TTE ? LetterStatus::PUBLISHED->value : LetterStatus::READY_TO_PRINT->value,
            ]);

            try {
                return $this->generatePdf($lockedLetter);
            } catch (\Exception $e) {
                Log::error('PDF generation failed during letter approval', [
                    'letter_id' => $lockedLetter->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $lockedLetter->update([
                    'status' => LetterStatus::PENDING_APPROVAL->value,
                    'letter_number' => null,
                    'published_at' => null,
                    'file_path' => null,
                ]);

                throw $e;
            }
        });
    }

    /**
     * Reject a letter with optional reason.
     */
    public function rejectLetter(Letter $letter, ?string $reason = null): void
    {
        // Validate letter rejection
        $validationErrors = $this->letterValidationService->validateLetterRejection($letter, auth()->id());
        if (! empty($validationErrors)) {
            throw new \DomainException(implode('\n', $validationErrors));
        }

        if (in_array($letter->status, Letter::STATUS_IMMUTABLE)) {
            throw new \DomainException('Cannot reject immutable letter.');
        }

        $letter->update([
            'status' => LetterStatus::REJECTED->value,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Cancel a letter (by owner, only if not immutable).
     */
    public function cancelLetter(Letter $letter): void
    {
        // Validate letter cancellation
        $validationErrors = $this->letterValidationService->validateLetterCancellation($letter, auth()->id());
        if (! empty($validationErrors)) {
            throw new \DomainException(implode('\n', $validationErrors));
        }

        if (in_array($letter->status, Letter::STATUS_IMMUTABLE)) {
            throw new \DomainException('Surat yang sudah diterbitkan tidak bisa dibatalkan.');
        }

        if ($letter->user_id !== auth()->id()) {
            throw new \DomainException('Bukan surat Anda.');
        }

        $letter->update(['status' => LetterStatus::CANCELLED->value]);
    }

    /**
     * Resubmit a rejected letter with updated data.
     */
    public function resubmitLetter(Letter $letter, array $data): void
    {
        // Validate letter resubmission
        $validationErrors = $this->letterValidationService->validateLetterResubmission($letter, auth()->id());
        if (! empty($validationErrors)) {
            throw new \DomainException(implode('\n', $validationErrors));
        }

        if ($letter->status !== LetterStatus::REJECTED->value) {
            throw new \DomainException('Hanya surat yang ditolak yang bisa diajukan ulang.');
        }

        if ($letter->user_id !== auth()->id()) {
            throw new \DomainException('Bukan surat Anda.');
        }

        $metadata = array_merge($letter->metadata ?? [], [
            'title' => $data['title'] ?? $letter->metadata['title'] ?? null,
            'activity_type' => $data['activityType'] ?? $letter->metadata['activity_type'] ?? null,
            'date_string' => $data['dateString'] ?? $letter->metadata['date_string'] ?? null,
            'time_string' => $data['timeString'] ?? $letter->metadata['time_string'] ?? null,
            'location' => $data['location'] ?? $letter->metadata['location'] ?? null,
            'destination_name' => $data['destinationName'] ?? $letter->metadata['destination_name'] ?? null,
            'tembusan' => isset($data['tembusan']) ? array_map('trim', explode("\n", $data['tembusan'])) : ($letter->metadata['tembusan'] ?? []),
        ]);

        $letter->update([
            'status' => LetterStatus::PENDING_APPROVAL->value,
            'rejection_reason' => null,
            'metadata' => $metadata,
            'team_snapshot' => $data['team'] ?? $letter->team_snapshot,
        ]);
    }

    /**
     * Batch approve multiple letters.
     */
    public function batchApprove(Collection $letters): array
    {
        $results = ['succeeded' => [], 'failed' => []];

        foreach ($letters as $letter) {
            try {
                $this->approveLetter($letter);
                $results['succeeded'][] = $letter->id;
            } catch (\Exception $e) {
                $results['failed'][$letter->id] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Batch reject multiple letters.
     */
    public function batchReject(Collection $letters, string $reason): array
    {
        $results = ['succeeded' => [], 'failed' => []];

        foreach ($letters as $letter) {
            try {
                $this->rejectLetter($letter, $reason);
                $results['succeeded'][] = $letter->id;
            } catch (\Exception $e) {
                $results['failed'][$letter->id] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get letter statistics for dashboard.
     */
    public function getLetterStats(): array
    {
        return [
            'total' => Letter::count(),
            'pending' => Letter::where('status', LetterStatus::PENDING_APPROVAL->value)->count(),
            'published' => Letter::where('status', LetterStatus::PUBLISHED->value)->count(),
            'rejected' => Letter::where('status', LetterStatus::REJECTED->value)->count(),
            'cancelled' => Letter::where('status', LetterStatus::CANCELLED->value)->count(),
            'ready_to_print' => Letter::where('status', LetterStatus::READY_TO_PRINT->value)->count(),
        ];
    }

    /**
     * Get letter statistics for a specific user dashboard.
     */
    public function getLetterStatsForUser(string $userId): array
    {
        return [
            'total' => Letter::where('user_id', $userId)->count(),
            'pending' => Letter::where('user_id', $userId)->where('status', LetterStatus::PENDING_APPROVAL->value)->count(),
            'published' => Letter::where('user_id', $userId)->where('status', LetterStatus::PUBLISHED->value)->count(),
            'rejected' => Letter::where('user_id', $userId)->where('status', LetterStatus::REJECTED->value)->count(),
            'cancelled' => Letter::where('user_id', $userId)->where('status', LetterStatus::CANCELLED->value)->count(),
            'ready_to_print' => Letter::where('user_id', $userId)->where('status', LetterStatus::READY_TO_PRINT->value)->count(),
        ];
    }

    /**
     * Search dosen users for autocomplete.
     */
    public function searchDosen(string $query): Collection
    {
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $query);

        return User::whereHas('roles', fn ($q) => $q->where('name', 'dosen'))
            ->where(function ($q) use ($escaped) {
                $q->where('name', 'like', '%'.$escaped.'%')
                    ->orWhere('email', 'like', '%'.$escaped.'%');
            })
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get();
    }
}
