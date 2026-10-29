<div>
    <x-slot:title>Tambah Pengguna</x-slot:title>
    <x-slot:pageTitle>Tambah Pengguna Baru</x-slot:pageTitle>
    <x-slot:pageSubtitle>Tambahkan pengguna baru ke sistem dengan informasi lengkap.</x-slot:pageSubtitle>
    <x-slot:pageActions>
        <a href="{{ route('users.index') }}" class="btn-outline-secondary btn" wire:navigate.hover>
            Kembali ke daftar pengguna
        </a>
    </x-slot:pageActions>

    <x-tabler.alert />

    <form wire:submit.prevent="save" class="card card-stacked" novalidate>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="create-name">
                            Nama <span class="text-danger">*</span>
                        </label>
                        <input id="create-name" type="text" class="form-control @error('name') is-invalid @enderror"
                            wire:model="name" autocomplete="name" placeholder="Masukkan nama lengkap" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="title-prefix">Gelar Depan</label>
                        <input id="title-prefix" type="text" class="form-control @error('title_prefix') is-invalid @enderror"
                            wire:model="title_prefix" placeholder="misal: Dr., Prof.">
                        @error('title_prefix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="title-suffix">Gelar Belakang</label>
                        <input id="title-suffix" type="text" class="form-control @error('title_suffix') is-invalid @enderror"
                            wire:model="title_suffix" placeholder="misal: S.Kom., M.Kom.">
                        @error('title_suffix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="create-username">
                            Username
                        </label>
                        <input id="create-username" type="text" class="form-control @error('username') is-invalid @enderror"
                            wire:model="username" autocomplete="username" placeholder="Masukkan username">
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="create-email">
                            Alamat email <span class="text-danger">*</span>
                        </label>
                        <input id="create-email" type="email"
                            class="form-control @error('email') is-invalid @enderror" wire:model="email"
                            autocomplete="email" placeholder="nama@contoh.com" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="create-password">
                            Kata sandi <span class="text-danger">*</span>
                        </label>
                        <div class="input-group" x-data="{ show: false }">
                            <input id="create-password" x-bind:type="show ? 'text' : 'password'"
                                class="form-control @error('password') is-invalid @enderror" wire:model="password"
                                autocomplete="new-password" placeholder="Masukkan kata sandi yang kuat" required>
                            <button type="button" class="btn btn-outline-secondary" @click="show = !show">
                                <i x-show="!show" class="ti ti-eye"></i>
                                <i x-show="show" class="ti ti-eye-off"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="create-password-confirmation">
                            Konfirmasi kata sandi <span class="text-danger">*</span>
                        </label>
                        <div class="input-group" x-data="{ show: false }">
                            <input id="create-password-confirmation" x-bind:type="show ? 'text' : 'password'"
                                class="form-control @error('password_confirmation') is-invalid @enderror"
                                wire:model="password_confirmation" autocomplete="new-password"
                                placeholder="Ulangi kata sandi" required>
                            <button type="button" class="btn btn-outline-secondary" @click="show = !show">
                                <i x-show="!show" class="ti ti-eye"></i>
                                <i x-show="show" class="ti ti-eye-off"></i>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-12">
                    <div class="mb-3">
                        <label class="form-label">
                            Peran <span class="text-muted">(opsional)</span>
                        </label>
                        <div class="row row-cards">
                            @foreach ($roleOptions as $option)
                                <div class="col-sm-6 col-lg-4">
                                    <label class="form-selectgroup-item">
                                        <input type="checkbox" name="role" value="{{ $option['value'] }}"
                                            class="form-selectgroup-input" wire:model="selectedRoles"
                                            @if (in_array($option['value'], $selectedRoles)) checked @endif>
                                        <span class="form-selectgroup-label">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                class="me-1 icon">
                                                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                                            </svg>
                                            {{ $option['label'] }}
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('selectedRoles')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-12">
                    <hr class="my-4">
                    <h3 class="mb-4">Informasi Identitas</h3>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="identity-id">
                            ID Identitas <span class="text-danger">*</span>
                        </label>
                        <input id="identity-id" type="text"
                            class="form-control @error('identity_id') is-invalid @enderror" wire:model="identity_id"
                            placeholder="mis., NIP, NIDN, atau ID Karyawan" required>
                        @error('identity_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="birthplace">
                            Tempat Lahir <span class="text-muted">(opsional)</span>
                        </label>
                        <input id="birthplace" type="text"
                            class="form-control @error('birthplace') is-invalid @enderror" wire:model="birthplace"
                            placeholder="Contoh: Kota, Provinsi">
                        @error('birthplace')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="birthdate">
                            Tanggal Lahir <span class="text-muted">(opsional)</span>
                        </label>
                        <input id="birthdate" type="date"
                            class="form-control @error('birthdate') is-invalid @enderror" wire:model="birthdate">
                        @error('birthdate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="address">
                            Alamat <span class="text-muted">(opsional)</span>
                        </label>
                        <textarea id="address" class="form-control @error('address') is-invalid @enderror" wire:model="address"
                            rows="3" placeholder="Masukkan alamat lengkap Anda"></textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label" for="sinta-id">
                            ID SINTA <span class="text-muted">(opsional)</span>
                        </label>
                        <input id="sinta-id" type="text"
                            class="form-control @error('sinta_id') is-invalid @enderror" wire:model="sinta_id"
                            placeholder="mis., 1234567">
                        @error('sinta_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label" for="scopus-id">ID Scopus</label>
                        <input id="scopus-id" type="text" class="form-control @error('scopus_id') is-invalid @enderror" 
                            wire:model="scopus_id" placeholder="Author ID (misal: 572101...)">
                        @error('scopus_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label" for="gs-id">ID Google Scholar</label>
                        <input id="gs-id" type="text" class="form-control @error('google_scholar_id') is-invalid @enderror" 
                            wire:model="google_scholar_id" placeholder="User ID">
                        @error('google_scholar_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label" for="wos-id">ID Web of Science</label>
                        <input id="wos-id" type="text" class="form-control @error('wos_id') is-invalid @enderror" 
                            wire:model="wos_id" placeholder="Researcher ID">
                        @error('wos_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="scopus-h">Scopus H-Index</label>
                        <input id="scopus-h" type="number" class="form-control @error('scopus_h_index') is-invalid @enderror" 
                            wire:model="scopus_h_index" min="0">
                        @error('scopus_h_index')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="gs-h">GS H-Index</label>
                        <input id="gs-h" type="number" class="form-control @error('gs_h_index') is-invalid @enderror" 
                            wire:model="gs_h_index" min="0">
                        @error('gs_h_index')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="wos-h">WoS H-Index</label>
                        <input id="wos-h" type="number" class="form-control @error('wos_h_index') is-invalid @enderror" 
                            wire:model="wos_h_index" min="0">
                        @error('wos_h_index')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="identity-type">
                            Tipe Identitas <span class="text-danger">*</span>
                        </label>
                        <select id="identity-type" class="form-select @error('type') is-invalid @enderror"
                            wire:model="type" required>
                            <option value="">Pilih tipe identitas...</option>
                            <option value="dosen">Dosen</option>
                            <option value="mahasiswa">Mahasiswa</option>
                            <option value="reviewer">Reviewer</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="institution">
                            Institusi <span class="{{ $this->isExempt ? 'd-none' : 'text-danger' }}">*</span>
                        </label>
                        <div wire:ignore>
                            <select id="institution" class="form-select @error('institution_id') is-invalid @enderror"
                                wire:model.live="institution_id" 
                                x-data="tomSelectWithCreate"
                                placeholder="Pilih institusi atau ketik baru...">
                                <option value="">Pilih institusi... (Bisa ketik nama baru untuk Reviewer)</option>
                                @foreach ($institutionOptions as $option)
                                    <option value="{{ $option['value'] }}" @selected($option['value'] == '1')>
                                        {{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('institution_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Hybrid institution handles this automatically via institution_id input --}}

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="faculty">
                            Fakultas <span class="{{ $this->isExempt ? 'd-none' : 'text-danger' }}">*</span>
                        </label>
                        <select id="faculty" class="form-select @error('faculty_id') is-invalid @enderror"
                            wire:model.live="faculty_id" @disabled(empty($facultyOptions))>
                            <option value="">Pilih fakultas...</option>
                            @foreach ($facultyOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('faculty_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="study-program">
                            Program Studi <span class="{{ $this->isExempt ? 'd-none' : 'text-danger' }}">*</span>
                        </label>
                        <select id="study-program"
                            class="form-select @error('study_program_id') is-invalid @enderror"
                            wire:model="study_program_id" @disabled(empty($studyProgramOptions))>
                            <option value="">Pilih program studi...</option>
                            @foreach ($studyProgramOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('study_program_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between card-footer">
            <a href="{{ route('users.index') }}" class="btn btn-link">
                Batal
            </a>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Buat pengguna</span>
                <span wire:loading>Membuat...</span>
            </button>
        </div>
    </form>
</div>
