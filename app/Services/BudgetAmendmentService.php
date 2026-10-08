<?php

namespace App\Services;

use App\Enums\BudgetAmendmentStatus;
use App\Enums\ProposalStatus;
use App\Models\BudgetAmendment;
use App\Models\BudgetGroup;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alur amandemen RAB pasca-danai: dosen mengajukan → Kepala LPPM menyetujui/menolak.
 * Proposal.status tidak tersentuh; versi aktif RAB berganti atomik saat disetujui.
 */
class BudgetAmendmentService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected BudgetValidationService $validationService
    ) {}

    /**
     * Syarat pengajuan amandemen. Mengembalikan [boleh, alasan].
     *
     * @return array{ok: bool, reason: ?string}
     */
    public function canRequest(Proposal $proposal, User $user): array
    {
        $isManager = $proposal->submitter_id === $user->getAuthIdentifier()
            || $proposal->teamMembers()->where('user_id', $user->getAuthIdentifier())->exists();

        if (! $isManager) {
            return ['ok' => false, 'reason' => 'Anda tidak memiliki akses untuk mengajukan amandemen proposal ini.'];
        }

        if ($proposal->status !== ProposalStatus::COMPLETED) {
            return ['ok' => false, 'reason' => 'Amandemen RAB hanya untuk proposal yang sudah selesai (COMPLETED).'];
        }

        if ($proposal->hasPendingBudgetAmendment()) {
            return ['ok' => false, 'reason' => 'Masih ada amandemen RAB yang menunggu persetujuan. Selesaikan dahulu.'];
        }

        if ($proposal->logbook_approved_at) {
            return ['ok' => false, 'reason' => 'LPJ sudah disahkan LPPM. Minta pembatalan pengesahan via Persetujuan Keuangan terlebih dahulu.'];
        }

        return ['ok' => true, 'reason' => null];
    }

    /**
     * Ajukan amandemen RAB baru. Item divalidasi struktur + pagu skema (ketat,
     * sama seperti revisi). Mengembalikan amandemen yang tersimpan (pending).
     *
     * @param  array<int, array{budget_group_id: mixed, budget_component_id?: mixed, year?: mixed, group?: mixed, component?: mixed, item_description?: mixed, volume?: mixed, unit_price?: mixed}>  $items
     */
    public function request(Proposal $proposal, User $user, array $items, string $reason): BudgetAmendment
    {
        $check = $this->canRequest($proposal, $user);
        if (! $check['ok']) {
            throw ValidationException::withMessages(['amendment' => [$check['reason']]]);
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['reason' => ['Alasan amandemen minimal 10 karakter.']]);
        }

        $normalized = $this->normalizeItems($items);

        $this->validateAgainstCaps($proposal, $normalized);

        return DB::transaction(function () use ($proposal, $user, $normalized, $reason) {
            $locked = Proposal::where('id', $proposal->id)->lockForUpdate()->firstOrFail();

            if ($locked->hasPendingBudgetAmendment()) {
                throw ValidationException::withMessages(['amendment' => ['Masih ada amandemen RAB yang menunggu persetujuan.']]);
            }

            $version = max(2, (int) ($locked->budgetItems()->max('version') ?? 1) + 1);

            $amendment = BudgetAmendment::create([
                'proposal_id' => $locked->id,
                'version' => $version,
                'status' => BudgetAmendmentStatus::PENDING,
                'reason' => $reason,
                'requested_by' => $user->getAuthIdentifier(),
            ]);

            foreach ($normalized as $item) {
                $amendment->items()->create($item);
            }

            $this->notificationService->notifyBudgetAmendmentSubmitted($amendment->fresh(), $user);

            return $amendment;
        });
    }

    /**
     * Normalisasi + validasi struktur tiap baris item.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeItems(array $items): array
    {
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => ['Amandemen harus memuat minimal 1 baris anggaran.']]);
        }

        $normalized = [];

        foreach (array_values($items) as $index => $item) {
            if (! is_array($item)) {
                throw ValidationException::withMessages(["items.{$index}" => ['Baris anggaran tidak valid.']]);
            }

            $volume = (float) ($item['volume'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            $rowErrors = [];
            if (empty($item['budget_group_id'])) {
                $rowErrors[] = 'Kelompok anggaran wajib dipilih.';
            }
            if (trim((string) ($item['item_description'] ?? '')) === '') {
                $rowErrors[] = 'Deskripsi item wajib diisi.';
            }
            if ($volume < 0 || $unitPrice < 0) {
                $rowErrors[] = 'Volume dan harga satuan tidak boleh negatif.';
            }

            if ($rowErrors !== []) {
                throw ValidationException::withMessages(["items.{$index}" => $rowErrors]);
            }

            $normalized[] = [
                'budget_group_id' => $item['budget_group_id'],
                'budget_component_id' => $item['budget_component_id'] ?? null,
                'year' => isset($item['year']) ? (int) $item['year'] : 1,
                'group' => $item['group'] ?? null,
                'component' => $item['component'] ?? null,
                'item_description' => trim((string) $item['item_description']),
                'volume' => $volume,
                'unit_price' => $unitPrice,
                'total_price' => $volume * $unitPrice,
            ];
        }

        return $normalized;
    }

    /**
     * Validasi total + persentase usulan amandemen terhadap pagu skema.
     */
    protected function validateAgainstCaps(Proposal $proposal, array $items): void
    {
        $isResearch = $proposal->detailable_type === Research::class;
        $type = $isResearch ? 'research' : 'community_service';

        $mapped = array_map(fn ($item) => [
            'budget_group_id' => $item['budget_group_id'],
            'total' => $item['total_price'],
        ], $items);

        $oldMapped = $proposal->activeBudgetItems()->get()->map(fn ($item) => [
            'budget_group_id' => $item->budget_group_id,
            'total' => $item->total_price,
        ])->toArray();

        $schemeId = $isResearch ? (int) ($proposal->research_scheme_id ?? 0) : (int) ($proposal->community_service_scheme_id ?? 0);

        // Amandemen memakai toleransi terhadap versi aktif (lihat BudgetValidationService).
        $this->validationService->validateAmendmentDelta(
            $oldMapped,
            $mapped,
            $type,
            (int) ($proposal->start_year ?: date('Y')),
            $proposal->semester ?: 'ganjil',
            $schemeId ?: null
        );
    }

    /**
     * Setujui amandemen: terapkan versi baru atomik + re-validasi realisasi.
     */
    public function approve(BudgetAmendment $amendment, User $decider, ?string $notes = null): void
    {
        DB::transaction(function () use ($amendment, $decider, $notes) {
            $locked = BudgetAmendment::where('id', $amendment->id)
                ->lockForUpdate()
                ->with('items')
                ->firstOrFail();

            if ($locked->status !== BudgetAmendmentStatus::PENDING) {
                throw ValidationException::withMessages(['amendment' => ['Amandemen sudah diputus sebelumnya.']]);
            }

            $proposal = Proposal::where('id', $locked->proposal_id)->lockForUpdate()->firstOrFail();

            if ($proposal->logbook_approved_at) {
                throw ValidationException::withMessages(['amendment' => ['LPJ sudah disahkan. Batalkan pengesahan terlebih dahulu.']]);
            }

            $this->assertRealizationFits($proposal, $locked);

            $proposal->budgetItems()->where('is_active', true)->update(['is_active' => false]);

            foreach ($locked->items as $item) {
                $proposal->budgetItems()->create([
                    'year' => $item->year ?? 1,
                    'budget_group_id' => $item->budget_group_id,
                    'budget_component_id' => $item->budget_component_id,
                    'group' => $item->group,
                    'component' => $item->component,
                    'item_description' => $item->item_description,
                    'volume' => (int) $item->volume,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'is_active' => true,
                    'version' => $locked->version,
                    'budget_amendment_id' => $locked->id,
                ]);
            }

            $locked->update([
                'status' => BudgetAmendmentStatus::APPROVED,
                'decision_notes' => $notes ? trim((string) $notes) : null,
                'decided_by' => $decider->getAuthIdentifier(),
                'decided_at' => now(),
            ]);

            $this->clearBudgetPdfCaches($proposal);
            $this->notificationService->notifyBudgetAmendmentDecided($locked->fresh(), $decider, true);
        });
    }

    /**
     * Tolak amandemen. Versi aktif RAB tidak berubah.
     */
    public function reject(BudgetAmendment $amendment, User $decider, string $notes): void
    {
        $notes = trim($notes);
        if (mb_strlen($notes) < 5) {
            throw ValidationException::withMessages(['decision_notes' => ['Catatan penolakan minimal 5 karakter.']]);
        }

        DB::transaction(function () use ($amendment, $decider, $notes) {
            $locked = BudgetAmendment::where('id', $amendment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== BudgetAmendmentStatus::PENDING) {
                throw ValidationException::withMessages(['amendment' => ['Amandemen sudah diputus sebelumnya.']]);
            }

            $locked->update([
                'status' => BudgetAmendmentStatus::REJECTED,
                'decision_notes' => $notes,
                'decided_by' => $decider->getAuthIdentifier(),
                'decided_at' => now(),
            ]);

            $this->notificationService->notifyBudgetAmendmentDecided($locked->fresh(), $decider, false);
        });
    }

    /**
     * Batalkan amandemen pending oleh pengaju/ketua.
     */
    public function cancel(BudgetAmendment $amendment, User $user): void
    {
        DB::transaction(function () use ($amendment, $user) {
            $locked = BudgetAmendment::where('id', $amendment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== BudgetAmendmentStatus::PENDING) {
                throw ValidationException::withMessages(['amendment' => ['Hanya amandemen pending yang bisa dibatalkan.']]);
            }

            $proposal = $locked->proposal;
            $isOwner = $locked->requested_by === $user->getAuthIdentifier()
                || $proposal->submitter_id === $user->getAuthIdentifier();

            if (! $isOwner) {
                throw ValidationException::withMessages(['amendment' => ['Anda tidak berhak membatalkan amandemen ini.']]);
            }

            $locked->items()->delete();
            $locked->delete();
        });
    }

    /**
     * Pastikan realisasi yang sudah dicatat muat dalam alokasi baru.
     */
    protected function assertRealizationFits(Proposal $proposal, BudgetAmendment $amendment): void
    {
        $newAllocations = [];
        foreach ($amendment->items as $item) {
            if ($item->budget_group_id === null) {
                continue;
            }
            $gid = (int) $item->budget_group_id;
            $newAllocations[$gid] = ($newAllocations[$gid] ?? 0) + (float) $item->total_price;
        }

        $violations = [];
        foreach ($newAllocations as $groupId => $allocation) {
            $used = (float) $proposal->dailyNotes()->where('budget_group_id', $groupId)->sum('amount');
            if ($used > $allocation) {
                $groupName = BudgetGroup::whereKey($groupId)->value('name') ?? "Kelompok #{$groupId}";
                $violations[] = "{$groupName}: terpakai Rp ".number_format($used, 0, ',', '.').' melebihi alokasi baru Rp '.number_format($allocation, 0, ',', '.');
            }
        }

        if ($violations !== []) {
            throw ValidationException::withMessages(['amendment' => array_merge(
                ['Realisasi yang sudah dicatat melebihi alokasi baru:'],
                $violations
            )]);
        }
    }

    /**
     * Bekukan angka RAB aktif ke snapshot proposal.
     * Dipanggil setiap kali LPJ disahkan agar angka LPJ historis tak berubah.
     *
     * @return array{version: int, total: float, groups: array<int, float>, unassigned: float, taken_at: string}
     */
    public function snapshotApprovedBudget(Proposal $proposal): array
    {
        $groups = $proposal->activeBudgetItems()
            ->selectRaw('budget_group_id, sum(total_price) as total')
            ->groupBy('budget_group_id')
            ->pluck('total', 'budget_group_id');

        $groupTotals = [];
        foreach ($groups as $groupId => $total) {
            $key = ($groupId === null || $groupId === '') ? 0 : (int) $groupId;
            $groupTotals[$key] = (float) $total;
        }

        $snapshot = [
            'version' => (int) ($proposal->activeBudgetItems()->max('version') ?? 1),
            'total' => (float) $proposal->activeBudgetItems()->sum('total_price'),
            'groups' => $groupTotals,
            'unassigned' => (float) ($groupTotals[0] ?? 0),
            'taken_at' => now()->toIso8601String(),
        ];

        $proposal->update(['approved_budget_snapshot' => $snapshot]);

        return $snapshot;
    }

    /**
     * Hapus PDF proposal/laporan/LPJ yang memuat angka RAB lama.
     */
    public function clearBudgetPdfCaches(Proposal $proposal): void
    {
        $patterns = [
            storage_path('app/pdf_cache/financial/financial_'.$proposal->id.'*.pdf'),
            storage_path('app/pdf_cache/proposals/*proposal_'.$proposal->id.'_*.pdf'),
        ];

        foreach ($proposal->progressReports()->pluck('id') as $reportId) {
            $patterns[] = storage_path('app/pdf_cache/reports/*report_'.$reportId.'_*.pdf');
        }

        foreach ($patterns as $pattern) {
            $files = glob($pattern);
            if (is_array($files)) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }
    }
}
