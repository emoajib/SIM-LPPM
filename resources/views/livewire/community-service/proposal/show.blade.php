<x-slot:title>{{ $proposal->title }}</x-slot:title>
<x-slot:pageTitle>{{ $proposal->title }}</x-slot:pageTitle>
<x-slot:pageSubtitle>Detail Proposal Pengabdian Masyarakat</x-slot:pageSubtitle>
<x-slot:pageActions>
    <div class="btn-list">
        @if (url()->previous() && url()->previous() !== url()->current())
            <a href="{{ url()->previous() }}" class="btn-outline-secondary btn" wire:navigate.hover>
                <x-lucide-arrow-left class="icon" />
                Kembali
            </a>
        @elseif (active_has_role('reviewer'))
            <a href="{{ route('review.community-service') }}" class="btn-outline-secondary btn" wire:navigate.hover>
                <x-lucide-arrow-left class="icon" />
                Kembali
            </a>
        @else
            <a href="{{ route('community-service.proposal.index') }}" class="btn-outline-secondary btn" wire:navigate.hover>
                <x-lucide-arrow-left class="icon" />
                Kembali
            </a>
        @endif
        @if ($this->canEdit)
            <a href="{{ $this->getEditRoute($proposal->id) }}" wire:navigate.hover class="btn btn-primary">
                <x-lucide-pencil class="icon" />
                Edit
            </a>
        @elseif ($proposal->status === \App\Enums\ProposalStatus::DRAFT && $proposal->submitter_id === auth()->id() && !$this->isScheduleOpen)
            <button type="button" class="btn btn-secondary" disabled title="Periode pengajuan ditutup">
                <x-lucide-lock class="icon" />
                Edit (Ditutup)
            </button>
        @endif
        @if ($this->canDelete)
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <x-lucide-trash-2 class="icon" />
                Hapus
            </button>
        @endif
        <a href="{{ route('proposals.preview-pdf', $proposal) }}" target="_blank" class="btn btn-outline-info">
            <x-lucide-eye class="icon" />
            Pratinjau Proposal
        </a>
        <a data-navigate-ignore="true" href="{{ route('proposals.export-pdf', $proposal) }}" target="_blank"
            class="btn-outline-primary btn">
            <x-lucide-download class="icon" />
            Unduh Proposal (PDF)
        </a>
    </div>
</x-slot:pageActions>

