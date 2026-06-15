<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class LetterService
{
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

        /** @var LetterType $letterType */
        $letterType = $letter->letterType;

        $metadata = array_merge([
            'signer_name' => Setting::get('lppm_head_name', ''),
            'signer_position' => Setting::get('lppm_head_position', ''),
            'signer_nidn' => Setting::get('lppm_head_nidn', ''),
        ], $letter->metadata ?? []);

        $qrUrl = URL::signedRoute('letters.verify', ['letter' => $letter->id]);
        $qrDataUri = generate_qr_code_data_uri($qrUrl);

        $data = [
            'letter' => $letter,
            'metadata' => $metadata,
            'team' => $letter->team_snapshot ?? [],
            'qrDataUri' => $qrDataUri,
        ];

        $pdf = Pdf::loadView((string) $letterType->template_view, $data);

        $filename = 'letters/'.$letterType->code.'-'.Str::slug($letter->letter_number ?? (string) $letter->id).'.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        Letter::where('id', $letter->id)->update(['file_path' => $filename]);

        return $filename;
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
            ->where('status', '!=', 'rejected');

        if ($teamSource !== null) {
            $query->where('team_source', $teamSource);
        }

        return $query->exists();
    }

    private function shouldBypassApproval(): bool
    {
        return Setting::get('surat_signature_mode', 'tte') === 'manual'
            && (bool) Setting::get('surat_wet_signature_bypass', false);
    }

    /**
     * Create a new letter request from a proposal.
     */
    public function requestLetter(Proposal $proposal, User $user, array $data): Letter
    {
        $metadata = [
            'activity_type' => $data['activityType'],
            'title' => $proposal->title,
            'date_string' => $data['dateString'],
            'time_string' => $data['timeString'],
            'location' => $data['location'],
            'destination_name' => $data['destinationName'] ?? null,
            'tembusan' => array_map('trim', explode("\n", $data['tembusan'] ?? '1. Arsip')),
            'signer_name' => Setting::get('lppm_head_name', 'Aria Mulyapradana, S.Psi., M.A.'),
            'signer_position' => Setting::get('lppm_head_position', 'Kepala LPPM'),
            'signer_nidn' => Setting::get('lppm_head_nidn', '0612118401'),
            'signer_address' => Setting::get('lppm_head_address', 'Jl. Rowolaku No. 01 Kajen, Pekalongan'),
        ];

        $bypass = $this->shouldBypassApproval();
        $status = $bypass ? 'ready_to_print' : 'pending_approval';

        $letter = Letter::create([
            'letter_type_id' => $data['letterTypeId'],
            'user_id' => $user->id,
            'reference_type' => get_class($proposal),
            'reference_id' => $proposal->id,
            'source' => 'proposal',
            'team_source' => 'proposal',
            'signature_mode' => Setting::get('surat_signature_mode', 'tte'),
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
            $letter->update([
                'published_at' => now(),
                'letter_number' => $this->generateNextNumber($letter->letterType),
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
        return DB::transaction(function () use ($user, $data) {
            $referenceType = $data['reference_type'] ?? null;
            $referenceId = $data['reference_id'] ?? null;

            // Lock to prevent duplicate submissions
            $exists = Letter::where('letter_type_id', $data['letterTypeId'])
                ->where('user_id', $user->id)
                ->when($referenceId, fn ($q) => $q->where('reference_type', $referenceType)->where('reference_id', $referenceId))
                ->when(! $referenceId, fn ($q) => $q->whereNull('reference_id')->where('source', 'manual'))
                ->where('status', '!=', 'cancelled')
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
                'signer_name' => Setting::get('lppm_head_name', 'Aria Mulyapradana, S.Psi., M.A.'),
                'signer_position' => Setting::get('lppm_head_position', 'Kepala LPPM'),
                'signer_nidn' => Setting::get('lppm_head_nidn', '0612118401'),
                'signer_address' => Setting::get('lppm_head_address', 'Jl. Rowolaku No. 01 Kajen, Pekalongan'),
            ];

            $referenceType = $data['reference_type'] ?? null;
            $referenceId = $data['reference_id'] ?? null;
            $source = $referenceId ? 'proposal' : 'manual';
            $teamSource = $referenceId ? 'manual' : 'manual';

            $bypass = $this->shouldBypassApproval();
            $status = $bypass ? 'ready_to_print' : 'pending_approval';

            $letter = Letter::create([
                'letter_type_id' => $data['letterTypeId'],
                'user_id' => $user->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'source' => $source,
                'team_source' => $teamSource,
                'signature_mode' => Setting::get('surat_signature_mode', 'tte'),
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
                $letter->update([
                    'published_at' => now(),
                    'letter_number' => $this->generateNextNumber($letter->letterType),
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
        return DB::transaction(function () use ($letter) {
            // Lock the letter row to prevent double-approve
            $lockedLetter = Letter::where('id', $letter->id)
                ->where('status', 'pending_approval')
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
                'status' => $lockedLetter->signature_mode === 'tte' ? 'published' : 'ready_to_print',
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
                    'status' => 'pending_approval',
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
        if (in_array($letter->status, Letter::STATUS_IMMUTABLE)) {
            throw new \DomainException('Cannot reject immutable letter.');
        }

        $letter->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Cancel a letter (by owner, only if not immutable).
     */
    public function cancelLetter(Letter $letter): void
    {
        if (in_array($letter->status, Letter::STATUS_IMMUTABLE)) {
            throw new \DomainException('Surat yang sudah diterbitkan tidak bisa dibatalkan.');
        }

        if ($letter->user_id !== auth()->id()) {
            throw new \DomainException('Bukan surat Anda.');
        }

        $letter->update(['status' => 'cancelled']);
    }

    /**
     * Resubmit a rejected letter with updated data.
     */
    public function resubmitLetter(Letter $letter, array $data): void
    {
        if ($letter->status !== 'rejected') {
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
            'status' => 'pending_approval',
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
            'pending' => Letter::where('status', 'pending_approval')->count(),
            'published' => Letter::where('status', 'published')->count(),
            'rejected' => Letter::where('status', 'rejected')->count(),
            'cancelled' => Letter::where('status', 'cancelled')->count(),
            'ready_to_print' => Letter::where('status', 'ready_to_print')->count(),
        ];
    }

    /**
     * Get letter statistics for a specific user dashboard.
     */
    public function getLetterStatsForUser(string $userId): array
    {
        return [
            'total' => Letter::where('user_id', $userId)->count(),
            'pending' => Letter::where('user_id', $userId)->where('status', 'pending_approval')->count(),
            'published' => Letter::where('user_id', $userId)->where('status', 'published')->count(),
            'rejected' => Letter::where('user_id', $userId)->where('status', 'rejected')->count(),
            'cancelled' => Letter::where('user_id', $userId)->where('status', 'cancelled')->count(),
            'ready_to_print' => Letter::where('user_id', $userId)->where('status', 'ready_to_print')->count(),
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
