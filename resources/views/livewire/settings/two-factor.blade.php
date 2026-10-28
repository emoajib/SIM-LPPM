<div>
    <x-tabler.alert />

    @if ($twoFactorEnabled)
        <div class="space-y-4">
            <div class="align-items-center mb-4 row">
                <div class="col-auto">
                    <span class="status status-green"></span>
                </div>
                <div class="col">
                    <h3 class="mb-1 card-title">Status: Aktif</h3>
                    <p class="text-muted">Autentikasi dua faktor sudah diaktifkan untuk akun Anda</p>
                </div>
            </div>

            <div class="alert alert-info">
                
                <strong>Keamanan Tinggi:</strong> Dengan autentikasi dua faktor diaktifkan, Anda akan diminta memasukkan
                kode verifikasi dari aplikasi autentikator saat login.
            </div>

            <h3 class="mt-4 card-title">Kode Pemulihan</h3>
            <p class="card-subtitle">
                Simpan kode pemulihan ini di tempat yang aman. Anda dapat menggunakan kode ini untuk mengakses akun jika
                kehilangan perangkat autentikator.
            </p>

            <livewire:settings.two-factor.recovery-codes :$requiresConfirmation />

            <div class="bg-transparent mt-auto card-footer">
                <div class="btn-list">
                    <button type="button" class="btn btn-danger" wire:click="disable" wire:loading.attr="disabled">
                        <span wire:loading.remove>Nonaktifkan 2FA</span>
                        <span wire:loading>Menonaktifkan...</span>
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-4">
            <div class="align-items-center mb-4 row">
                <div class="col-auto">
                    <span class="status status-red"></span>
                </div>
                <div class="col">
                    <h3 class="mb-1 card-title">Status: Tidak Aktif</h3>
                    <p class="text-muted">Autentikasi dua faktor belum diaktifkan untuk akun Anda</p>
                </div>
            </div>

            <div class="alert alert-warning">
                
                <strong>Peringatan Keamanan:</strong> Mengaktifkan autentikasi dua faktor akan meningkatkan keamanan
                akun Anda secara signifikan.
            </div>

            <h3 class="mt-4 card-title">Cara Kerja</h3>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="bg-primary mx-auto mb-3 text-white avatar">
                            
                        </div>
                        <h4 class="card-title">1. Install Aplikasi</h4>
                        <p class="text-muted">Install aplikasi autentikator seperti Google Authenticator atau Authy</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="bg-primary mx-auto mb-3 text-white avatar">
                            
                        </div>
                        <h4 class="card-title">2. Scan Kode QR</h4>
                        <p class="text-muted">Scan kode QR yang ditampilkan atau masukkan kode manual</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="bg-primary mx-auto mb-3 text-white avatar">
                            
                        </div>
                        <h4 class="card-title">3. Verifikasi</h4>
                        <p class="text-muted">Masukkan kode dari aplikasi untuk mengaktifkan 2FA</p>
                    </div>
                </div>
            </div>

            <div class="bg-transparent mt-auto card-footer">
                <div class="btn-list">
                    <button type="button" class="btn btn-primary" wire:click="enable" wire:loading.attr="disabled">
                        <span wire:loading.remove>Aktifkan 2FA</span>
                        <span wire:loading>Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal untuk Setup 2FA akan ditampilkan di sini -->
    @if ($showModal)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $modalConfig['title'] }}</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Content akan diisi oleh komponen children -->
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
