<div>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Jadwal Proposal
                    </h2>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-12">
                    <form wire:submit.prevent="save">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Pengaturan Jadwal Proposal</h3>
                            </div>
                            <div class="card-body">
                                @if (session('success'))
                                    <div class="alert alert-success">
                                        {{ session('success') }}
                                    </div>
                                @endif

                                <!-- Current Year Budget Cap Information -->
                                @php
                                    $currentYear = date('Y');
                                @endphp
                                @if ($this->currentYearBudgetCap)
                                    <div class="alert alert-info mb-4" role="alert">
                                        <div class="d-flex">
                                            <div>
                                                <x-lucide-info class="icon alert-icon" />
                                            </div>
                                            <div>
                                                <h4 class="alert-title">Batasan Anggaran Tahun {{ $currentYear }}</h4>
                                                <div class="text-muted mb-2">
                                                    Berikut adalah batasan maksimal anggaran proposal untuk tahun ini:
                                                </div>
                                                <div class="d-flex flex-wrap gap-3">
                                                    @if ($this->currentYearBudgetCap->research_budget_cap)
                                                        <div>
                                                            <strong>Penelitian:</strong>
                                                            <x-tabler.badge color="success" class="ms-1">
                                                                Rp
                                                                {{ number_format($this->currentYearBudgetCap->research_budget_cap, 0, ',', '.') }}
                                                            </x-tabler.badge>
                                                        </div>
                                                    @endif
                                                    @if ($this->currentYearBudgetCap->community_service_budget_cap)
                                                        <div>
                                                            <strong>Pengabdian Masyarakat:</strong>
                                                            <x-tabler.badge color="primary" class="ms-1">
                                                                Rp
                                                                {{ number_format($this->currentYearBudgetCap->community_service_budget_cap, 0, ',', '.') }}
                                                            </x-tabler.badge>
                                                        </div>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block mt-2">
                                                    <x-lucide-info class="icon icon-sm me-1" />
                                                    Untuk mengubah batasan anggaran, silakan akses <strong>Settings →
                                                        Master Data → Batas Anggaran Tahunan</strong>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-4" role="alert">
                                        <div class="d-flex">
                                            <div>
                                                <x-lucide-alert-triangle class="icon alert-icon" />
                                            </div>
                                            <div>
                                                <h4 class="alert-title">Belum Ada Batasan Anggaran</h4>
                                                <div class="text-muted">
                                                    Batasan anggaran untuk tahun {{ $currentYear }} belum diatur.
                                                    Silakan akses <strong>Settings → Master Data → Batas Anggaran
                                                        Tahunan</strong> untuk mengatur batasan anggaran.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-md-6">
                                        <h4 class="mb-3">Penelitian</h4>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Mulai</label>
                                            <input type="datetime-local" class="form-control" wire:model="research_start_date">
                                            @error('research_start_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Selesai</label>
                                            <input type="datetime-local" class="form-control" wire:model="research_end_date">
                                            @error('research_end_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <h4 class="mb-3 mt-4">Perbaikan Usulan Penelitian</h4>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Mulai</label>
                                            <input type="datetime-local" class="form-control"
                                                wire:model="research_revision_start_date">
                                            @error('research_revision_start_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Selesai</label>
                                            <input type="datetime-local" class="form-control"
                                                wire:model="research_revision_end_date">
                                            @error('research_revision_end_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <h4 class="mb-3 mt-4">Laporan Akhir Penelitian</h4>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Mulai</label>
                                            <input type="datetime-local" class="form-control"
                                                wire:model="research_final_report_start_date">
                                            @error('research_final_report_start_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Selesai</label>
                                            <input type="datetime-local" class="form-control"
                                                wire:model="research_final_report_end_date">
                                            @error('research_final_report_end_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h4 class="mb-3">Pengabdian Masyarakat</h4>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Mulai</label>
                                            <input type="datetime-local" class="form-control"
                                                wire:model="community_service_start_date">
                                            @error('community_service_start_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Selesai</label>
                                            <input type="datetime-local" class="form-control"
                                                wire:model="community_service_end_date">
                                            @error('community_service_end_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <h4 class="mb-3 mt-4">Perbaikan Usulan Pengabdian Masyarakat</h4>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Mulai</label>
                                            <input type="datetime-local" class="form-control"
                                                wire:model="community_service_revision_start_date">
                                            @error('community_service_revision_start_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Selesai</label>
                                            <input type="datetime-local" class="form-control"
                                                wire:model="community_service_revision_end_date">
                                            @error('community_service_revision_end_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <h4 class="mb-3 mt-4">Laporan Akhir Pengabdian Masyarakat</h4>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Mulai</label>
                                            <input type="datetime-local" class="form-control"
                                                wire:model="community_service_final_report_start_date">
                                            @error('community_service_final_report_start_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal & Jam Selesai</label>
                                            <input type="datetime-local" class="form-control"
                                                wire:model="community_service_final_report_end_date">
                                            @error('community_service_final_report_end_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>