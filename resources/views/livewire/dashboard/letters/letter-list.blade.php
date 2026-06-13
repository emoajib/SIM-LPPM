<div>
    @if($isActive)
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-transparent border-0 py-3">
            <h3 class="card-title">
                <i class="ti ti-mail-opened me-2"></i> Dokumen Persuratan Terkait
            </h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Nomor & Jenis Surat</th>
                        <th>Status</th>
                        <th>Tanda Tangan</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($letters as $letter)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $letter->letter_number ?? 'Sedang Diproses' }}</div>
                            <div class="text-muted small">{{ $letter->letterType->name }}</div>
                        </td>
                        <td>
                            <span class="badge bg-{{ \App\Models\Letter::statusColor($letter->status) }}-lt">
                                {{ \App\Models\Letter::statusLabel($letter->status) }}
                            </span>
                        </td>
                        <td>
                            <span class="text-muted small">
                                {{ $letter->signature_mode === 'tte' ? 'Digital (Barcode)' : 'Basah (Pena)' }}
                            </span>
                        </td>
                        <td>
                            @if(in_array($letter->status, ['published', 'ready_to_print']))
                            <a href="{{ route('letter.download', $letter->id) }}" target="_blank" class="btn btn-sm btn-ghost-primary">
                                <i class="ti ti-download"></i> Unduh PDF
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            Belum ada surat yang diajukan untuk usulan ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
