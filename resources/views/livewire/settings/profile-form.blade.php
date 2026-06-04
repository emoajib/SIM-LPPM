<div>
    <h3 class="card-title">Foto Profil</h3>
    <div class="align-items-center mb-4 row">
        <div class="col-auto">
            <!-- Photo Preview -->
            @if ($photo)
                <span class="avatar avatar-xl" style="background-image: url({{ $photo->temporaryUrl() }})"></span>
            @elseif (auth()->user()->profile_picture)
                <span class="avatar avatar-xl" wire:key="avatar-{{ auth()->user()->updated_at }}" style="background-image: url({{ auth()->user()->profile_picture }})"></span>
            @else
                <span class="avatar avatar-xl">
                    {{ auth()->user()->initials() }}
                </span>
            @endif
        </div>
        <div class="col-auto">
            <input type="file" class="d-none" wire:model="photo" x-ref="photo" accept="image/*">

            <button type="button" class="btn btn-primary" x-on:click.prevent="$refs.photo.click()" wire:loading.attr="disabled" wire:target="photo">
                <span wire:loading.remove wire:target="photo">Ubah Foto</span>
                <span wire:loading wire:target="photo">Mengunggah...</span>
            </button>
        </div>
        @if (auth()->user()->getFirstMedia('avatar') || auth()->user()->identity?->profile_picture)
            <div class="col-auto">
                <button type="button" class="btn btn-ghost-danger" wire:click="removeAvatar"
                    wire:confirm="Apakah Anda yakin ingin menghapus foto profil?">
                    Hapus Foto
                </button>
            </div>
        @endif

        <div class="mt-2 col-12">
            @error('photo')
                <span class="text-danger small">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <h3 class="mt-4 card-title">Informasi Dasar</h3>
    <div class="row g-3">
        <div class="col-md">
            <div class="form-label">Nama Lengkap <span class="text-danger">*</span></div>
            <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name"
                placeholder="Masukkan nama lengkap (tanpa gelar)" />
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md">
            <div class="form-label">Email <span class="text-danger">*</span></div>
            <input type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email"
                placeholder="Masukkan email" />
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mt-1 row g-3">
        <div class="col-md-6">
            <div class="form-label">Gelar Depan</div>
            <input type="text" class="form-control @error('title_prefix') is-invalid @enderror"
                wire:model="title_prefix" placeholder="misal: Dr., Prof." />
            @error('title_prefix')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <div class="form-label">Gelar Belakang</div>
            <input type="text" class="form-control @error('title_suffix') is-invalid @enderror"
                wire:model="title_suffix" placeholder="misal: S.Kom., M.Kom." />
            @error('title_suffix')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mt-1 row g-3">
        <div class="col-md">
            <div class="form-label">NIDN/NUPTK <span class="text-danger">*</span></div>
            <input type="text" class="form-control @error('identity_id') is-invalid @enderror" wire:model="identity_id"
                placeholder="Masukkan NIDN/NUPTK" />
            @error('identity_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        {{-- <div class="col-md">
            <div class="form-label">Tipe User <span class="text-danger">*</span></div>
            <div wire:ignore>
                <select class="form-select @error('type') is-invalid @enderror" wire:model="type" x-data="tomSelect"
                    placeholder="Pilih Tipe">
                    <option value="">Pilih Tipe</option>
                    <option value="dosen">Dosen</option>
                    <option value="mahasiswa">Mahasiswa</option>
                </select>
            </div>
            @error('type')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div> --}}
        <div class="col-md">
            <div class="form-label">ID SINTA</div>
            <input type="text" class="form-control @error('sinta_id') is-invalid @enderror" wire:model="sinta_id"
                placeholder="ID SINTA" />
            @error('sinta_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mt-1 row g-3">
        <div class="col-md">
            <div class="form-label">ID Scopus</div>
            <input type="text" class="form-control @error('scopus_id') is-invalid @enderror" wire:model="scopus_id"
                placeholder="Author ID (misal: 572101...)" />
            <small class="text-muted">Gunakan angka Author ID saja.</small>
            @error('scopus_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md">
            <div class="form-label">ID Google Scholar</div>
            <input type="text" class="form-control @error('google_scholar_id') is-invalid @enderror"
                wire:model="google_scholar_id" placeholder="User ID (misal: yW3_...)" />
            <small class="text-muted">Gunakan kode user ID (dari URL profil).</small>
            @error('google_scholar_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md">
            <div class="form-label">ID Web of Science</div>
            <input type="text" class="form-control @error('wos_id') is-invalid @enderror" wire:model="wos_id"
                placeholder="ResearcherID (misal: A-1234-20...) " />
            <small class="text-muted">Gunakan ResearcherID atau Author ID.</small>
            @error('wos_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mt-1 row g-3">
        <div class="col-md-4">
            <div class="form-label">Scopus H-Index</div>
            <input type="number" class="form-control @error('scopus_h_index') is-invalid @enderror"
                wire:model="scopus_h_index" placeholder="0" min="0" />
            @error('scopus_h_index')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-4">
            <div class="form-label">GS H-Index</div>
            <input type="number" class="form-control @error('gs_h_index') is-invalid @enderror" wire:model="gs_h_index"
                placeholder="0" min="0" />
            @error('gs_h_index')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-4">
            <div class="form-label">WoS H-Index</div>
            <input type="number" class="form-control @error('wos_h_index') is-invalid @enderror"
                wire:model="wos_h_index" placeholder="0" min="0" />
            @error('wos_h_index')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <h3 class="mt-4 card-title">Informasi Pribadi</h3>
    <div class="row g-3">
        <div class="col-md">
            <div class="form-label">Tempat Lahir</div>
            <input type="text" class="form-control @error('birthplace') is-invalid @enderror" wire:model="birthplace"
                placeholder="Masukkan tempat lahir" />
            @error('birthplace')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md">
            <div class="form-label">Tanggal Lahir</div>
            <input type="date" class="form-control @error('birthdate') is-invalid @enderror" wire:model="birthdate" />
            @error('birthdate')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <div class="form-label">Alamat</div>
            <textarea class="form-control @error('address') is-invalid @enderror" wire:model="address" rows="3"
                placeholder="Masukkan alamat lengkap"></textarea>
            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <h3 class="mt-4 card-title">Informasi Akademik</h3>
    <div class="row g-3">
        <div class="col-md">
            <div class="form-label">Institusi</div>
            <div wire:ignore>
                <select class="form-select @error('institution_id') is-invalid @enderror"
                    wire:model.live="institution_id" x-data="tomSelectWithCreate"
                    placeholder="Pilih atau ketik Institusi">
                    <option value="">Pilih atau ketik Institusi</option>
                    @foreach ($institutions as $institution)
                        <option value="{{ $institution['id'] }}">{{ $institution['name'] }}</option>
                    @endforeach
                </select>
            </div>
            @error('institution_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md">
            <div class="form-label">Fakultas</div>
            <div wire:key="faculty-select-{{ $institution_id }}">
                <select class="form-select @error('faculty_id') is-invalid @enderror" wire:model.live="faculty_id"
                    x-data="tomSelect" placeholder="Pilih Fakultas">
                    <option value="">Pilih Fakultas</option>
                    @foreach ($faculties as $faculty)
                        <option value="{{ $faculty['id'] }}">{{ $faculty['name'] }}</option>
                    @endforeach
                </select>
            </div>
            @error('faculty_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md">
            <div class="form-label">Program Studi</div>
            <div wire:key="program-select-{{ $faculty_id }}-{{ $institution_id }}">
                <select class="form-select @error('study_program_id') is-invalid @enderror"
                    wire:model="study_program_id" x-data="tomSelect" placeholder="Pilih Program Studi">
                    <option value="">Pilih Program Studi</option>
                    @foreach ($studyPrograms as $program)
                        <option value="{{ $program['id'] }}">{{ $program['name'] }}</option>
                    @endforeach
                </select>
            </div>
            @error('study_program_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md">
            <div class="form-label">Rumpun Ilmu</div>
            <div wire:key="science-cluster-select">
                <select class="form-select @error('science_cluster_id') is-invalid @enderror"
                    wire:model="science_cluster_id" x-data="tomSelect"
                    placeholder="Pilih Rumpun Ilmu">
                    <option value="">Pilih Rumpun Ilmu</option>
                    @foreach ($scienceClusters as $cluster)
                        <option value="{{ $cluster['id'] }}">{{ $cluster['name'] }}</option>
                    @endforeach
                </select>
            </div>
            @error('science_cluster_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
        <div class="mt-4">
            <div class="alert alert-warning">
                <div class="d-flex">
                    <div>
                        <x-lucide-alert-triangle class="icon alert-icon" />
                    </div>
                    <div class="ms-2">
                        <h4 class="alert-title">Email belum diverifikasi</h4>
                        <div class="text-muted">
                            Alamat email Anda belum diverifikasi.
                            <button type="button" class="ms-1 p-0 btn btn-link"
                                wire:click.prevent="resendVerificationNotification">
                                Klik di sini untuk mengirim ulang email verifikasi.
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('status') === 'verification-link-sent')
                <div class="alert alert-success">
                    <div class="d-flex">
                        <div>
                            <x-lucide-check-circle class="icon alert-icon" />
                        </div>
                        <div class="ms-2">
                            <h4 class="alert-title">Email verifikasi dikirim</h4>
                            <div class="text-muted">
                                Tautan verifikasi baru telah dikirim ke alamat email Anda.
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="bg-transparent mt-auto card-footer">
        <div class="justify-content-end btn-list">
            <button type="button" class="btn" wire:click="resetForm">
                Batal
            </button>
            <button type="submit" class="btn btn-primary" wire:click="updateProfileInformation"
                wire:loading.attr="disabled" wire:target="updateProfileInformation, photo">
                <span wire:loading.remove>Simpan Perubahan</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
    </div>

    <x-tabler.alert />
</div>