@php
    $outputTypeGroup = strtolower(($output->type ?? '').' '.($output->group ?? ''));
    $isVideo = str_contains($outputTypeGroup, 'video');
    $isMedia = str_contains($outputTypeGroup, 'media');
    $linkUrl = $rowMandatoryOutput?->video_url
        ?? $rowMandatoryOutput?->media_url
        ?? $rowMandatoryOutput?->article_url
        ?? $rowMandatoryOutput?->journal_url;
    $linkLabel = $isVideo ? 'Tonton Video' : ($isMedia ? 'Lihat Berita' : 'Lihat Link');
@endphp
@if ($rowMandatoryOutput && $rowMandatoryOutput->hasMedia('journal_article'))
    @php
        $media = $rowMandatoryOutput->getFirstMedia('journal_article');
    @endphp
    <a data-navigate-ignore="true"
        href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}"
        target="_blank" class="btn btn-sm btn-success">
        <x-lucide-file-check class="icon icon-sm" />
        Lihat Dokumen
    </a>
@elseif (! empty($linkUrl))
    <div class="d-flex flex-column gap-1">
        <a href="{{ $linkUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-danger">
            <x-lucide-play class="icon icon-sm" />
            {{ $linkLabel }}
        </a>
        <div class="d-flex align-items-center gap-1">
            <small class="text-muted text-truncate" style="max-width: 180px;" title="{{ $linkUrl }}">{{ \Illuminate\Support\Str::limit($linkUrl, 40) }}</small>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1"
                data-copy-url="{{ $linkUrl }}" title="Salin link"
                onclick="(function(btn){var url=btn.dataset.copyUrl;var done=function(){var t=btn.innerHTML;btn.innerHTML='Tersalin ✓';setTimeout(function(){btn.innerHTML=t;},1500);};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(url).then(done).catch(function(){btn.innerHTML='Gagal';});}else{var ta=document.createElement('textarea');ta.value=url;document.body.appendChild(ta);ta.select();try{document.execCommand('copy');done();}catch(e){btn.innerHTML='Gagal';}document.body.removeChild(ta);}})(this)">
                <x-lucide-copy class="icon icon-sm" />
            </button>
        </div>
    </div>
@else
    <span class="text-muted">
        <x-lucide-file-x class="icon icon-sm" />
        {{ $isVideo ? 'Belum Isi Link' : 'Belum Upload' }}
    </span>
@endif
