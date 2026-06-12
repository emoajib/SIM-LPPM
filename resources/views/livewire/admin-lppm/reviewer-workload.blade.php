<x-slot:title>Beban Kerja Reviewer</x-slot:title>
<x-slot:pageTitle>Beban Kerja Reviewer</x-slot:pageTitle>
<x-slot:pageSubtitle>Pantau beban kerja dan progres review dari setiap reviewer yang terdaftar.</x-slot:pageSubtitle>

<div>
    <div class="mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <select class="form-select" wire:model.live="yearFilter">
                    <option value="">Semua Tahun</option>
                    @foreach ($this->availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" wire:model.live="semesterFilter">
                    <option value="all">Semua Semester</option>
                    <option value="ganjil">Ganjil</option>
                    <option value="genap">Genap</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-secondary w-100" wire:click="resetFilters">
                    <i class="ti ti-rotate"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <thead>
                    <tr>
                        <th>Nama Reviewer</th>
                        <th>Fakultas</th>
                        <th class="text-center">Total Tugas</th>
                        <th class="text-center">Pending</th>
                        <th class="text-center">Selesai</th>
                        <th>Progres</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->reviewers as $reviewer)
                        @php
                            $total = (int) $reviewer->total_assigned;
                            $completed = (int) $reviewer->completed_count;
                            $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
                        @endphp
                        <tr wire:key="reviewer-{{ $reviewer->id }}">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="me-2 avatar avatar-sm"
                                        style="background-image: url({{ $reviewer->profile_picture }})"></span>
                                    <div>
                                        <div class="fw-bold">{{ $reviewer->name }}</div>
                                        <div class="text-secondary small">{{ $reviewer->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $reviewer->identity?->faculty?->name ?? '—' }}</td>
                            <td class="text-center">
                                <span class="bg-blue-lt badge">{{ $total }}</span>
                            </td>
                            <td class="text-center">
                                <span class="bg-warning-lt badge">{{ $reviewer->pending_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="bg-success-lt badge">{{ $completed }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-2 w-100 progress progress-xs">
                                        <div class="bg-primary progress-bar" x-data
                                            :style="'width: ' + {{ $percentage }} + '%'"></div>
                                    </div>
                                    <span class="small">{{ $percentage }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-center">Tidak ada reviewer terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
