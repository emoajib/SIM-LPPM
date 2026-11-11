<x-slot:title><i class="ti ti-pencil me-1" aria-hidden="true"></i>
Edit Usulan Penelitian</x-slot:title>
<x-slot:pageTitle><i class="ti ti-pencil me-1" aria-hidden="true"></i>
Edit Usulan Penelitian</x-slot:pageTitle>
<x-slot:pageSubtitle>Perbarui proposal penelitian Anda dengan mengisi form di bawah ini.</x-slot:pageSubtitle>
<x-slot:pageActions>
    <a href="{{ route('research.proposal.index') }}" class="btn-outline-secondary btn d-flex align-items-center gap-2">
        
        Kembali ke Daftar
    </a>
</x-slot:pageActions>

<div>
    <x-tabler.alert />

    <!-- Step Indicator -->
    <div class="card mb-3">
        <div class="card-body">
            <ul class="steps steps-green steps-counter my-4">
                <li class="step-item {{ $currentStep === 1 ? 'active' : '' }} {{ $currentStep > 1 ? 'completed' : '' }}">
                    Identitas Usulan
                </li>
                <li class="step-item {{ $currentStep === 2 ? 'active' : '' }} {{ $currentStep > 2 ? 'completed' : '' }}">
                    Substansi Usulan
                </li>
                <li class="step-item {{ $currentStep === 3 ? 'active' : '' }} {{ $currentStep > 3 ? 'completed' : '' }}">
                    RAB
                </li>
                <li class="step-item {{ $currentStep === 4 ? 'active' : '' }} {{ $currentStep > 4 ? 'completed' : '' }}">
                    Dokumen Pendukung
                </li>
                <li class="step-item {{ $currentStep === 5 ? 'active' : '' }}">
                    Konfirmasi Usulan
                </li>
            </ul>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <div class="d-flex">
                
                <div>
                    <h4 class="alert-title">Terdapat kesalahan pada input form:</h4>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form wire:submit.prevent="save" novalidate>
        <!-- Step Content -->
        @if ($currentStep === 1)
            @include('livewire.community-service.proposal.steps.identitas-usulan')
        @elseif ($currentStep === 2)
            @include('livewire.community-service.proposal.steps.substansi-usulan')
        @elseif ($currentStep === 3)
            @include('livewire.community-service.proposal.steps.rab')
        @elseif ($currentStep === 4)
            @include('livewire.community-service.proposal.steps.dokumen-pendukung')
        @elseif ($currentStep === 5)
            @include('livewire.community-service.proposal.steps.konfirmasi')
        @endif

        <x-tabler.alert />
        <!-- Navigation Buttons -->
        <div class="d-flex justify-content-between mt-3 gap-2">
            <div>
                @if ($currentStep > 1)
                    <button type="button" wire:click="previousStep" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                        <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                        Sebelumnya
                    </button>
                @else
                    <a href="{{ route('research.proposal.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="ti ti-x me-1" aria-hidden="true"></i>
                        <i class="ti ti-x me-1" aria-hidden="true"></i>
                        Batal
                    </a>
                @endif
            </div>

            <div>
                @if ($currentStep < 5)
                    <button type="button" wire:click="saveDraft" class="btn btn-outline-info d-flex align-items-center gap-2"
                        wire:loading.attr="disabled" wire:target="saveDraft">
                        <span wire:loading.remove wire:target="saveDraft">
                            <i class="ti ti-save me-1" aria-hidden="true"></i>
                            Simpan Draft
                        </span>
                        <span wire:loading wire:target="saveDraft">
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Menyimpan...
                        </span>
                    </button>
                    <button type="button" wire:click="nextStep" class="btn btn-primary d-flex align-items-center gap-2">
                        <i class="ti ti-arrow-right me-1" aria-hidden="true"></i>
                        <i class="ti ti-arrow-right me-1" aria-hidden="true"></i>
                        Selanjutnya
                    </button>
                @else
                    <button type="button" wire:click="saveDraft" class="btn btn-outline-info d-flex align-items-center gap-2"
                        wire:loading.attr="disabled" wire:target="saveDraft">
                        <span wire:loading.remove wire:target="saveDraft">
                            <i class="ti ti-save me-1" aria-hidden="true"></i>
                            Simpan Draft
                        </span>
                        <span wire:loading wire:target="saveDraft">
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Menyimpan...
                        </span>
                    </button>
                    <button type="submit" class="btn btn-success d-flex align-items-center gap-2">
                        <span class="spinner-border spinner-border-sm me-2" wire:loading role="status"
                            aria-hidden="true"></span>
                        <i class="ti ti-save me-1" aria-hidden="true"></i>
                        
                        <span wire:loading.remove><i class="ti ti-save me-1" aria-hidden="true"></i>
Simpan Proposal</span>
                        <span wire:loading>Menyimpan...</span>
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>
