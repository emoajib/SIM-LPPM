<div class="py-4 container-tight">
    <div class="mb-4 text-center">
        <a href="." class="navbar-brand navbar-brand-autodark">
            <img src="/logo.png" alt="Logo" width="100" height="100">
        </a>
    </div>
    <div class="card card-md shadow-sm border-0">
        <div class="card-body p-5">
            <h2 class="mb-4 text-center h2 fw-bold text-primary">{{ __('Lupa kata sandi') }}</h2>
            <p class="mb-4 text-secondary text-center small">
                {{ __('Masukkan email Anda untuk menerima tautan reset kata sandi') }}
            </p>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" wire:submit.prevent="sendPasswordResetLink" autocomplete="off" novalidate>
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('Alamat Email') }}</label>
                    <input type="email" wire:model="email" class="form-control form-control-lg @error('email') is-invalid @enderror" 
                        placeholder="email@contoh.com" autocomplete="email" required autofocus />
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-footer">
                    <button type="submit" class="w-100 btn btn-primary btn-lg fw-bold shadow-sm d-flex align-items-center gap-2 justify-content-center d-flex align-items-center gap-2" wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <i class="ti ti-send me-1" aria-hidden="true"></i>
                        <span wire:loading.remove>{{ __('Kirim tautan reset kata sandi') }}</span>
                        <span wire:loading>{{ __('Memproses...') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="mt-4 text-center text-secondary small">
        {{ __('Atau, kembali ke') }} <a href="{{ route('login') }}" class="fw-bold text-decoration-none" wire:navigate>{{ __('masuk') }}</a>
    </div>
</div>
