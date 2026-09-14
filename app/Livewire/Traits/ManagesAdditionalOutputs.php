<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Constants\ProposalConstants;
use App\Models\AdditionalOutput;
use App\Models\ProposalOutput;

/**
 * Trait to manage dynamic additional outputs (Tambah / Edit / Hapus) in Final Reports.
 * Vetted by AI - Manual Review Required by Senior Engineer/Manager
 */
trait ManagesAdditionalOutputs
{
    public bool $showOutputModal = false;

    public ?int $editingProposalOutputId = null;

    public string $outputType = '';

    public string $outputGroup = '';

    public int $outputYear = 1;

    public string $outputTargetStatus = 'Draft';

    public string $outputDescription = '';

    public function openAddOutputModal(): void
    {
        if (! $this->canEdit) {
            abort(403, 'Akses ditolak. Laporan tidak dapat diedit.');
        }

        $this->editingProposalOutputId = null;
        $this->outputType = '';
        $this->outputGroup = '';
        $this->outputYear = 1;
        $this->outputTargetStatus = 'Draft';
        $this->outputDescription = '';
        $this->showOutputModal = true;
    }

    public function openEditProposalOutputModal(int $outputId): void
    {
        if (! $this->canEdit) {
            abort(403, 'Akses ditolak. Laporan tidak dapat diedit.');
        }

        /** @var ProposalOutput $output */
        $output = $this->proposal->outputs()
            ->where('id', $outputId)
            ->where('category', 'Tambahan')
            ->firstOrFail();

        $this->editingProposalOutputId = $output->id;
        $this->outputType = $output->type ?? '';
        $this->outputGroup = $output->group ?? '';
        $this->outputYear = (int) ($output->output_year ?? 1);
        $this->outputTargetStatus = $output->target_status ?? 'Draft';
        $this->outputDescription = $output->description ?? '';
        $this->showOutputModal = true;
    }

    public function closeOutputModal(): void
    {
        $this->showOutputModal = false;
        $this->editingProposalOutputId = null;
        $this->resetValidation([
            'outputType',
            'outputYear',
            'outputTargetStatus',
            'outputDescription',
        ]);
    }

    public function saveProposalOutputPlan(): void
    {
        if (! $this->canEdit) {
            abort(403, 'Akses ditolak. Laporan tidak dapat diedit.');
        }

        $this->validate([
            'outputType' => 'required|string|max:255',
            'outputYear' => 'required|integer|min:1|max:5',
            'outputTargetStatus' => 'required|string|max:50',
            'outputDescription' => 'nullable|string|max:500',
        ], [
            'outputType.required' => 'Jenis luaran wajib dipilih.',
            'outputYear.required' => 'Tahun target wajib diisi.',
            'outputTargetStatus.required' => 'Status target wajib dipilih.',
        ]);

        $group = $this->outputGroup;
        if (empty($group)) {
            $group = $this->detectOutputGroup($this->outputType);
        }

        if ($this->editingProposalOutputId) {
            $output = $this->proposal->outputs()
                ->where('id', $this->editingProposalOutputId)
                ->where('category', 'Tambahan')
                ->firstOrFail();

            $output->update([
                'type' => $this->outputType,
                'group' => $group,
                'output_year' => $this->outputYear,
                'target_status' => $this->outputTargetStatus,
                'description' => $this->outputDescription,
            ]);

            $message = 'Rencana luaran tambahan berhasil diperbarui.';
        } else {
            $newOutput = $this->proposal->outputs()->create([
                'category' => 'Tambahan',
                'type' => $this->outputType,
                'group' => $group,
                'output_year' => $this->outputYear,
                'target_status' => $this->outputTargetStatus,
                'description' => $this->outputDescription,
            ]);

            if (isset($this->form)) {
                if (! isset($this->form->additionalOutputs[$newOutput->id])) {
                    $this->form->additionalOutputs[$newOutput->id] = $this->form->getEmptyAdditionalOutput();
                }
            }

            $message = 'Luaran tambahan baru berhasil ditambahkan.';
        }

        $this->proposal->load('outputs');
        $this->closeOutputModal();
        $this->toastSuccess($message);
    }

    public function deleteProposalOutput(int $outputId): void
    {
        if (! $this->canEdit) {
            abort(403, 'Akses ditolak. Laporan tidak dapat diedit.');
        }

        /** @var ProposalOutput $output */
        $output = $this->proposal->outputs()
            ->where('id', $outputId)
            ->where('category', 'Tambahan')
            ->firstOrFail();

        // If progress report exists, clean up associated AdditionalOutput and its uploaded media
        if ($this->progressReport) {
            /** @var AdditionalOutput|null $additionalOutput */
            $additionalOutput = $this->progressReport->additionalOutputs()
                ->where('proposal_output_id', $output->id)
                ->first();

            if ($additionalOutput instanceof AdditionalOutput) {
                $additionalOutput->clearMediaCollection('book_document');
                $additionalOutput->clearMediaCollection('publication_certificate');
                $additionalOutput->delete();
            }
        }

        if (isset($this->form->additionalOutputs[$output->id])) {
            unset($this->form->additionalOutputs[$output->id]);
        }

        $output->delete();
        $this->proposal->load('outputs');

        $this->toastSuccess('Luaran tambahan berhasil dihapus.');
    }

    protected function detectOutputGroup(string $type): string
    {
        $isResearch = str_contains($this->proposal->detailable_type ?? '', 'Research');
        $catalog = $isResearch ? ProposalConstants::RESEARCH_OUTPUT_TYPES : ProposalConstants::PKM_OUTPUT_TYPES;

        foreach ($catalog as $group => $types) {
            if (in_array($type, $types, true)) {
                return $group;
            }
        }

        return 'lainnya';
    }

    public function getAvailableOutputOptionsProperty(): array
    {
        $isResearch = str_contains($this->proposal->detailable_type ?? '', 'Research');

        return $isResearch ? ProposalConstants::RESEARCH_OUTPUT_TYPES : ProposalConstants::PKM_OUTPUT_TYPES;
    }
}
