@props([
    'id',
    'src' => null,
    'alt' => '',
    'title' => null,
    'description' => null,
    'wireIgnore' => true,
    'zoomable' => true,
    'downloadable' => false,
    'downloadText' => 'Download',
    'closable' => true,
    'showCounter' => true,
])

@php
    $imageId = $id . '-image';
@endphp

<x-tabler.modal
    :id="$id"
    :title="$title"
    :wire-ignore="$wireIgnore"
    size="xl"
    centered="true"
    :close-button="$closable"
    scrollable="true"
    class="modal-image-preview"
>
    <x-slot name="body">
        <div class="image-preview-container" x-data="imagePreview('{{ $src }}')" x-init="init()">
            @if($description)
                <div class="image-description mb-3">
                    <p class="text-muted mb-0">{{ $description }}</p>
                </div>
            @endif

            <div class="image-controls d-flex justify-content-between align-items-center mb-3">
                <div class="image-counter">
                    @if($showCounter)
                        <small class="text-muted">
                            <span x-text="currentIndex + 1"></span> of <span x-text="totalImages"></span>
                        </small>
                    @endif
                </div>

                @if($zoomable || $downloadable)
                    <div class="image-actions d-flex gap-2">
                        @if($zoomable)
                            <button type="button" class="btn btn-sm btn-light" title="Perkecil" aria-label="Perkecil" @click="zoomOut">
                                
                            </button>
                            <button type="button" class="btn btn-sm btn-light" title="Reset zoom" aria-label="Reset zoom" @click="resetZoom">
                                100%
                            </button>
                            <button type="button" class="btn btn-sm btn-light" title="Perbesar" aria-label="Perbesar" @click="zoomIn">
                                
                            </button>
                        @endif

                        @if($downloadable)
                            <button type="button" class="btn btn-sm btn-light" @click="downloadImage">
                                
                                {{ $downloadText }}
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            <div class="image-viewer-container">
                <div class="image-wrapper">
                    <img
                        :id="'{{ $imageId }}'"
                        :src="currentImage"
                        :alt="'{{ $alt }}'"
                        :style="`transform: scale(${zoomLevel}); transform-origin: center center;`"
                        :class="{'preview-image': true, 'zoomed': isZoomed}"
                        x-transition
                        @click="if ({{ $zoomable ? 'true' : 'false' }} && event.target === event.currentTarget) zoomToggle()"
                    >
                </div>
            </div>

            {{ $slot }}
        </div>
    </x-slot>
</x-tabler.modal>
