@props([
    'title' => 'Dokumen Pendukung',
    'items' => [],
    'sectionNum' => 0,
])

@if(count($items) > 0)
    <div class="section-title">{{ $sectionNum }}. {{ $title }}</div>

    @foreach($items as $index => $item)
        @if($index > 0)<div class="page-break"></div>@endif

        @php
            $media = $item['media'];
            $type = $item['type'] ?? 'other';
        @endphp

        @if($type === 'image')
            @php $imgPath = embed_attachment_image($media); @endphp
            @if($imgPath)
                <div style="margin: 10px 0; text-align: center;">
                    <div style="font-weight: bold; margin-bottom: 8px; font-size: 9pt; text-align: left;">
                        {{ $item['label'] }}
                    </div>
                    <img src="{{ $imgPath }}" class="lampiran-image">
                </div>
            @else
                <div style="padding: 10px; border: 1px dashed red; background: #fff0f0; color: red; font-size: 8pt;">
                    Error: File gambar {{ $media->file_name }} tidak ditemukan di server.
                </div>
            @endif
        @elseif($type === 'pdf')
            <div style="margin: 10px 0; padding: 12px; border: 1px solid #ccc; background: #f8f9fa; border-radius: 3px;">
                <div style="font-weight: bold; font-size: 9pt;">{{ $item['label'] }}</div>
                <div style="font-size: 7.5pt; color: #666; margin-top: 3px;">
                    {{ $media->file_name }} &mdash; {{ number_format($media->size / 1024, 1) }} KB
                </div>
                <div style="font-size: 7pt; color: #888; font-style: italic; margin-top: 5px; padding-top: 5px; border-top: 1px dashed #ddd;">
                    Dokumen terlampir setelah halaman ini.
                </div>
            </div>
        @else
            <div style="margin: 10px 0; padding: 12px; border: 1px dashed #aaa; background: #fcfcfc; border-radius: 3px;">
                <div style="font-weight: bold; font-size: 9pt;">{{ $item['label'] }}</div>
                <div style="font-size: 7.5pt; color: #666; margin-top: 3px;">
                    {{ $media->file_name }} ({{ strtoupper($media->extension) }})
                </div>
                <div style="font-size: 7pt; color: #999; font-style: italic; margin-top: 5px; padding-top: 5px; border-top: 1px dashed #eee;">
                    File tidak dapat ditampilkan dalam PDF. Harap buka secara terpisah.
                </div>
            </div>
        @endif
    @endforeach
@endif
