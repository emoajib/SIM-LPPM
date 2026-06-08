<div>
    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">4.1 Mitra Kerjasama</h3>
        </div>
        <div class="card-body">
            @if ($proposal->partners->isEmpty())
                <p class="text-muted">Belum ada mitra yang ditambahkan</p>
            @else
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Nama Mitra</th>
                                <th>Institusi</th>
                                <th>Email</th>
                                <th>Negara</th>
                                <th>Alamat</th>
                                <th>Tipe</th>
                                <th>Surat Kesanggupan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($proposal->partners as $partner)
                                <tr>
                                    <td>
                                        <div class="font-weight-medium">{{ $partner->name }}</div>
                                    </td>
                                    <td>
                                        @if ($partner->institution)
                                            <div class="d-flex align-items-center">
                                                <x-lucide-building class="me-1 text-muted icon" />
                                                {{ $partner->institution }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($partner->email)
                                            <a href="mailto:{{ $partner->email }}" class="text-reset">
                                                <div class="d-flex align-items-center">
                                                    <x-lucide-mail class="me-1 text-muted icon" />
                                                    {{ $partner->email }}
                                                </div>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($partner->country)
                                            <div class="d-flex align-items-center">
                                                <x-lucide-map-pin class="me-1 text-muted icon" />
                                                {{ $partner->country }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($partner->address)
                                            <div class="text-truncate" style="max-width: 200px;"
                                                title="{{ $partner->address }}">
                                                {{ $partner->address }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-tabler.badge color="blue">
                                            {{ $partner->type ?? 'External' }}
                                        </x-tabler.badge>
                                    </td>
                                    <td>
                                        {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                                        @if ($partner->hasCommitmentForProposal($proposal->id))
                                            @php $media = $partner->getCommitmentMediaForProposal($proposal->id); @endphp
                                            @if ($media)
                                                <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                                    download="{{ $media->file_name ?? $media->name ?? 'download' }}"
                                                    target="_blank" class="btn btn-sm btn-primary">
                                                    <x-lucide-download class="icon" />
                                                    Unduh
                                                </a>
                                            @endif
                                        @else
                                            <x-tabler.badge color="yellow">
                                                <x-lucide-file-x class="me-1 icon" />
                                                Tidak Ada
                                            </x-tabler.badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- 4.2 Lembar Pengesahan -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">4.2 Lembar Pengesahan (Dokumen Luar)</h3>
        </div>
        <div class="card-body">
            @if ($proposal->detailable && $proposal->detailable->hasMedia('approval_file'))
                <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                    <div class="d-flex align-items-center">
                        <x-lucide-file-check class="icon text-success me-3" size="32" />
                        <div>
                            <div class="font-weight-bold">File Lembar Pengesahan Basah</div>
                            <div class="text-muted small">
                                Berhasil diunggah pada {{ $proposal->detailable->getFirstMedia('approval_file')->created_at->format('d M Y H:i') }}
                            </div>
                        </div>
                    </div>
                    @php $media = $proposal->detailable->getFirstMedia('approval_file'); @endphp
                            <a data-navigate-ignore="true" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
                                download="{{ $media->file_name ?? $media->name ?? 'download' }}" target="_blank" class="btn btn-primary">
                        <x-lucide-external-link class="icon me-1" />
                        Lihat Dokumen
                    </a>
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <x-lucide-file-x class="icon mb-2 opacity-50" size="48" />
                    <p>Belum ada lembar pengesahan basah (scan) yang diunggah.</p>
                </div>
            @endif
        </div>
    </div>
</div>
