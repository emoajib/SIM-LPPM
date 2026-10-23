<div>
    <h3 class="card-title mb-3">
        <x-lucide-volume-2 class="icon me-1" />
        Audio CAPTCHA
    </h3>
    <p class="text-secondary mb-3">
        Atur ketersediaan dan jenis suara untuk soal verifikasi keamanan berbentuk audio.
        Audio hanya tampil di halaman login ketika Turnstile tidak dikonfigurasi.
    </p>

    <div class="list-group list-group-flush">
        <div class="list-group-item px-0">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar bg-blue-lt">
                        <x-lucide-volume-2 class="icon" />
                    </span>
                </div>
                <div class="col">
                    <div class="text-body d-block font-weight-medium">Aktifkan soal audio</div>
                    <div class="text-muted mt-n1">
                        Jika dimatikan, pengguna hanya dapat menyelesaikan soal bergambar.
                    </div>
                </div>
                <div class="col-auto">
                    <label class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" wire:model.live="audioEnabled">
                    </label>
                </div>
            </div>
        </div>

        <div class="list-group-item px-0">
            <div class="text-body d-block font-weight-medium mb-2">Jenis suara</div>
            <div class="row g-2">
                @foreach($voices as $voice)
                    <div class="col-12 col-md-6" wire:key="captcha-voice-{{ $voice['key'] }}">
                        <label
                            class="d-block border rounded-3 p-3 h-100 {{ $audioVoice === $voice['key'] ? 'border-primary bg-blue-lt' : '' }} {{ $voice['available'] ? '' : 'opacity-50' }}">
                            <span class="d-flex align-items-start gap-2">
                                <input type="radio" class="form-check-input mt-1" name="audio_voice"
                                    value="{{ $voice['key'] }}" wire:model.live="audioVoice"
                                    @disabled(! $voice['available'])>
                                <span>
                                    <span class="d-block fw-medium">{{ $voice['label'] }}</span>
                                    <span class="d-block small text-muted">
                                        {{ $voice['locale'] !== '' ? strtoupper($voice['locale']) : '—' }}
                                    </span>
                                    @if(! $voice['available'])
                                        <span class="badge bg-red-lt mt-1">Aset tidak tersedia</span>
                                    @endif
                                </span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
            @error('audioVoice')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @if(config('turnstile.site_key'))
        <div class="alert alert-info mt-3 mb-0">
            Turnstile sedang aktif. Soal audio baru akan tampil di halaman login setelah Turnstile dinonaktifkan.
        </div>
    @else
        <div class="alert alert-secondary mt-3 mb-0">
            Perubahan berlaku pada kunjungan berikutnya ke halaman login. Suara Inggris menggunakan angka dan operator
            dalam bahasa Inggris.
        </div>
    @endif
</div>
