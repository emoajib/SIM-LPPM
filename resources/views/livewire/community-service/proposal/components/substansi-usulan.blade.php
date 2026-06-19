<div>
    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">2.1 Substansi Usulan</h3>
        </div>
        <div class="card-body">
            @php $communityService = $proposal->detailable; @endphp
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Kelompok Makro Riset</label>
                    <p class="text-reset">{{ $communityService?->macroResearchGroup?->name ?? '—' }}</p>
                </div>
                <div class="col-md-12">
                    <label class="form-label">File Substansi</label>
                    @if ($communityService && $communityService->hasMedia('substance_file'))
                        @php
                            $media = $communityService->getFirstMedia('substance_file');
                        @endphp
                        <div class="mb-0 alert alert-info">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <x-lucide-file-text class="me-2 text-primary icon" />
                                    <strong>{{ $media->name }}</strong>
                                    <small class="ms-2 text-muted">({{ $media->human_readable_size }})</small>
                                </div>
                                <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                    download="{{ $media->file_name ?? $media->name ?? 'download' }}" target="_blank"
                                    class="btn-outline-primary btn btn-sm">
                                    <x-lucide-download class="icon" />
                                    Download
                                </a>
                            </div>
                        </div>
                    @else
                        <p class="text-muted text-reset">Tidak ada file</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Section 2.2: Luaran Target Capaian --}}
    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">2.2 Luaran Target Capaian</h3>
        </div>
        <div class="card-body">
            <h5 class="mb-2">2.2.1 Luaran Wajib</h5>
            @php
                $requiredOutputs = $proposal->outputs->where('category', 'Wajib');
                $startYear = (int) ($proposal->start_year ?? date('Y'));
                $duration = (int) ($proposal->duration_in_years ?? 1);
            @endphp
            @if ($requiredOutputs->isEmpty())
                <p class="text-muted small">Belum ada luaran wajib</p>
            @else
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-vcenter table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Tahun Ke-</th>
                                <th>Kelompok</th>
                                <th>Luaran</th>
                                <th>Status</th>
                                <th>Keterangan (URL)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requiredOutputs as $output)
                                @php
                                    $outputYear = $output->output_year ?? 1;
                                    $displayYear = $startYear + $outputYear - 1;
                                @endphp
                                <tr>
                                    <td>{{ $outputYear }} ({{ $displayYear }})</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $output->group)) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $output->type)) }}</td>
                                    <td>{{ $output->target_status }}</td>
                                    <td>{{ $output->description ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <h5 class="mb-2">2.2.2 Luaran Tambahan</h5>
            @php
                $additionalOutputs = $proposal->outputs->where('category', 'Tambahan');
            @endphp
            @if ($additionalOutputs->isEmpty())
                <p class="text-muted small">Belum ada luaran tambahan</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-vcenter table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Tahun Ke-</th>
                                <th>Kelompok</th>
                                <th>Luaran</th>
                                <th>Status</th>
                                <th>Keterangan (URL)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($additionalOutputs as $output)
                                @php
                                    $outputYear = $output->output_year ?? 1;
                                    $displayYear = $startYear + $outputYear - 1;
                                @endphp
                                <tr>
                                    <td>{{ $outputYear }} ({{ $displayYear }})</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $output->group)) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $output->type)) }}</td>
                                    <td>{{ $output->target_status }}</td>
                                    <td>{{ $output->description ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
