<div>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0">Daftar Manual Book</h3>
        <div>
            <input type="text" class="form-control" style="max-width:300px" placeholder="Cari manual book..." wire:model.live.debounce.300ms="search">
        </div>
    </div>

    <div class="row row-cards">
        @forelse($manualBooks as $book)
            @php $media = $book->getFirstMedia('manual_book_file'); @endphp
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
                                <h4 class="card-title mb-1 text-truncate">{{ $book->title }}</h4>
                                <p class="text-muted small mb-0">Versi {{ $book->version_number }}</p>
                            </div>
                        </div>

                        <div class="mb-2">
                            @foreach($book->assigned_roles as $role)
                                <span class="badge bg-secondary me-1">{{ format_role_name($role) }}</span>
                            @endforeach
                        </div>

                        <div class="mt-auto">
                            @if($media)
                                <a href="{{ route('media.download', $media) }}" class="btn btn-primary w-100" target="_blank">
                                    @include('components.layouts.partials.menu.icon', ['name' => 'download', 'class' => 'icon me-1'])
                                    Download ({{ number_format($media->size / 1024, 1) }} KB)
                                </a>
                            @else
                                <div class="alert alert-warning mb-0 py-2 text-center small">
                                    @include('components.layouts.partials.menu.icon', ['name' => 'book-off', 'class' => 'icon me-1'])
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
                        @include('components.layouts.partials.menu.icon', ['name' => 'book-off', 'class' => 'icon icon-lg mb-3 text-muted'])
                        <h4 class="text-muted">Belum Ada Manual Book</h4>
                        <p class="text-muted mb-0">Belum ada manual book yang tersedia.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if($manualBooks->hasPages())
        <div class="mt-3">
            {{ $manualBooks->links() }}
        </div>
    @endif
</div>
