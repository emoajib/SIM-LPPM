<div>
    <div class="row row-cards">
        @forelse($manualBooks as $book)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar avatar-lg bg-primary text-white">
                                    @include('components.layouts.partials.menu.icon', ['name' => 'file-text', 'class' => 'icon icon-lg'])
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
                                @php $isImage = $book['mimeType'] && str_starts_with($book['mimeType'], 'image/'); @endphp
                                @if($isImage)
                                    <a href="{{ $book['downloadUrl'] }}" class="btn btn-primary w-100 mb-1" target="_blank">
                                        @include('components.layouts.partials.menu.icon', ['name' => 'download', 'class' => 'icon me-1']) Unduh
                                    </a>
                                    <p class="text-muted small mb-2 text-center">
                                        {{ number_format($book['fileSize'] / 1024, 1) }} KB
                                    </p>
                                    <img src="{{ $book['downloadUrl'] }}?view=1" class="img-fluid rounded" style="max-height:300px">
                                @else
                                    <div class="d-flex gap-2">
                                        <a href="{{ $book['downloadUrl'] }}?view=1" class="btn btn-outline-primary w-50" target="_blank">
                                            @include('components.layouts.partials.menu.icon', ['name' => 'eye', 'class' => 'icon me-1']) Lihat
                                        </a>
                                        <a href="{{ $book['downloadUrl'] }}" class="btn btn-primary w-50" target="_blank">
                                            @include('components.layouts.partials.menu.icon', ['name' => 'download', 'class' => 'icon me-1']) Unduh
                                        </a>
                                    </div>
                                    <p class="text-muted small mt-2 mb-0 text-center">
                                        {{ number_format($book['fileSize'] / 1024, 1) }} KB
                                    </p>
                                @endif
                            @else
                                <div class="alert alert-warning mb-0 py-2 text-center small">
                                    @include('components.layouts.partials.menu.icon', ['name' => 'book-off', 'class' => 'icon me-1'])
                                    File belum tersedia
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
                        @include('components.layouts.partials.menu.icon', ['name' => 'book-off', 'class' => 'icon icon-lg mb-3 text-muted'])
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
