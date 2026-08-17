<?php

namespace App\Livewire\Traits;

use App\Constants\ProposalConstants;
use App\Models\Partner;
use App\Services\BudgetValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait WithProposalWizard
{
    public function addOutput(): void
    {
        $this->form->outputs[] = [
            'year' => 1,
            'category' => 'Wajib',
            'group' => '',
            'type' => '',
            'status' => '',
            'description' => '',
        ];
    }

    public function removeOutput(int $index): void
    {
        unset($this->form->outputs[$index]);
        $this->form->outputs = array_values($this->form->outputs);
    }

    public function addBudgetItem(): void
    {
        $this->form->budget_items[] = [
            'year' => 1,
            'budget_group_id' => '',
            'budget_component_id' => '',
            'group' => '',
            'component' => '',
            'item' => '',
            'unit' => '',
            'volume' => 1,
            'unit_price' => 0,
            'total' => 0,
        ];
    }

    public function removeBudgetItem(int $index): void
    {
        unset($this->form->budget_items[$index]);
        $this->form->budget_items = array_values($this->form->budget_items);
    }

    public function calculateTotal(int $index): void
    {
        $volume = (float) ($this->form->budget_items[$index]['volume'] ?? 0);
        $price = (float) ($this->form->budget_items[$index]['unit_price'] ?? 0);
        $this->form->budget_items[$index]['total'] = $volume * $price;
    }

    public function addScheduleItem(): void
    {
        $this->form->schedule_items[] = [
            'activity_name' => '',
            'year' => 1,
            'start_month' => 1,
            'end_month' => 3,
        ];
    }

    public function removeScheduleItem(int $index): void
    {
        unset($this->form->schedule_items[$index]);
        $this->form->schedule_items = array_values($this->form->schedule_items);
    }

    public function initDefaultSchedule(): void
    {
        $duration = max((int) ($this->form->duration_in_years ?: 1), 1);
        $isPkm = $this->getProposalTypeForValidation() === 'community-service';
        $items = [];

        $templates = $isPkm ? [
            ['activity_name' => 'Sosialisasi & Koordinasi Mitra', 'start_month' => 1, 'end_month' => 3],
            ['activity_name' => 'Pelaksanaan Kegiatan / Pelatihan', 'start_month' => 4, 'end_month' => 7],
            ['activity_name' => 'Pendampingan & Evaluasi Mitra', 'start_month' => 8, 'end_month' => 10],
            ['activity_name' => 'Penyusunan Laporan & Publikasi PKM', 'start_month' => 11, 'end_month' => 12],
        ] : [
            ['activity_name' => 'Studi Literatur & Persiapan', 'start_month' => 1, 'end_month' => 3],
            ['activity_name' => 'Pengumpulan Data / Observasi', 'start_month' => 4, 'end_month' => 7],
            ['activity_name' => 'Analisis & Pengolahan Data', 'start_month' => 8, 'end_month' => 10],
            ['activity_name' => 'Penyusunan Laporan & Publikasi', 'start_month' => 11, 'end_month' => 12],
        ];

        for ($year = 1; $year <= $duration; $year++) {
            foreach ($templates as $t) {
                $items[] = array_merge($t, ['year' => $year]);
            }
        }

        $this->form->schedule_items = $items;
    }

    public function saveNewPartner(): void
    {
        $isPkm = $this->getProposalTypeForValidation() === 'community-service';

        $this->validate([
            'form.new_partner.name' => 'required|string|max:255',
            'form.new_partner.email' => 'nullable|email|max:255',
            'form.new_partner.institution' => 'required|string|max:255',
            'form.new_partner.country' => 'required|string|max:255',
            'form.new_partner.type' => ['required', 'string', 'max:255', Rule::in(ProposalConstants::PARTNER_TYPES)],
            'form.new_partner.address' => 'nullable|string',
            'form.new_partner_commitment_file' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        try {
            DB::transaction(function () {
                $partner = Partner::create([
                    'name' => $this->form->new_partner['name'],
                    'email' => $this->form->new_partner['email'],
                    'institution' => $this->form->new_partner['institution'],
                    'country' => $this->form->new_partner['country'],
                    'type' => $this->form->new_partner['type'],
                    'address' => $this->form->new_partner['address'],
                ]);

                if ($this->form->new_partner_commitment_file && $this->form->proposal) {
                    if ($this->form->new_partner_commitment_file instanceof TemporaryUploadedFile) {
                        $partner
                            ->addMedia($this->form->new_partner_commitment_file->getRealPath())
                            ->usingName($this->form->new_partner_commitment_file->getClientOriginalName())
                            ->usingFileName($this->form->new_partner_commitment_file->hashName())
                            ->withCustomProperties(['proposal_id' => $this->form->proposal->id])
                            ->toMediaCollection('commitment_letter');
                    }
                }

                $this->form->partner_ids[] = $partner->id;

                $this->form->new_partner = [
                    'name' => '',
                    'email' => '',
                    'institution' => '',
                    'country' => '',
                    'type' => '',
                    'address' => '',
                ];

                $this->form->new_partner_commitment_file = null;
            });

            $this->toastSuccess('Mitra baru berhasil ditambahkan.');
            $this->dispatch('partner-created');
            $this->dispatch('close-modal', modalId: 'modal-partner');
        } catch (\Exception $e) {
            \Log::error('Save New Partner Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->toastError('Gagal menambahkan mitra: '.$e->getMessage());
        }
    }

    /**
     * Tambah mitra yang sudah ada di database ke proposal ini.
     * Digunakan saat dosen memilih mitra existing dari daftar.
     */
    public function addExistingPartner(string $partnerId): void
    {
        // Validasi partner ID ada di database
        $partner = Partner::find($partnerId);
        if (! $partner) {
            $this->addError('existing_partner_id', 'Mitra tidak ditemukan.');

            return;
        }

        // Hindari duplikasi
        if (in_array($partnerId, $this->form->partner_ids ?? [])) {
            $this->addError('existing_partner_id', 'Mitra "'.$partner->name.'" sudah ditambahkan.');

            return;
        }

        $this->form->partner_ids[] = $partnerId;
        $this->dispatch('partner-created');
        $this->dispatch('close-modal', modalId: 'modal-partner');
    }

    /**
     * Buka modal upload Surat Kesediaan untuk mitra tertentu.
     */
    public function prepareCommitmentUpload(string $partnerId): void
    {
        // Pastikan mitra memang sudah ada di proposal ini
        if (! in_array($partnerId, $this->form->partner_ids ?? [])) {
            $this->addError('commitmentUploadFile', 'Mitra tidak ditemukan di proposal ini.');

            return;
        }

        $this->commitmentUploadPartnerId = $partnerId;
        $this->commitmentUploadFile = null;
        $this->dispatch('open-modal', modalId: 'modal-upload-kesediaan');
    }

    /**
     * Simpan Surat Kesediaan Mitra ke koleksi `commitment_letter` di model Partner.
     */
    public function uploadCommitmentLetter(?string $partnerId = null): void
    {
        $targetPartnerId = $partnerId ?? $this->commitmentUploadPartnerId;

        if (! $targetPartnerId) {
            $this->toastError('ID Mitra tidak ditemukan.');

            return;
        }

        $this->validate([
            'commitmentUploadFile' => 'required|file|mimes:pdf|max:5120',
        ]);

        try {
            $partner = Partner::find($targetPartnerId);
            if (! $partner) {
                $this->addError('commitmentUploadFile', 'Mitra tidak ditemukan.');

                return;
            }

            if ($this->form->proposal) {
                $proposalId = $this->form->proposal->id;

                // Hapus file lama HANYA untuk proposal ini (menggunakan filter yang lebih aman)
                $partner->getMedia('commitment_letter')
                    ->filter(function ($media) use ($proposalId) {
                        return $media->getCustomProperty('proposal_id') === $proposalId;
                    })
                    ->each(fn ($media) => $media->delete());

                // Pastikan file adalah instance TemporaryUploadedFile yang valid
                if (! ($this->commitmentUploadFile instanceof TemporaryUploadedFile)) {
                    throw new \Exception('File tidak valid atau gagal diunggah. Silakan coba unggah kembali.');
                }

                $partner->addMedia($this->commitmentUploadFile->getRealPath())
                    ->usingName($this->commitmentUploadFile->getClientOriginalName())
                    ->usingFileName($this->commitmentUploadFile->hashName())
                    ->withCustomProperties(['proposal_id' => $proposalId])
                    ->toMediaCollection('commitment_letter');

                $this->toastSuccess('Surat Kesediaan berhasil diunggah.');
            } else {
                // Jika ini adalah proposal baru (masih di memori), kita simpan ke array sementara?
                // Namun sistem saat ini mewajibkan proposal di-save draft dulu sebelum upload mitra.
                $this->toastWarning('Silakan simpan draft usulan terlebih dahulu sebelum mengunggah dokumen mitra.');
            }

            $this->resetCommitmentUpload();
            $this->dispatch('close-modal', modalId: 'modal-upload-kesediaan');

            // Refresh computed partners agar tampilan tabel terupdate
            unset($this->partners);
        } catch (\Exception $e) {
            \Log::error('Upload Commitment Letter Error: '.$e->getMessage(), [
                'partner_id' => $targetPartnerId,
                'proposal_id' => $this->form->proposal?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->toastError('Gagal mengunggah file: '.$e->getMessage());
        }
    }

    /**
     * Reset state upload surat kesediaan.
     */
    public function resetCommitmentUpload(): void
    {
        $this->commitmentUploadPartnerId = null;
        $this->commitmentUploadFile = null;
    }

    public function validateBudgetRealtime(): void
    {
        try {
            if (! empty($this->form->budget_items)) {
                $year = (int) ($this->form->start_year ?: date('Y'));
                $semester = $this->form->semester ?: 'ganjil';
                $schemeId = $this->getProposalTypeForValidation() === 'research'
                    ? (int) $this->form->research_scheme_id
                    : (int) $this->form->community_service_scheme_id;

                app(BudgetValidationService::class)->validateBudgetGroupPercentages(
                    $this->form->budget_items,
                    $this->getProposalTypeForValidation(),
                    $year,
                    $semester,
                    $schemeId ?: null
                );

                app(BudgetValidationService::class)->validateBudgetCap(
                    $this->form->budget_items,
                    $this->getProposalTypeForValidation(),
                    $year,
                    $semester,
                    $schemeId ?: null
                );
            }

            $this->budgetValidationErrors = [];
        } catch (ValidationException $e) {
            $this->budgetValidationErrors = $e->errors()['budget_items'] ?? [];
        }
    }

    abstract protected function getProposalTypeForValidation(): string;
}
