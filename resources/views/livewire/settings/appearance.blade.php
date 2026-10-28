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
        <p class="form-hint">Ubah logo yang ditampilkan pada navbar aplikasi Anda.</p>
        <form wire:submit="saveLogo" enctype="multipart/form-data">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar avatar-xl form-imagecheck-image">
                        <img src="{{ $currentLogo }}" alt="Logo Aplikasi" style="object-fit: contain;">
                    </span>
                </div>
                <div class="col">
                    <input type="file" class="form-control" wire:model="logo"
                        accept="image/png, image/jpeg, image/jpg, image/webp">
                    <div class="mt-2 text-muted small">Max file size is 2MB. Recommended dimensions: 200x200px.</div>
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
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveLogo, logo">
                    Simpan Logo
                </button>
            </div>
        </form>
    </div>

    <!-- Teks Aplikasi/Brand -->
    <div class="mt-5 mb-4 border-top pt-4 border-muted">
        <h3 class="card-title">Pengaturan Nama Aplikasi</h3>
        <p class="form-hint">Ubah nama sistem atau instansi yang ditampilkan di sebelah logo pada atas menu aplikasi
            Anda.</p>
        <form wire:submit="saveSettings">
            <div class="mb-3">
                <label class="form-label">Nama Aplikasi</label>
                <input type="text" class="form-control" wire:model="dashboardName" placeholder="Contoh: SIM LPPM ITSNU">
            </div>

            <div class="mb-3">
                <label class="form-label">Judul Halaman Login</label>
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
                <button type="submit" class="btn btn-primary">
                    Simpan Nama Aplikasi
                </button>
            </div>
        </form>
    </div>

    <div class="bg-transparent mt-auto card-footer">
        <div class="justify-content-end btn-list">
            <button type="button" class="btn" @click="resetTheme()">
                
                Reset Perubahan Tema
            </button>
            <button type="button" class="btn btn-primary" @click="saveTheme()">
                Simpan Preferensi Tema
            </button>
        </div>
    </div>
</div>