<x-layouts.auth>
    <div class="py-4 container-tight">
        <div class="mb-4 text-center">
            <a href="." class="navbar-brand navbar-brand-autodark">
                <img src="/logo.png" alt="Logo" width="100" height="100">
            </a>
        </div>
        <div class="card card-md">
            <div class="card-body">
                <h2 class="mb-4 text-center h2">{{ __('Verifikasi Email') }}</h2>

                <div class="mb-3 text-secondary text-center">
                    {{ __('Silakan verifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirimkan kepada Anda.') }}
                </div>

                @if (session('status') == 'verification-link-sent')
                    <div class="mb-3 alert alert-success" role="alert">
                        {{ __('Tautan verifikasi baru telah dikirim ke alamat email yang Anda berikan saat pendaftaran.') }}
                    </div>
                @endif

                <div class="d-flex flex-column gap-3">
                    <button wire:click="sendVerification" class="w-100 btn btn-primary d-flex align-items-center gap-2 justify-content-center d-flex align-items-center gap-2">
                        <i class="ti ti-mail me-1" aria-hidden="true"></i>
                        {{ __('Kirim ulang email verifikasi') }}
                    </button>

                    <a wire:click="logout" class="text-secondary text-center btn btn-link d-flex align-items-center gap-2 justify-content-center d-flex align-items-center gap-2">
                        <i class="ti ti-logout me-1" aria-hidden="true"></i>
                        {{ __('Keluar') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.auth>