<div class="row" x-data="{ currentStep: {{ active_has_role('reviewer') ? 5 : 1 }} }">
    <div class="col-md-12">
        <x-tabler.alert />
    </div>

    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    @if(auth()->user()->activeHasAnyRole(['admin lppm', 'admin lppm saintek', 'admin lppm dekabita', 'kepala lppm', 'superadmin']))
        <div class="col-md-12 mb-3">
            <div class="card border-info shadow-sm">
                <div class="card-header bg-info-lt d-flex justify-content-between align-items-center py-2">
                    <h3 class="card-title text-info mb-0 fs-4">
                        <x-lucide-file-text class="icon me-2" />
                        Nomor Kontrak Pengabdian (Admin LPPM)
                    </h3>
                    @if($proposal->contract_number)
                        <span class="badge bg-green text-white">Kontrak Terdaftar</span>
                    @else
                        <span class="badge bg-secondary text-white">Belum Diterbitkan</span>
                    @endif
                </div>
                <div class="card-body py-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-5">Nomor Kontrak Perjanjian Penugasan</label>
                            <input type="text" wire:model="contractNumber" class="form-control" placeholder="Contoh: 013/ITSNU/LPPM/KTR-PKM/VIII/2026" />
                            @error('contractNumber') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1 fs-5">Tanggal Kontrak</label>
                            <input type="date" wire:model="contractDate" class="form-control" />
                            @error('contractDate') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-2">
                            <button type="button" wire:click="saveContract" wire:loading.attr="disabled" class="btn btn-info w-100">
                                <span wire:loading.remove wire:target="saveContract"><x-lucide-save class="icon me-1" /> Simpan</span>
                                <span wire:loading wire:target="saveContract"><span class="spinner-border spinner-border-sm"></span></span>
                            </button>
                        </div>
                    </div>
                    <small class="text-muted mt-2 d-block">Nomor kontrak ini akan otomatis muncul pada Cover Laporan Pengabdian dan Cover Laporan Keuangan.</small>
                </div>
            </div>
        </div>
    @endif

    {{-- Schedule Closed Banner: only visible to submitter when draft & window is closed --}}
    @if ($proposal->status === \App\Enums\ProposalStatus::DRAFT
        && $proposal->submitter_id === auth()->id()
        && !$this->isScheduleOpen)
        <div class="col-md-12 mb-3">
            <div class="alert alert-warning alert-dismissible" role="alert">
                <div class="d-flex">
                    <div>
                        <x-lucide-calendar-x class="alert-icon icon me-2" />
                    </div>
                    <div>
                        <h4 class="alert-title">Periode Pengajuan Ditutup</h4>
                        <div class="text-secondary">
                            Proposal ini tidak dapat diedit karena periode pengajuan/revisi saat ini sedang ditutup oleh Admin LPPM.
                            Silakan hubungi Admin LPPM untuk informasi jadwal berikutnya.
                        </div>
                    </div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
            </div>
        </div>
    @endif

    @php
        $user = auth()->user();
        $role = 'guest';
        if (active_has_role('reviewer'))
            $role = 'reviewer';
        elseif (active_has_role('dekan'))
            $role = 'dekan';
        elseif (active_has_role('admin lppm'))
            $role = 'admin';
        elseif (active_has_role('kepala lppm'))
            $role = 'kepala';
        elseif ($user->id === $proposal->submitter_id)
            $role = 'dosen';
    @endphp

    <div class="col-md-12">
        @include('livewire.proposals.components.role-info-alert', ['role' => $role])
    </div>

    <!-- Steps Indicator -->
    <div class="mb-3 col-md-12">
        <div class="card">
            <div class="card-body">
                <ul class="my-4 steps steps-green steps-counter">
                    <li class="step-item" :class="{ 'active': currentStep === 1 }">
                        <a href="#" @click.prevent="currentStep = 1" class="text-decoration-none">Identitas Usulan</a>
                    </li>
                    <li class="step-item" :class="{ 'active': currentStep === 2 }">
                        <a href="#" @click.prevent="currentStep = 2" class="text-decoration-none">Substansi Usulan</a>
                    </li>
                    <li class="step-item" :class="{ 'active': currentStep === 3 }">
                        <a href="#" @click.prevent="currentStep = 3" class="text-decoration-none">RAB</a>
                    </li>
                    <li class="step-item" :class="{ 'active': currentStep === 4 }">
                        <a href="#" @click.prevent="currentStep = 4" class="text-decoration-none">Dokumen Pendukung</a>
                    </li>
                    <li class="step-item" :class="{ 'active': currentStep === 5 }">
                        <a href="#" @click.prevent="currentStep = 5" class="text-decoration-none">Review & Riwayat</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="col-md-12">
        <!-- Section 1: Identitas Usulan -->
        <div id="section-identitas" x-show="currentStep === 1">
            @include('livewire.community-service.proposal.components.identitas-usulan', ['proposal' => $proposal])
            <div class="mb-3">
                <livewire:community-service.proposal.team-member-form :proposalId="$proposal->id" :key="'team-form-' . $proposal->id" />
            </div>
        </div>

        <!-- Section 2: Substansi Usulan -->
        <div id="section-substansi" x-show="currentStep === 2">
            @include('livewire.community-service.proposal.components.substansi-usulan', ['proposal' => $proposal])
        </div>

        <!-- Section 3: RAB -->
        <div id="section-rab" x-show="currentStep === 3">
            @include('livewire.proposals.proposal-rab', ['proposal' => $proposal])
        </div>

        <!-- Section 4: Dokumen Pendukung -->
        <div id="section-dokumen" x-show="currentStep === 4">
            @include('livewire.proposals.proposal-partners', ['proposal' => $proposal])
        </div>

        <!-- Section 5: Workflow & Aksi -->
        <div id="section-workflow" x-show="currentStep === 5">
            @include('livewire.community-service.proposal.components.workflow-actions', ['proposal' => $proposal])
            
            <livewire:dashboard.letters.letter-list :proposal="$proposal" />

            <!-- History Section -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Riwayat Perubahan Data</h3>
                        </div>
                        <div class="card-body">
                            <livewire:proposals.proposal-activity-timeline :proposal="$proposal" />
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Riwayat Status</h3>
                        </div>
                        <div class="card-body">
                            @include('livewire.proposals.proposal-status-history', ['logs' => $proposal->statusLogs, 'proposal' => $proposal])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-4">
            <div class="d-flex justify-content-between">
                <button type="button" class="btn" @click="currentStep--" x-show="currentStep > 1">
                    <x-lucide-arrow-left class="icon" />
                    Kembali
                </button>
                <div x-show="currentStep === 1"></div>
                <button type="button" class="btn btn-primary" @click="currentStep++" x-show="currentStep < 5">
                    Selanjutnya
                    <x-lucide-arrow-right class="icon" />
                </button>
            </div>
        </div>
    </div>

    @include('livewire.proposals.components.modals', ['proposal' => $proposal])
</div>