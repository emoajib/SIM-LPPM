<div>
    <div class="row row-cards">
        @forelse($manualBooks as $book)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar avatar-lg bg-primary text-white">
                                    <i class="icon icon-tabler icon-tabler-notebook icon-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <h4 class="card-title mb-1 text-truncate">{{ $book['title'] }}</h4>
                                <p class="text-muted small mb-0">Versi {{ $book['version_number'] }}</p>
                            </div>
                        </div>

                        @if($book['description'])
                            <p class="text-muted small mb-3">{{ $book['description'] }}</p>
                        @endif

                        <div class="mt-auto">
                            @if($book['hasFile'])
                                <div class="d-flex gap-2">
                                    <a href="{{ $book['downloadUrl'] }}" class="btn btn-primary w-100" target="_blank">
                                        <i class="icon icon-tabler icon-tabler-download me-1"></i> Download PDF
                                    </a>
                                </div>
                                <p class="text-muted small mt-2 mb-0 text-center">
                                    {{ number_format($book['fileSize'] / 1024, 1) }} KB
                                </p>
                            @else
                                <div class="alert alert-warning mb-0 py-2 text-center small">
                                    <i class="icon icon-tabler icon-tabler-file-off me-1"></i>
                                    File PDF belum tersedia
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="icon icon-tabler icon-tabler-notebook-off icon-lg mb-3 text-muted"></i>
                        <h4 class="text-muted">Belum Ada Manual Book</h4>
                        <p class="text-muted mb-0">
                            Belum ada panduan yang tersedia untuk role {{ format_role_name($role) }}.
                        </p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>
