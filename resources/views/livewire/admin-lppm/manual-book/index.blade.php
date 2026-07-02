<div>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0">Daftar Manual Book</h3>
        <div>
            <input type="text" class="form-control" style="max-width:300px" placeholder="Cari manual book..." wire:model.live.debounce.300ms="search">
        </div>
    </div>

    <div class="row row-cards">
        @forelse($manualBooks as $book)
            @php
                $media = $book->getFirstMedia('manual_book_file');
                $isImage = $media && str_starts_with($media->mime_type ?? '', 'image/');
            @endphp
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 me-3">
                                @if($isImage)
                                    <div class="avatar avatar-lg">
                                        <img src="{{ route('media.download', ['media' => $media, 'view' => 1]) }}" class="rounded" style="width:48px;height:48px;object-fit:cover">
                                    </div>
                                @else
                                    <div class="avatar avatar-lg bg-primary text-white">
                                        @include('components.layouts.partials.menu.icon', ['name' => 'file-text', 'class' => 'icon icon-lg'])
                                    </div>
                                @endif
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
                                <div class="d-flex gap-2">
                                    <a href="{{ route('media.download', $media) }}?view=1" class="btn btn-outline-primary w-50" target="_blank">
                                        @include('components.layouts.partials.menu.icon', ['name' => 'eye', 'class' => 'icon me-1']) Lihat
                                    </a>
                                    <a href="{{ route('media.download', $media) }}" class="btn btn-primary w-50" target="_blank">
                                        @include('components.layouts.partials.menu.icon', ['name' => 'download', 'class' => 'icon me-1']) Unduh
                                    </a>
                                </div>
                                <p class="text-muted small mt-2 mb-0 text-center">
                                    {{ number_format($media->size / 1024, 1) }} KB
                                </p>
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
