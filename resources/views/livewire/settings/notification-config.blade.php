<div>
    <x-slot:title>Konfigurasi Notifikasi</x-slot:title>
    <x-slot:pageTitle>Konfigurasi Notifikasi</x-slot:pageTitle>

    @if($message)
        <div class="alert alert-info">{{ $message }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">SMTP Email</h3></div>
        <div class="card-body">
            <p class="text-muted small mb-3">Gunakan SMTP kampus (host: mail.itsnu.ac.id, port 465/SSL atau 587/TLS) atau Gmail (smtp.gmail.com, port 587/TLS, aktifkan App Password). Username = email pengirim, password = kata sandi email/App Password.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Host</label>
                    <input type="text" class="form-control" wire:model="mailHost" placeholder="smtp.gmail.com / smtp.itsnu.ac.id">
                    @error('mailHost') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Port</label>
                    <input type="number" class="form-control" wire:model="mailPort">
                    @error('mailPort') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Enkripsi</label>
                    <select class="form-select" wire:model="mailEncryption">
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                        <option value="">None</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" wire:model="mailUsername">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" wire:model="mailPassword" placeholder="kosongkan jika tidak diubah">
                </div>
                <div class="col-md-6">
                    <label class="form-label"><i class="ti ti-plus me-1" aria-hidden="true"></i>
From Address</label>
                    <input type="email" class="form-control" wire:model="mailFromAddress">
                    @error('mailFromAddress') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">From Name</label>
                    <input type="text" class="form-control" wire:model="mailFromName">
                    @error('mailFromName') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">WhatsApp</h3></div>
        <div class="card-body">
            <p class="text-muted small mb-3"><i class="ti ti-send me-1" aria-hidden="true"></i>
Dapatkan API URL & Key dari provider WA gateway (mis. Fonnte/Wablas). Sender ID = nomor WA pengirim terdaftar. Pastikan nomor tidak spam untuk hindari banned.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">API URL</label>
                    <input type="text" class="form-control" wire:model="waApiUrl" placeholder="https://api.whatsapp.../send">
                </div>
                <div class="col-md-6">
                    <label class="form-label">API Key</label>
                    <input type="password" class="form-control" wire:model="waApiKey" placeholder="kosongkan jika tidak diubah">
                </div>
                <div class="col-md-6">
                    <label class="form-label"><i class="ti ti-send me-1" aria-hidden="true"></i>
Sender ID</label>
                    <input type="text" class="form-control" wire:model="waSenderId">
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <button class="btn btn-primary d-flex align-items-center gap-2" wire:click="save">
            <i class="ti ti-save me-1" aria-hidden="true"></i>
            Simpan Konfigurasi
        </button>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Test Email</h3></div>
        <div class="card-body">
            <p class="text-muted small mb-2"><i class="ti ti-login me-1" aria-hidden="true"></i>
Masukkan email tujuan untuk menguji pengiriman. Pastikan konfigurasi SMTP sudah disimpan terlebih dahulu. Gunakan email aktif (Gmail/ac.id) untuk verifikasi.</p>
            <div class="row g-2">
                <div class="col-md-8">
                    <input type="email" class="form-control" wire:model="testEmail" placeholder="email tujuan test">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-primary w-100 d-flex align-items-center gap-2" wire:click="sendTest">
                        <i class="ti ti-send me-1" aria-hidden="true"></i>
                        Kirim Test
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">Test WhatsApp</h3></div>
        <div class="card-body">
            <p class="text-muted small mb-2"><i class="ti ti-login me-1" aria-hidden="true"></i>
Masukkan nomor WhatsApp tujuan (format internasional, mis. 628xxx). Pastikan API URL & Key sudah diisi.</p>
            <div class="row g-2">
                <div class="col-md-8">
                    <input type="text" class="form-control" wire:model="testPhone" placeholder="628xxxxxxxxxx">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-success w-100 d-flex align-items-center gap-2" wire:click="sendTestWa">
                        <i class="ti ti-phone me-1" aria-hidden="true"></i>
                        Kirim Test
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
