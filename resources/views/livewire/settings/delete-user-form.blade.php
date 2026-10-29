<div>
    <form wire:submit.prevent="deleteUser">
        <div class="mb-3">
            <label class="form-label">Kata Sandi</label>
            <div class="input-group" x-data="{ show: false }">
                <input x-bind:type="show ? 'text' : 'password'" class="form-control" wire:model="password" />
                <button type="button" class="btn btn-outline-secondary" @click="show = !show" x-bind:title="show ? 'Sembunyikan' : 'Tampilkan'">
                    <i x-show="!show" class="ti ti-eye"></i>
                    <i x-show="show" class="ti ti-eye-off"></i>
                </button>
            </div>
            @error('password') <span class="text-danger small">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="btn btn-danger">Hapus Akun</button>
    </form>
</div>
