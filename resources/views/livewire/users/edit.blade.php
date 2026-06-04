<div>
    <x-slot:title>Edit Pengguna</x-slot:title>
    <x-slot:pageTitle>Edit Pengguna</x-slot:pageTitle>
    <x-slot:pageSubtitle>Perbarui profil pengguna dan penetapan peran.</x-slot:pageSubtitle>
    <x-slot:pageActions>
        <a href="{{ route('users.show', $this->user) }}" class="btn-outline-secondary btn" wire:navigate.hover>
            Lihat profil
        </a>
    </x-slot:pageActions>

    <x-tabler.alert />

    <form wire:submit.prevent="save" class="card card-stacked" novalidate>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="user-name">
                            Nama <span class="text-danger">*</span>
                        </label>
                        <input id="user-name" type="text" class="form-control @error('name') is-invalid @enderror"
                            wire:model="name" autocomplete="name" placeholder="Masukkan nama lengkap" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="title-prefix">Gelar Depan</label>
                        <input id="title-prefix" type="text"
                            class="form-control @error('title_prefix') is-invalid @enderror" wire:model="title_prefix"
                            placeholder="misal: Dr., Prof.">
                        @error('title_prefix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="title-suffix">Gelar Belakang</label>
                        <input id="title-suffix" type="text"
                            class="form-control @error('title_suffix') is-invalid @enderror" wire:model="title_suffix"
                            placeholder="misal: S.Kom., M.Kom.">
                        @error('title_suffix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="user-username">
                            Username
                        </label>
                        <input id="user-username" type="text"
                            class="form-control @error('username') is-invalid @enderror" wire:model="username"
                            autocomplete="username" placeholder="Masukkan username">
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="user-email">
                            Alamat email <span class="text-danger">*</span>
                        </label>
                        <input id="user-email" type="email" class="form-control @error('email') is-invalid @enderror"
                            wire:model="email" autocomplete="email" placeholder="nama@contoh.com" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-12">
                    <hr class="my-3">
                    <h4 class="card-title text-secondary">Manajemen Password</h4>
                    @if ($this->user->original_password)
                        <div class="alert alert-warning mb-3">
                            <div class="d-flex justify-content-between align-items-center" x-data="{ show: false }">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <strong>Password Asli Tersimpan:</strong>
                                        <div class="d-flex align-items-center">
                                            <code class="text-azure"
                                                x-show="show">{{ $this->user->original_password }}</code>
                                            <span class="text-muted fst-italic" x-show="!show">********</span>
                                        </div>
                                        <button type="button" class="btn btn-icon btn-sm btn-ghost-secondary"
                                            @click="show = !show" title="Toggle visibility">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                stroke-linecap="round" stroke-linejoin="round" x-show="!show">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                                <path
                                                    d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                            </svg>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                stroke-linecap="round" stroke-linejoin="round" x-show="show"
                                                style="display: none;">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" />
                                                <path
                                                    d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 3.145 -3.594 5.27 -4.194" />
                                                <path d="M7.5 8l12.01 12.01" />
                                                <path d="M3 3l18 18" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="text-muted small mt-1">Ini adalah password yang tersimpan saat
                                        pembuatan/import user.</div>
                                </div>
                                <span class="badge bg-yellow-lt">Visible to Admin</span>
                            </div>
                        </div>
                    @endif
                    <div class="alert alert-info d-flex align-items-center justify-content-between">
                        <div>Kosongkan jika tidak ingin mengubah password pengguna.</div>
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="generatePassword">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-wand" width="24"
                                height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M6 21l15 -15l-3 -3l-15 15l3 3"></path>
                                <path d="M15 6l3 3"></path>
                                <path d="M9 3a2 2 0 0 0 2 2a2 2 0 0 0 -2 2a2 2 0 0 0 -2 -2a2 2 0 0 0 2 -2"></path>
                                <path d="M19 13a2 2 0 0 0 2 2a2 2 0 0 0 -2 2a2 2 0 0 0 -2 -2a2 2 0 0 0 2 -2"></path>
                            </svg>
                            Generate Password Standar
                        </button>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3" x-data="{ show: false }">
                        <label class="form-label">Password Baru</label>
                        <div class="input-group input-group-flat">
                            <input :type="show ? 'text' : 'password'"
                                class="form-control @error('password') is-invalid @enderror" wire:model="password"
                                autocomplete="new-password" placeholder="Minimal 8 karakter">
                            <span class="input-group-text">
                                <a href="#" class="link-secondary" title="Tampilkan password" data-bs-toggle="tooltip"
                                    @click.prevent="show = !show">
                                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path
                                            d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                    </svg>
                                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" />
                                        <path
                                            d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 3.145 -3.594 5.27 -4.194" />
                                        <path d="M7.5 8l12.01 12.01" />
                                        <path d="M3 3l18 18" />
                                    </svg>
                                </a>
                            </span>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3" x-data="{ show: false }">
                        <label class="form-label">Konfirmasi Password</label>
                        <div class="input-group input-group-flat">
                            <input :type="show ? 'text' : 'password'" class="form-control"
                                wire:model="password_confirmation" autocomplete="new-password"
                                placeholder="Ulangi password baru">
                            <span class="input-group-text">
                                <a href="#" class="link-secondary" title="Tampilkan password" data-bs-toggle="tooltip"
                                    @click.prevent="show = !show">
                                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path
                                            d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                    </svg>
                                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" />
                                        <path
                                            d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 3.145 -3.594 5.27 -4.194" />
                                        <path d="M7.5 8l12.01 12.01" />
                                        <path d="M3 3l18 18" />
                                    </svg>
                                </a>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="">
                        <label class="form-label">
                            Peran <span class="text-muted">(opsional)</span>
                        </label>
                        <div class="form-selectgroup @error('selectedRoles') is-invalid @enderror">
                            @foreach ($this->roleOptions as $option)
                                <label class="form-selectgroup-item">
                                    <input type="checkbox" name="role" value="{{ $option['value'] }}"
                                        class="form-selectgroup-input" wire:model="selectedRoles" @if (in_array($option['value'], $selectedRoles)) checked @endif>
                                    <span class="form-selectgroup-label">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" class="icon me-1">
                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                                        </svg>
                                        {{ $option['label'] }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedRoles')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-12">
                    <hr class="my-4">
                    <h3>Informasi Identitas</h3>
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
                        <input id="birthdate" type="date" class="form-control @error('birthdate') is-invalid @enderror"
                            wire:model="birthdate">
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
                        <textarea id="address" class="form-control @error('address') is-invalid @enderror"
                            wire:model="address" rows="3" placeholder="Masukkan alamat lengkap Anda"></textarea>
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
                        <input id="sinta-id" type="text" class="form-control @error('sinta_id') is-invalid @enderror"
                            wire:model="sinta_id" placeholder="mis., 1234567">
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
                        <input id="gs-id" type="text"
                            class="form-control @error('google_scholar_id') is-invalid @enderror"
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

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="functional-position">Jabatan Fungsional</label>
                        <select id="functional-position" class="form-select @error('functional_position') is-invalid @enderror"
                            wire:model="functional_position">
                            <option value="">Pilih jabatan...</option>
                            <option value="Tenaga Pengajar">Tenaga Pengajar</option>
                            <option value="Asisten Ahli">Asisten Ahli</option>
                            <option value="Lektor">Lektor</option>
                            <option value="Lektor Kepala">Lektor Kepala</option>
                            <option value="Guru Besar">Guru Besar</option>
                        </select>
                        @error('functional_position')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="sinta-score">Skor SINTA Overall (Manual)</label>
                        <input id="sinta-score" type="number" step="0.01"
                            class="form-control @error('sinta_score_v3_overall') is-invalid @enderror"
                            wire:model="sinta_score_v3_overall" placeholder="0.00">
                        <div class="form-text">Gunakan titik (.) untuk desimal. Data ini digunakan untuk pengecekan
                            kelayakan usulan.</div>
                        @error('sinta_score_v3_overall')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="scopus-h">Scopus H-Index</label>
                        <input id="scopus-h" type="number"
                            class="form-control @error('scopus_h_index') is-invalid @enderror"
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
                                wire:model.live="institution_id" x-data="tomSelectWithCreate"
                                placeholder="Pilih institusi atau ketik baru...">
                                <option value="">Pilih institusi... (Bisa ketik nama baru untuk Reviewer)</option>
                                @foreach ($this->institutionOptions as $option)
                                    <option value="{{ $option['value'] }}">
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('institution_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Hybrid institution handles this automatically via institution_id input --}}
                @if($this->user->identity?->institution_name)
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Institusi Terdaftar (Manual)</label>
                            <input type="text" class="form-control" value="{{ $this->user->identity->institution_name }}"
                                disabled>
                            <div class="form-text">Reviewer ini terdaftar dengan institusi luar yang belum diverifikasi.
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="faculty">
                            Fakultas <span class="{{ $this->isExempt ? 'd-none' : 'text-danger' }}">*</span>
                        </label>
                        <select id="faculty" class="form-select @error('faculty_id') is-invalid @enderror"
                            wire:model.live="faculty_id" @disabled(empty($this->facultyOptions))>
                            <option value="">Pilih fakultas...</option>
                            @foreach ($this->facultyOptions as $option)
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
                        <select id="study-program" class="form-select @error('study_program_id') is-invalid @enderror"
                            wire:model="study_program_id" @disabled(empty($this->studyProgramOptions))>
                            <option value="">Pilih program studi...</option>
                            @foreach ($this->studyProgramOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('study_program_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="science-cluster">Rumpun Ilmu</label>
                        <select id="science-cluster" class="form-select @error('science_cluster_id') is-invalid @enderror"
                            wire:model="science_cluster_id">
                            <option value="">Pilih rumpun ilmu...</option>
                            @foreach ($this->scienceClusterOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('science_cluster_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="d-block form-label">Verifikasi email</label>
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="emailVerified">
                            <span class="form-check-label">Tandai email sebagai terverifikasi</span>
                        </label>
                        @error('emailVerified')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <p class="text-secondary mt-2">
                            Alihkan untuk mengatur status verifikasi secara manual.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between card-footer" x-data="{ show: false }"
            x-on:user-updated.window="console.log('updated');show = true; setTimeout(() => show = false, 5000);">
            <a href="{{ route('users.show', $this->user) }}" class="btn btn-link" wire:navigate.hover>
                Batal
            </a>
            <div x-show="show" class="text-green fw-bold">
                <x-lucide-check-circle class="icon me-1" />
                Pengguna telah diperbarui.
            </div>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Simpan perubahan</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
    </form>
</div>