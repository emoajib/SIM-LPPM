<div>
    <x-tabler.alert />
    <div class="mb-3" x-data="{ show: false }">
        <label class="form-label">Kata Sandi Saat Ini</label>
        <div class="input-group input-group-flat">
            <input :type="show ? 'text' : 'password'" class="form-control @error('current_password') is-invalid @enderror"
                wire:model="current_password" placeholder="Masukkan kata sandi saat ini" autocomplete="current-password" />
            <span class="input-group-text">
                <a href="#" class="link-secondary" title="Tampilkan kata sandi" data-bs-toggle="tooltip" @click.prevent="show = !show">
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                    </svg>
                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 3.145 -3.594 5.27 -4.194" /><path d="M7.5 8l12.01 12.01" /><path d="M3 3l18 18" /></svg>
                </a>
            </span>
            @error('current_password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-3" x-data="{ show: false }">
        <label class="form-label">Kata Sandi Baru</label>
        <div class="input-group input-group-flat">
            <input :type="show ? 'text' : 'password'" class="form-control @error('password') is-invalid @enderror" wire:model="password"
                placeholder="Masukkan kata sandi baru" autocomplete="new-password" />
            <span class="input-group-text">
                <a href="#" class="link-secondary" title="Tampilkan kata sandi" data-bs-toggle="tooltip" @click.prevent="show = !show">
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                    </svg>
                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 3.145 -3.594 5.27 -4.194" /><path d="M7.5 8l12.01 12.01" /><path d="M3 3l18 18" /></svg>
                </a>
            </span>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-3" x-data="{ show: false }">
        <label class="form-label"><i class="ti ti-check me-1" aria-hidden="true"></i>
Konfirmasi Kata Sandi</label>
        <div class="input-group input-group-flat">
            <input :type="show ? 'text' : 'password'" class="form-control @error('password_confirmation') is-invalid @enderror"
                wire:model="password_confirmation" placeholder="Konfirmasi kata sandi baru"
                autocomplete="new-password" />
            <span class="input-group-text">
                <a href="#" class="link-secondary" title="Tampilkan kata sandi" data-bs-toggle="tooltip" @click.prevent="show = !show">
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                    </svg>
                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 3.145 -3.594 5.27 -4.194" /><path d="M7.5 8l12.01 12.01" /><path d="M3 3l18 18" /></svg>
                </a>
            </span>
            @error('password_confirmation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="bg-transparent mt-auto card-footer">
        <div class="justify-content-end btn-list">
            <button type="button" class="btn d-flex align-items-center gap-2" wire:click="resetForm">
                <i class="ti ti-x me-1" aria-hidden="true"></i>
                Batal
            </button>
            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2" wire:click="updatePassword" wire:loading.attr="disabled">
                <i class="ti ti-save me-1" aria-hidden="true"></i>
                <span wire:loading.remove><i class="ti ti-save me-1" aria-hidden="true"></i>
Simpan Kata Sandi</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
    </div>

    @session('status')
        <div class="mt-3 alert alert-success">
            Kata sandi berhasil diperbarui.
        </div>
    @endsession

    <div class="mt-3 alert alert-info">
        <h4 class="alert-title">Tips Keamanan Kata Sandi</h4>
        <div class="text-muted">
            Gunakan minimal 8 karakter dengan kombinasi huruf besar, huruf kecil, angka, dan simbol untuk keamanan
            maksimal.
        </div>
    </div>
</div>
