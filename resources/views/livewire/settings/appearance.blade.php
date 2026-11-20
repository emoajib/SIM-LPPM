<div x-data="{
    config: {
        theme: 'light',
        'theme-base': 'gray',
        'theme-font': 'sans-serif',
        'theme-primary': 'blue',
        'theme-radius': '1',
    },
    defaults: {
        theme: 'light',
        'theme-base': 'gray',
        'theme-font': 'sans-serif',
        'theme-primary': 'blue',
        'theme-radius': '1',
    },
    url: new URL(window.location),

    init() {
        this.loadSettings();
    },

    loadSettings() {
        for (let key in this.defaults) {
            const savedValue = window.localStorage.getItem('tabler-' + key);
            this.config[key] = savedValue || this.defaults[key];
            this.applyTheme(key, this.config[key]);
        }
    },

    applyTheme(key, value) {
        if (value) {
            document.documentElement.setAttribute('data-bs-' + key, value);
            window.localStorage.setItem('tabler-' + key, value);
            this.url.searchParams.set(key, value);
        }
    },

    saveTheme() {
        for (let key in this.config) {
            window.localStorage.setItem('tabler-' + key, this.config[key]);
        }
        window.history.pushState({}, '', this.url);
        console.log('Preferensi tema berhasil disimpan');
    },

    resetTheme() {
        for (let key in this.defaults) {
            const defaultValue = this.defaults[key];
            this.config[key] = defaultValue;
            window.localStorage.setItem('tabler-' + key, defaultValue);
            this.applyTheme(key, defaultValue);
        }
        window.history.pushState({}, '', this.url);
        console.log('Preferensi tema direset ke default');
    }
}" x-init="init()" class="space-y-4">
    <div class="mb-4">
        <label class="form-label">Mode Tampilan</label>
        <p class="form-hint">Pilih tema yang Anda inginkan untuk antarmuka aplikasi.</p>
        <label class="form-check">
            <div class="form-selectgroup-item">
                <input type="radio" name="theme" value="light" class="form-check-input" x-model="config.theme"
                    @change="applyTheme('theme', config.theme)" />
                <div class="form-check-label">Terang</div>
            </div>
        </label>
        <label class="form-check">
            <div class="form-selectgroup-item">
                <input type="radio" name="theme" value="dark" class="form-check-input" x-model="config.theme"
                    @change="applyTheme('theme', config.theme)" />
                <div class="form-check-label">Gelap</div>
            </div>
        </label>
    </div>

    <div class="mb-4">
        <label class="form-label">Skema Warna</label>
        <p class="form-hint">Pilih warna utama yang sempurna untuk aplikasi Anda.</p>
        <div class="row g-2">
            <template
                x-for="color in ['blue', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan']"
                :key="color">
                <div class="col-auto">
                    <label class="form-colorinput">
                        <input name="theme-primary" type="radio" :value="color" class="form-colorinput-input"
                            x-model="config['theme-primary']"
                            @change="applyTheme('theme-primary', config['theme-primary'])" />
                        <span :class="'bg-' + color + ' form-colorinput-color'"></span>
                    </label>
                </div>
            </template>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">Jenis Huruf</label>
        <p class="form-hint">Pilih jenis huruf yang sesuai dengan aplikasi Anda.</p>
        <div>
            <template x-for="font in ['sans-serif', 'serif', 'monospace', 'comic']" :key="font">
                <label class="form-check">
                    <div class="form-selectgroup-item">
                        <input type="radio" name="theme-font" :value="font" class="form-check-input"
                            x-model="config['theme-font']" @change="applyTheme('theme-font', config['theme-font'])" />
                        <div class="form-check-label" x-text="font.charAt(0).toUpperCase() + font.slice(1)"></div>
                    </div>
                </label>
            </template>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">Tema Dasar</label>
        <p class="form-hint">Pilih gradasi abu-abu untuk aplikasi Anda.</p>
        <div>
            <template x-for="base in ['slate', 'gray', 'zinc', 'neutral', 'stone']" :key="base">
                <label class="form-check">
                    <div class="form-selectgroup-item">
                        <input type="radio" name="theme-base" :value="base" class="form-check-input"
                            x-model="config['theme-base']" @change="applyTheme('theme-base', config['theme-base'])" />
                        <div class="form-check-label" x-text="base.charAt(0).toUpperCase() + base.slice(1)"></div>
                    </div>
                </label>
            </template>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">Radius Sudut</label>
        <p class="form-hint">Pilih faktor radius border untuk aplikasi Anda.</p>
        <div>
            <template x-for="radius in ['0', '0.5', '1', '1.5', '2']" :key="radius">
                <label class="form-check">
                    <div class="form-selectgroup-item">
                        <input type="radio" name="theme-radius" :value="radius" class="form-check-input"
                            x-model="config['theme-radius']"
                            @change="applyTheme('theme-radius', config['theme-radius'])" />
                        <div class="form-check-label" x-text="radius"></div>
                    </div>
                </label>
            </template>
        </div>
    </div>

    <!-- Upload Logo -->
    <div class="mt-5 mb-4 border-top pt-4 border-muted">
        <h3 class="card-title">Pengaturan Logo Dashboard</h3>
        <p class="form-hint"><i class="ti ti-pencil me-1" aria-hidden="true"></i>
