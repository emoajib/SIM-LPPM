<div>
    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">2.1 Kelompok Makro Riset</h3>
        </div>
        <div class="card-body">
            @php $research = $proposal->detailable; @endphp
            <div class="mb-3">
                <label class="form-label">
                    Kelompok Makro Riset
                </label>
                <p class="text-muted">{{ $research?->macroResearchGroup?->name ?? '—' }}</p>
            </div>
        </div>
    </div>

    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">2.2 File Substansi</h3>
        </div>
        <div class="card-body">
            @php $research = $proposal->detailable; @endphp
            <div class="mb-3">
                <label class="form-label">File Substansi</label>
                @if ($research && $research->hasMedia('substance_file'))
                    @php
                        $media = $research->getFirstMedia('substance_file');
                    @endphp
                    <div class="mb-0 alert alert-info">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <x-lucide-file-text class="me-2 text-primary icon" />
                                <strong>{{ $media->name }}</strong>
                                <small class="ms-2 text-muted">({{ $media->human_readable_size }})</small>
                            </div>
                            <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}" target="_blank"
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

    {{-- Section 2.3.1: Luaran Wajib --}}
    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">2.3.1 Luaran Wajib</h3>
        </div>
        @php
            $requiredOutputs = $proposal->outputs->where('category', 'Wajib');
        @endphp
        @if ($requiredOutputs->isEmpty())
            <div class="card-body">
                <p class="text-muted">Belum ada luaran wajib</p>
            </div>
        @else
            @php
                $startYear = (int) ($proposal->start_year ?? date('Y'));
                $duration = (int) ($proposal->duration_in_years ?? 1);
            @endphp
            <div class="card-body">
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
                                <td>{{ $output->description ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        @endif
    </div>

    {{-- Section 2.3.2: Luaran Tambahan --}}
    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">2.3.2 Luaran Tambahan</h3>
        </div>
        @php
            $additionalOutputs = $proposal->outputs->where('category', 'Tambahan');
        @endphp
        @if ($additionalOutputs->isEmpty())
            <div class="card-body">
                <p class="text-muted">Belum ada luaran tambahan</p>
            </div>
        @else
            @php
                $startYear = (int) ($proposal->start_year ?? date('Y'));
                $duration = (int) ($proposal->duration_in_years ?? 1);
            @endphp
            <div class="card-body">
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
                                <td>{{ $output->description ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        @endif
    </div>
</div>