Ubah logo yang ditampilkan pada navbar aplikasi Anda.</p>
        <form wire:submit="saveLogo" enctype="multipart/form-data">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar avatar-xl form-imagecheck-image">
                        <img src="{{ $currentLogo }}" alt="Logo Aplikasi" style="object-fit: contain;">
                    </span>
                </div>
                <div class="col">
                    <label class="form-label" for="logo_file">Berkas logo</label>
                    <input type="file" id="logo_file" class="form-control @error('logo') is-invalid @enderror"
                        wire:model="logo" accept="image/png, image/jpeg, image/jpg, image/webp"
                        aria-describedby="logo_hint">
                    <div class="mt-2 text-muted small" id="logo_hint">
                        Berkas <strong>langsung tersimpan</strong> begitu dipilih. Maksimal 2MB, disarankan 200×200 px.
                    </div>
                </div>
            </div>

            <div wire:loading wire:target="logo" class="mt-2 text-primary small">Mengunggah...</div>

            @error('logo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror

            @if (session()->has('success_logo'))
                <div class="alert alert-success mt-3">
                    {{ session('success_logo') }}
                </div>
            @endif

            <div class="mt-3">
                <button type="submit" class="btn btn-outline-primary d-flex align-items-center gap-2" wire:loading.attr="disabled" wire:target="saveLogo, logo">
                    <i class="ti ti-refresh me-1" aria-hidden="true"></i>
                    Unggah Ulang / Simpan
                </button>
            </div>
        </form>
    </div>

    <!-- Upload Maskot Banner Dashboard -->
    <div class="mt-5 mb-4 border-top pt-4 border-muted" id="maskot">
        <h3 class="card-title">Maskot Banner Dashboard</h3>
        <p class="form-hint"><i class="ti ti-pencil me-1" aria-hidden="true"></i>
Ubah ilustrasi maskot yang tampil di banner dashboard modern (kanan). Hanya admin LPPM & superadmin.</p>

        <div class="row align-items-center g-3">
            <div class="col-auto">
                <span class="avatar avatar-xl form-imagecheck-image" style="width:120px;height:120px;background:linear-gradient(135deg,#0284c7,#0f172a);display:flex;align-items:flex-end;justify-content:center;overflow:hidden;">
                    @if ($currentMascot)
                        <img src="{{ $currentMascot }}" alt="Maskot Dashboard" style="max-width:100%;max-height:100%;object-fit:contain;">
                    @else
                        <span class="text-white small text-center">SVG bawaan</span>
                    @endif
                </span>
            </div>
            <div class="col">
                <form wire:submit="saveMascot" enctype="multipart/form-data">
                    <label class="form-label" for="mascot_file">Berkas maskot</label>
                    <input type="file" id="mascot_file" class="form-control @error('mascot') is-invalid @enderror"
                        wire:model="mascot" accept="image/png, image/jpeg, image/jpg, image/webp"
                        aria-describedby="mascot_hint">
                    <div class="mt-2 text-muted small" id="mascot_hint">
                        Berkas <strong>langsung tersimpan</strong> begitu dipilih. Maksimal 2MB, disarankan
                        PNG transparan rasio ±1:1.
                    </div>

                    <div wire:loading wire:target="mascot" class="mt-2 text-primary small">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Mengunggah maskot...
                    </div>

                    <div class="mt-2 small" aria-live="polite">
                        @if ($currentMascot)
                            <span class="badge bg-success-lt text-success">Maskot aktif</span>
                            <span class="text-secondary ms-1">{{ $currentMascotName }}</span>
                            <a href="{{ $currentMascot }}" target="_blank" rel="noopener noreferrer" class="ms-2">Lihat gambar</a>
                        @else
                            <span class="badge bg-secondary-lt text-secondary">Belum ada maskot</span>
                            <span class="text-secondary ms-1">Dashboard memakai SVG bawaan.</span>
                        @endif
                    </div>

                    @error('mascot') <div class="text-danger small mt-1">{{ $message }}</div> @enderror

                    @if (session()->has('success_mascot'))
                        <div class="alert alert-success mt-3">
                            {{ session('success_mascot') }}
                        </div>
                    @endif

                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-outline-primary d-flex align-items-center gap-2" wire:loading.attr="disabled" wire:target="saveMascot, mascot">
                            <i class="ti ti-refresh me-1" aria-hidden="true"></i>
                            Unggah Ulang / Simpan
                        </button>
                        @if ($currentMascot)
                            <button type="button" wire:click="removeMascot" wire:confirm="Kembalikan maskot ke bawaan?" class="btn btn-outline-danger d-flex align-items-center gap-2">
                                <i class="ti ti-trash me-1" aria-hidden="true"></i>
                                Kembalikan ke Bawaan
                            </button>
                        @endif
                    </div>
                </form>

                <!-- Ukuran maskot (preset) -->
                <div class="mt-4 pt-3 border-top border-muted">
                    <span class="form-label mb-2 d-block">Ukuran maskot di banner</span>
                    <div class="d-flex flex-wrap gap-3" role="radiogroup" aria-label="Ukuran maskot banner">
                        @foreach ([
                            'small' => ['label' => 'Kecil', 'note' => '75%'],
                            'medium' => ['label' => 'Sedang', 'note' => '100%'],
                            'large' => ['label' => 'Besar', 'note' => '125%'],
                        ] as $value => $option)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mascot_size"
                                    id="mascot_size_{{ $value }}" wire:model="mascotSize" value="{{ $value }}">
                                <label class="form-check-label" for="mascot_size_{{ $value }}">
                                    {{ $option['label'] }} <span class="text-muted small">({{ $option['note'] }})</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-1 text-muted small">
                        Teks banner otomatis menyesuaikan agar tidak tertutup maskot.
                    </div>

                    @error('mascotSize') <div class="text-danger small mt-1">{{ $message }}</div> @enderror

                    @if (session()->has('success_mascot_size'))
                        <div class="alert alert-success mt-3 mb-0">{{ session('success_mascot_size') }}</div>
                    @endif

                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <button type="button" wire:click="saveMascotSize"
                            class="btn btn-primary d-flex align-items-center gap-2"
                            wire:loading.attr="disabled" wire:target="saveMascotSize, mascotSize">
                            <i class="ti ti-check me-1" aria-hidden="true"></i>
                            Simpan Ukuran
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Avatar Default Pengguna (Pria / Wanita) -->
    <div class="mt-5 mb-4 border-top pt-4 border-muted" id="avatar-default">
        <h3 class="card-title">Avatar Default Pengguna</h3>
        <p class="form-hint"><i class="ti ti-pencil me-1" aria-hidden="true"></i>
Tampil untuk pengguna yang belum mengunggah foto profil. Avatar dipilih otomatis sesuai
jenis kelamin pada data identitas (L = pria, P = wanita); bila jenis kelamin kosong, sistem
memakai perkiraan dari nama. Foto profil milik pengguna sendiri selalu diutamakan. Bila avatar
belum diatur, yang tampil adalah inisial nama. Khusus admin LPPM &amp; superadmin.</p>

        <div class="row g-3">
            @foreach ([
                ['gender' => 'male', 'label' => 'Avatar Pria', 'current' => $currentAvatarMale, 'prop' => 'avatarMale', 'flash' => 'success_avatar_male'],
                ['gender' => 'female', 'label' => 'Avatar Wanita', 'current' => $currentAvatarFemale, 'prop' => 'avatarFemale', 'flash' => 'success_avatar_female'],
            ] as $slot)
                <div class="col-12 col-md-6">
                    <div class="card card-sm h-100">
                        <div class="card-body">
                            <h4 class="card-title mb-3">{{ $slot['label'] }}</h4>

                            <div class="d-flex align-items-center gap-3 mb-3">
                                @php
                                    $pending = $slot['gender'] === 'male' ? $avatarMale : $avatarFemale;
                                    $previewUrl = $slot['current'];
                                    if ($pending) {
                                        try {
                                            $previewUrl = $pending->temporaryUrl();
                                        } catch (\Throwable $e) {
                                            $previewUrl = null;
                                        }
                                    }
                                @endphp
                                <span class="avatar avatar-xl"
                                    style="width:72px;height:72px;background:#e2e8f0 center/cover no-repeat{{ $previewUrl ? ';background-image:url('.$previewUrl.')' : '' }};overflow:hidden;">
                                    @if (! $previewUrl)
                                        <span class="text-secondary small">Inisial</span>
                                    @endif
                                </span>
                                <div class="small text-secondary">
                                    {{ $slot['current'] ? 'Avatar aktif digunakan.' : 'Belum diatur — pengguna melihat inisial nama.' }}
                                </div>
                            </div>

                            <label class="form-label" for="avatar_{{ $slot['gender'] }}_file">Berkas avatar {{ $slot['gender'] === 'male' ? 'pria' : 'wanita' }}</label>
                            <input type="file" id="avatar_{{ $slot['gender'] }}_file"
                                class="form-control @error($slot['prop']) is-invalid @enderror"
                                wire:model="{{ $slot['prop'] }}"
                                accept="image/png, image/jpeg, image/jpg, image/webp"
                                aria-describedby="avatar_{{ $slot['gender'] }}_hint">
                            <div class="mt-1 text-muted small" id="avatar_{{ $slot['gender'] }}_hint">
                                Maksimal 2MB. Disarankan PNG/JPG rasio 1:1, minimal 200×200 px.
                            </div>

                            <div wire:loading wire:target="{{ $slot['prop'] }}" class="mt-2 text-primary small">Mengunggah...</div>
                            @error($slot['prop']) <div class="text-danger small mt-1">{{ $message }}</div> @enderror

                            @if (session()->has($slot['flash']))
                                <div class="alert alert-success mt-3 mb-0">{{ session($slot['flash']) }}</div>
                            @endif

                            <div class="mt-3 d-flex flex-wrap gap-2">
                                <button type="button"
                                    wire:click="saveAvatar{{ $slot['gender'] === 'male' ? 'Male' : 'Female' }}"
                                    class="btn btn-primary d-flex align-items-center gap-2"
                                    wire:loading.attr="disabled" wire:target="saveAvatar{{ $slot['gender'] === 'male' ? 'Male' : 'Female' }}, {{ $slot['prop'] }}">
                                    <i class="ti ti-upload me-1" aria-hidden="true"></i>
                                    Simpan {{ $slot['label'] }}
                                </button>
                                @if ($slot['current'])
                                    <button type="button"
                                        wire:click="removeAvatar{{ $slot['gender'] === 'male' ? 'Male' : 'Female' }}"
                                        wire:confirm="Hapus avatar {{ $slot['gender'] === 'male' ? 'pria' : 'wanita' }} dan kembalikan ke inisial?"
                                        class="btn btn-outline-danger d-flex align-items-center gap-2">
                                        <i class="ti ti-trash me-1" aria-hidden="true"></i>
                                        Hapus
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Teks Aplikasi/Brand -->
    <div class="mt-5 mb-4 border-top pt-4 border-muted">
        <h3 class="card-title">Pengaturan Nama Aplikasi</h3>
        <p class="form-hint"><i class="ti ti-pencil me-1" aria-hidden="true"></i>
Ubah nama sistem atau instansi yang ditampilkan di sebelah logo pada atas menu aplikasi
            Anda.</p>
        <form wire:submit="saveSettings">
            <div class="mb-3">
                <label class="form-label">Nama Aplikasi</label>
                <input type="text" class="form-control" wire:model="dashboardName" placeholder="Contoh: SIM LPPM ITSNU">
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="ti ti-login me-1" aria-hidden="true"></i>
Judul Halaman Login</label>
                <input type="text" class="form-control" wire:model="loginTitle"
                    placeholder="Contoh: Masuk ke Akun Anda">
                <p class="form-hint">Teks ini akan ditampilkan sebagai judul utama pada halaman login.</p>
            </div>

            @if (session()->has('success_settings'))
                <div class="alert alert-success mt-2">
                    {{ session('success_settings') }}
                </div>
            @endif

            <div class="mt-3">
                <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="ti ti-save me-1" aria-hidden="true"></i>
                    Simpan Nama Aplikasi
                </button>
            </div>
        </form>
    </div>

    <div class="bg-transparent mt-auto card-footer">
        <div class="justify-content-end btn-list">
            <button type="button" class="btn d-flex align-items-center gap-2" @click="resetTheme()">
                
                Reset Perubahan Tema
            </button>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2" @click="saveTheme()">
                <i class="ti ti-save me-1" aria-hidden="true"></i>
                Simpan Preferensi Tema
            </button>
        </div>
    </div>
</div>