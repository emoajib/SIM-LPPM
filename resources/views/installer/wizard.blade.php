<div class="page-installer">
    <div class="container-xl py-4">
        <div class="justify-content-center row">
            <div class="col-lg-8 col-xl-7">
                {{-- Header --}}
                <div class="mb-4 text-center">
                    <a href="." class="navbar-brand navbar-brand-autodark">
                        <img src="{{ asset('logo.png') }}" alt="Logo" width="80" height="80">
                    </a>
                    <h1 class="mt-3">LPPM-ITSNU Installation</h1>
                    <p class="text-secondary">Ayo siapkan sistem manajemen penelitian Anda</p>
                </div>

                {{-- Step Indicator --}}
                <div class="card card-md mb-4">
                    <div class="card-body py-3">
                        <div class="steps steps-counter steps-primary">
                            @foreach ([
        1 => 'Environment',
        2 => 'Database',
        3 => 'Konfigurasi',
        4 => 'Institusi',
        5 => 'Admin',
        6 => 'Install',
    ] as $step => $label)
                                @php
                                    $isCompleted = isset($completedSteps[$step]) && $completedSteps[$step];
                                    $isCurrent = $currentStep === $step;
                                    $isAccessible = $step <= $currentStep;
                                @endphp
                                <a href="#" wire:click.prevent="goToStep({{ $step }})"
                                    @class([
                                        'step-item',
                                        'active' => $isCurrent,
                                        'completed' => $isCompleted,
                                    ])
                                    @if (!$isAccessible) style="pointer-events: none; opacity: 0.5;" @endif>
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Main Content Card --}}
                <div class="card card-md">
                    <div class="card-body">
                        @if ($currentStep === 1)
                            {{-- Step 1: Environment Check --}}
                            <h2 class="card-title mb-1">Pengecekan Environment</h2>
                            <p class="text-secondary mb-4">Memverifikasi server Anda memenuhi semua persyaratan...</p>

                            <div class="list-group list-group-flush mb-4">
                                @foreach ($environmentChecks as $key => $check)
                                    <div
                                        class="list-group-item d-flex align-items-center {{ $check['status'] ? '' : 'list-group-item-danger' }}">
                                        <span class="me-3">
                                            @if ($check['status'])
                                                <svg xmlns="http://www.w3.org/2000/svg" class="text-success icon"
                                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                                    <path d="M9 12l2 2l4 -4" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="text-danger icon"
                                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                                    <path d="M12 9v4" />
                                                    <path d="M12 16v.01" />
                                                </svg>
                                            @endif
                                        </span>
                                        <div class="flex-fill">
                                            <div class="fw-medium">{{ $check['label'] }}</div>
                                            @if (!$check['status'])
                                                <div class="text-danger small">Diperlukan: {{ $check['required'] }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <x-tabler.badge :color="$check['status'] ? 'success' : 'danger'" variant="solid">
                                                {{ $check['current'] }}
                                            </x-tabler.badge>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if (!$environmentPassed)
                                <div class="alert alert-warning">
                                    <div class="d-flex">
                                        <div>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon"
                                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M12 9v4" />
                                                <path
                                                    d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                                                <path d="M12 16h.01" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="alert-title">Perbaiki masalah di atas sebelum melanjutkan</h4>
                                            <div class="text-secondary">
                                                Jika izin direktori yang menjadi masalah, jalankan:
                                                <code class="bg-warning-subtle rounded px-2 py-1">chmod -R 755 storage
                                                    bootstrap/cache</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn" wire:click="checkEnvironment"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="checkEnvironment">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                                        </svg>
                                        Periksa Ulang
                                    </span>
                                    <span wire:loading wire:target="checkEnvironment">
                                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        Memeriksa...
                                    </span>
                                </button>
                                @if ($environmentPassed)
                                    <button type="button" class="btn btn-primary" wire:click="nextStep">
                                        Lanjutkan
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M5 12l14 0" />
                                            <path d="M13 18l6 -6" />
                                            <path d="M13 6l6 6" />
                                        </svg>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-primary" disabled>
                                        Lanjutkan
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2"
                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M5 12l14 0" />
                                            <path d="M13 18l6 -6" />
                                            <path d="M13 6l6 6" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @elseif ($currentStep === 2)
                            {{-- Step 2: Database Configuration --}}
                            <h2 class="card-title mb-1">Konfigurasi Database</h2>
                            <p class="text-secondary mb-4">Masukkan kredensial database MariaDB/MySQL Anda</p>

                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label class="form-label required">Database Host</label>
                                    <input type="text"
                                        class="form-control @error('databaseForm.host') is-invalid @enderror"
                                        wire:model="databaseForm.host" placeholder="127.0.0.1">
                                    @error('databaseForm.host')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">Port</label>
                                    <input type="text"
                                        class="form-control @error('databaseForm.port') is-invalid @enderror"
                                        wire:model="databaseForm.port" placeholder="3306">
                                    @error('databaseForm.port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Nama Database</label>
                                <input type="text"
                                    class="form-control @error('databaseForm.database') is-invalid @enderror"
                                    wire:model="databaseForm.database" placeholder="lppm_itsnu">
                                @error('databaseForm.database')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label required">Username</label>
                                    <input type="text"
                                        class="form-control @error('databaseForm.username') is-invalid @enderror"
                                        wire:model="databaseForm.username" placeholder="root">
                                    @error('databaseForm.username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <div class="input-group input-group-flat" x-data="{ show: false }">
                                        <input :type="show ? 'text' : 'password'"
                                            class="form-control @error('databaseForm.dbPasswordInput') is-invalid @enderror"
                                            wire:model="databaseForm.dbPasswordInput"
                                            placeholder="Biarkan kosong jika tidak ada">
                                        <span class="input-group-text">
                                            <a href="#" class="link-secondary" @click.prevent="show = !show" tabindex="-1" title="Toggle" aria-label="Toggle">
                                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                                <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>
                                            </a>
                                        </span>
                                    </div>
                                    @error('databaseForm.dbPasswordInput')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-check">
                                    <input type="checkbox" class="form-check-input"
                                        wire:model="databaseForm.createDatabase">
                                    <span class="form-check-label">Buat database jika belum ada</span>
                                </label>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button class="btn btn-ghost-secondary" wire:click="previousStep">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M5 12l14 0" />
                                        <path d="M5 12l6 6" />
                                        <path d="M5 12l6 -6" />
                                    </svg>
                                    Kembali
                                </button>
                                <div class="btn-list">
                                    <button class="btn" wire:click="testDatabaseConnection"
                                        wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="testDatabaseConnection">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24"
                                                height="24" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M3.5 5.5l1.5 1.5l2.5 -2.5" />
                                                <path d="M3.5 11.5l1.5 1.5l2.5 -2.5" />
                                                <path d="M3.5 17.5l1.5 1.5l2.5 -2.5" />
                                                <path d="M11 6l9 0" />
                                                <path d="M11 12l9 0" />
                                                <path d="M11 18l9 0" />
                                            </svg>
                                            Test Koneksi
                                        </span>
                                        <span wire:loading wire:target="testDatabaseConnection">
                                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                            Testing...
                                        </span>
                                    </button>
                                    <button class="btn btn-primary" wire:click="nextStep">
                                        Lanjutkan
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2"
                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M5 12l14 0" />
                                            <path d="M13 18l6 -6" />
                                            <path d="M13 6l6 6" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @elseif ($currentStep === 3)
                            {{-- Step 3: Environment Configuration --}}
                            <h2 class="card-title mb-1">Konfigurasi Environment</h2>
                            <p class="text-secondary mb-4">Atur pengaturan aplikasi, session, cache, dan email</p>

                            {{-- Application Settings --}}
                            <div class="mb-4">
                                <h3 class="mb-3">Pengaturan Aplikasi</h3>
                                <div class="mb-3">
                                    <label class="form-label required">Nama Aplikasi</label>
                                    <input type="text"
                                        class="form-control @error('environmentForm.appName') is-invalid @enderror"
                                        wire:model="environmentForm.appName" placeholder="LPPM ITSNU">
                                    <small class="text-muted">Nama yang akan ditampilkan di browser dan email</small>
                                    @error('environmentForm.appName')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label required">Environment</label>
                                        <select class="form-select @error('environmentForm.appEnv') is-invalid @enderror"
                                            wire:model="environmentForm.appEnv">
                                            @foreach ($envOptions['appEnv'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('environmentForm.appEnv')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required">Bahasa</label>
                                        <select class="form-select @error('environmentForm.appLocale') is-invalid @enderror"
                                            wire:model="environmentForm.appLocale">
                                            @foreach ($envOptions['appLocale'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('environmentForm.appLocale')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label required">URL Aplikasi</label>
                                    <input type="url"
                                        class="form-control @error('environmentForm.appUrl') is-invalid @enderror"
                                        wire:model="environmentForm.appUrl" placeholder="https://example.com">
                                    @error('environmentForm.appUrl')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-check">
                                        <input type="checkbox" class="form-check-input"
                                            wire:model="environmentForm.appDebug">
                                        <span class="form-check-label">Aktifkan Mode Debug (tidak disarankan untuk production)</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Session & Cache Settings --}}
                            <div class="mb-4">
                                <h3 class="mb-3">Session & Cache</h3>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Session Driver</label>
                                        <select class="form-select @error('environmentForm.sessionDriver') is-invalid @enderror"
                                            wire:model="environmentForm.sessionDriver">
                                            @foreach ($envOptions['sessionDriver'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('environmentForm.sessionDriver')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Cache Store</label>
                                        <select class="form-select @error('environmentForm.cacheStore') is-invalid @enderror"
                                            wire:model="environmentForm.cacheStore">
                                            @foreach ($envOptions['cacheStore'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('environmentForm.cacheStore')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Queue Connection</label>
                                        <select class="form-select @error('environmentForm.queueConnection') is-invalid @enderror"
                                            wire:model="environmentForm.queueConnection">
                                            @foreach ($envOptions['queueConnection'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('environmentForm.queueConnection')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Mail Settings --}}
                            <div class="mb-4">
                                <h3 class="mb-3">Pengaturan Email</h3>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Mail Driver</label>
                                        <select class="form-select @error('environmentForm.mailMailer') is-invalid @enderror"
                                            wire:model.live="environmentForm.mailMailer">
                                            @foreach ($envOptions['mailMailer'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('environmentForm.mailMailer')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Enkripsi</label>
                                        <select class="form-select @error('environmentForm.mailEncryption') is-invalid @enderror"
                                            wire:model="environmentForm.mailEncryption">
                                            @foreach ($envOptions['mailEncryption'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('environmentForm.mailEncryption')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                @if ($environmentForm->mailMailer === 'smtp')
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <label class="form-label">SMTP Host</label>
                                            <input type="text"
                                                class="form-control @error('environmentForm.mailHost') is-invalid @enderror"
                                                wire:model="environmentForm.mailHost" placeholder="smtp.example.com">
                                            @error('environmentForm.mailHost')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Port</label>
                                            <input type="text"
                                                class="form-control @error('environmentForm.mailPort') is-invalid @enderror"
                                                wire:model="environmentForm.mailPort" placeholder="587">
                                            @error('environmentForm.mailPort')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">SMTP Username</label>
                                            <input type="text"
                                                class="form-control @error('environmentForm.mailUsername') is-invalid @enderror"
                                                wire:model="environmentForm.mailUsername" placeholder="user@example.com">
                                            @error('environmentForm.mailUsername')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">SMTP Password</label>
                                            <div class="input-group input-group-flat" x-data="{ show: false }">
                                                <input :type="show ? 'text' : 'password'"
                                                    class="form-control @error('environmentForm.mailPassword') is-invalid @enderror"
                                                    wire:model="environmentForm.mailPassword" placeholder="Password SMTP">
                                                <span class="input-group-text">
                                                    <a href="#" class="link-secondary" @click.prevent="show = !show" tabindex="-1" title="Toggle" aria-label="Toggle">
                                                        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                                        <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>
                                                    </a>
                                                </span>
                                            </div>
                                            @error('environmentForm.mailPassword')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">From Email</label>
                                        <input type="email"
                                            class="form-control @error('environmentForm.mailFromAddress') is-invalid @enderror"
                                            wire:model="environmentForm.mailFromAddress" placeholder="noreply@example.com">
                                        @error('environmentForm.mailFromAddress')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">From Name</label>
                                        <input type="text"
                                            class="form-control @error('environmentForm.mailFromName') is-invalid @enderror"
                                            wire:model="environmentForm.mailFromName" placeholder="LPPM ITSNU">
                                        @error('environmentForm.mailFromName')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Storage Settings --}}
                            <div class="mb-4">
                                <h3 class="mb-3">Penyimpanan File</h3>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Filesystem Disk</label>
                                        <select class="form-select @error('environmentForm.filesystemDisk') is-invalid @enderror"
                                            wire:model.live="environmentForm.filesystemDisk">
                                            @foreach ($envOptions['filesystemDisk'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('environmentForm.filesystemDisk')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Media Disk</label>
                                        <select class="form-select @error('environmentForm.mediaDisk') is-invalid @enderror"
                                            wire:model.live="environmentForm.mediaDisk">
                                            @foreach ($envOptions['mediaDisk'] as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('environmentForm.mediaDisk')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                            </div>

                            {{-- Turnstile Settings --}}
                            <div class="mb-4">
                                <h3 class="mb-3">Cloudflare Turnstile</h3>
                                <p class="text-secondary small mb-3">Turnstile digunakan untuk proteksi spam pada form dan wajib diisi. Dapatkan key di <a href="https://dash.cloudflare.com/sign-up?to=/:account/turnstile" target="_blank">Cloudflare Dashboard</a>.</p>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label required">Site Key</label>
                                        <input type="text"
                                            class="form-control @error('environmentForm.turnstileSiteKey') is-invalid @enderror"
                                            wire:model="environmentForm.turnstileSiteKey"
                                            placeholder="0x0000000000000000000000">
                                        @error('environmentForm.turnstileSiteKey')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required">Secret Key</label>
                                        <div class="input-group input-group-flat" x-data="{ show: false }">
                                            <input :type="show ? 'text' : 'password'"
                                                class="form-control @error('environmentForm.turnstileSecretKey') is-invalid @enderror"
                                                wire:model="environmentForm.turnstileSecretKey"
                                                placeholder="0x0000000000000000000000">
                                            <span class="input-group-text">
                                                <a href="#" class="link-secondary" @click.prevent="show = !show" tabindex="-1" title="Toggle" aria-label="Toggle">
                                                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>
                                                </a>
                                            </span>
                                        </div>
                                        @error('environmentForm.turnstileSecretKey')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button class="btn btn-ghost-secondary" wire:click="previousStep">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M5 12l14 0" />
                                        <path d="M5 12l6 6" />
                                        <path d="M5 12l6 -6" />
                                    </svg>
                                    Kembali
                                </button>
                                <button class="btn btn-primary" wire:click="nextStep">
                                    Lanjutkan
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M5 12l14 0" />
                                        <path d="M13 18l6 -6" />
                                        <path d="M13 6l6 6" />
                                    </svg>
                                </button>
                            </div>
                        @elseif ($currentStep === 4)
                            {{-- Step 4: Institution Setup --}}
                            <h2 class="card-title mb-1">Pengaturan Institusi</h2>
                            <p class="text-secondary mb-4">Konfigurasi detail institusi Anda</p>

                            <div class="mb-3">
                                <label class="form-label required">Nama Institusi</label>
                                <input type="text"
                                    class="form-control @error('institutionForm.institutionName') is-invalid @enderror"
                                    wire:model="institutionForm.institutionName"
                                    placeholder="Institut Teknologi dan Sains Nahdlatul Ulama">
                                @error('institutionForm.institutionName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Nama Singkat</label>
                                <input type="text"
                                    class="form-control @error('institutionForm.institutionShortName') is-invalid @enderror"
                                    wire:model="institutionForm.institutionShortName" placeholder="ITSNU Pekalongan">
                                @error('institutionForm.institutionShortName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" rows="2" wire:model="institutionForm.address"
                                    placeholder="Alamat lengkap institusi"></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" class="form-control" wire:model="institutionForm.phone"
                                        placeholder="(0285) 123456">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control @error('institutionForm.institutionEmail') is-invalid @enderror"
                                        wire:model="institutionForm.institutionEmail"
                                        placeholder="lppm@itsnu.ac.id">
                                    @error('institutionForm.institutionEmail')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Website</label>
                                <input type="url" class="form-control" wire:model="institutionForm.website"
                                    placeholder="https://itsnu.ac.id">
                            </div>

                            {{-- Faculties --}}
                            <div class="mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <label class="form-label mb-0">Fakultas</label>
                                    <button type="button" class="btn-outline-primary btn btn-sm"
                                        wire:click="addFaculty">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2"
                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M12 5l0 14" />
                                            <path d="M5 12l14 0" />
                                        </svg>
                                        Tambah Fakultas
                                    </button>
                                </div>

                                @foreach ($institutionForm->faculties as $index => $faculty)
                                    <div class="row g-2 mb-2" wire:key="faculty-{{ $index }}">
                                        <div class="col">
                                            <input type="text"
                                                class="form-control @error('institutionForm.faculties.' . $index . '.name') is-invalid @enderror"
                                                placeholder="Nama Fakultas"
                                                wire:model="institutionForm.faculties.{{ $index }}.name">
                                            @error('institutionForm.faculties.' . $index . '.name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-auto" style="width: 120px;">
                                            <input type="text" class="form-control" placeholder="Kode"
                                                wire:model="institutionForm.faculties.{{ $index }}.code">
                                        </div>
                                        @if (count($institutionForm->faculties) > 1)
                                            <div class="col-auto">
                                                <button type="button" class="btn btn-ghost-danger btn-icon"
                                                    wire:click="removeFaculty({{ $index }})" title="Toggle" aria-label="Toggle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon"
                                                        width="24" height="24" viewBox="0 0 24 24"
                                                        stroke-width="2" stroke="currentColor" fill="none"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M4 7l16 0" />
                                                        <path d="M10 11l0 6" />
                                                        <path d="M14 11l0 6" />
                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="d-flex justify-content-between">
                                <button class="btn btn-ghost-secondary" wire:click="previousStep">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M5 12l14 0" />
                                        <path d="M5 12l6 6" />
                                        <path d="M5 12l6 -6" />
                                    </svg>
                                    Kembali
                                </button>
                                <button class="btn btn-primary" wire:click="nextStep">
                                    Lanjutkan
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M5 12l14 0" />
                                        <path d="M13 18l6 -6" />
                                        <path d="M13 6l6 6" />
                                    </svg>
                                </button>
                            </div>
                        @elseif ($currentStep === 5)
                            {{-- Step 5: Admin Account --}}
                            <h2 class="card-title mb-1">Akun Administrator</h2>
                            <p class="text-secondary mb-4">Buat akun administrator utama</p>

                            <div class="mb-3">
                                <label class="form-label required">Nama Lengkap</label>
                                <input type="text"
                                    class="form-control @error('adminForm.adminName') is-invalid @enderror"
                                    wire:model="adminForm.adminName" placeholder="Administrator">
                                @error('adminForm.adminName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Alamat Email</label>
                                <input type="email"
                                    class="form-control @error('adminForm.adminEmail') is-invalid @enderror"
                                    wire:model="adminForm.adminEmail" placeholder="admin@example.com">
                                @error('adminForm.adminEmail')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Password</label>
                                <div class="input-group input-group-flat" x-data="{ show: false }">
                                    <input :type="show ? 'text' : 'password'"
                                        class="form-control @error('adminForm.adminPassword') is-invalid @enderror"
                                        wire:model="adminForm.adminPassword"
                                        placeholder="Minimal 8 karakter">
                                    <span class="input-group-text">
                                        <a href="#" class="link-secondary" @click.prevent="show = !show" tabindex="-1" title="Toggle" aria-label="Toggle">
                                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>
                                        </a>
                                    </span>
                                </div>
                                @error('adminForm.adminPassword')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Minimal 8 karakter dengan huruf besar, huruf kecil, dan angka
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label required">Konfirmasi Password</label>
                                <div class="input-group input-group-flat" x-data="{ show: false }">
                                    <input :type="show ? 'text' : 'password'"
                                        class="form-control @error('adminForm.adminPasswordConfirmation') is-invalid @enderror"
                                        wire:model="adminForm.adminPasswordConfirmation"
                                        placeholder="Ulangi password">
                                    <span class="input-group-text">
                                        <a href="#" class="link-secondary" @click.prevent="show = !show" tabindex="-1" title="Toggle" aria-label="Toggle">
                                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>
                                        </a>
                                    </span>
                                </div>
                                @error('adminForm.adminPasswordConfirmation')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between">
                                <button class="btn btn-ghost-secondary" wire:click="previousStep">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M5 12l14 0" />
                                        <path d="M5 12l6 6" />
                                        <path d="M5 12l6 -6" />
                                    </svg>
                                    Kembali
                                </button>
                                <button class="btn btn-primary" wire:click="nextStep">
                                    Lanjutkan
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M5 12l14 0" />
                                        <path d="M13 18l6 -6" />
                                        <path d="M13 6l6 6" />
                                    </svg>
                                </button>
                            </div>
                        @elseif ($currentStep === 6)
                            {{-- Step 6: Installation Progress --}}
                            {{-- Poll for progress updates while installing --}}
                            @if ($isInstalling)
                                <div wire:poll.1s="checkProgress"></div>
                            @endif

                            <h2 class="card-title mb-1">Instalasi</h2>
                            <p class="text-secondary mb-4">Menyiapkan aplikasi Anda...</p>

                            @if (!$installationProgress['complete'] && !$isInstalling && !$installationProgress['error'])
                                <div class="alert alert-info">
                                    <div class="d-flex">
                                        <div>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon"
                                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                                <path d="M12 9h.01" />
                                                <path d="M11 12h1v4h1" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="alert-title">Siap untuk Instalasi</h4>
                                            <div class="text-secondary mb-3">Semua konfigurasi telah selesai. Klik
                                                tombol di bawah untuk memulai proses instalasi.</div>
                                            <div class="text-secondary row g-2">
                                                <div class="col-6">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="text-success icon me-1" width="24" height="24"
                                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                        fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M5 12l5 5l10 -10" />
                                                    </svg>
                                                    Environment OK
                                                </div>
                                                <div class="col-6">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="text-success icon me-1" width="24" height="24"
                                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                        fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M5 12l5 5l10 -10" />
                                                    </svg>
                                                    Database OK
                                                </div>
                                                <div class="col-6">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="text-success icon me-1" width="24" height="24"
                                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                        fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M5 12l5 5l10 -10" />
                                                    </svg>
                                                    Konfigurasi OK
                                                </div>
                                                <div class="col-6">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="text-success icon me-1" width="24" height="24"
                                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                        fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M5 12l5 5l10 -10" />
                                                    </svg>
                                                    Institusi OK
                                                </div>
                                                <div class="col-6">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="text-success icon me-1" width="24" height="24"
                                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                        fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M5 12l5 5l10 -10" />
                                                    </svg>
                                                    Admin OK
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button class="w-100 btn btn-primary" wire:click="startInstallation"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="startInstallation">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                            <path d="M7 11l5 5l5 -5" />
                                            <path d="M12 4l0 12" />
                                        </svg>
                                        Mulai Instalasi
                                    </span>
                                    <span wire:loading wire:target="startInstallation">
                                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        Memulai...
                                    </span>
                                </button>
                            @endif

                            @if ($isInstalling || $installationProgress['complete'] || $installationProgress['error'])
                                {{-- Installation Steps Progress --}}
                                @php
                                    $installSteps = [
                                        ['key' => 'backup_env', 'label' => 'Backup konfigurasi', 'threshold' => 5],
                                        ['key' => 'write_env', 'label' => 'Menulis file environment', 'threshold' => 10],
                                        ['key' => 'clear_config', 'label' => 'Membersihkan cache', 'threshold' => 12],
                                        ['key' => 'generate_key', 'label' => 'Generate application key', 'threshold' => 17],
                                        ['key' => 'run_migrations', 'label' => 'Menjalankan migrasi database', 'threshold' => 67],
                                        ['key' => 'run_seeders', 'label' => 'Mengisi data awal', 'threshold' => 92],
                                        ['key' => 'create_admin', 'label' => 'Membuat akun admin', 'threshold' => 97],
                                        ['key' => 'storage_link', 'label' => 'Membuat storage link', 'threshold' => 99],
                                        ['key' => 'finalize', 'label' => 'Finalisasi instalasi', 'threshold' => 100],
                                    ];
                                    $currentPercent = $installationProgress['percent'];
                                    $hasError = !empty($installationProgress['error']);
                                @endphp

                                <div class="mb-4">
                                    <div class="list-group list-group-flush">
                                        @foreach ($installSteps as $index => $step)
                                            @php
                                                $prevThreshold = $index > 0 ? $installSteps[$index - 1]['threshold'] : 0;
                                                $isCompleted = $currentPercent >= $step['threshold'];
                                                $isActive = $currentPercent >= $prevThreshold && $currentPercent < $step['threshold'];
                                                $isPending = $currentPercent < $prevThreshold;
                                                $isFailed = $hasError && $isActive;
                                            @endphp
                                            <div class="list-group-item d-flex align-items-center py-2 {{ $isFailed ? 'list-group-item-danger' : '' }}">
                                                <span class="me-3">
                                                    @if ($isFailed)
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                                            <path d="M10 10l4 4m0 -4l-4 4" />
                                                        </svg>
                                                    @elseif ($isCompleted)
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                                            <path d="M9 12l2 2l4 -4" />
                                                        </svg>
                                                    @elseif ($isActive)
                                                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon text-secondary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                                        </svg>
                                                    @endif
                                                </span>
                                                <span class="{{ $isCompleted ? 'text-success' : ($isActive ? 'fw-bold' : 'text-secondary') }}">
                                                    {{ $step['label'] }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Progress Bar --}}
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-medium">
                                            @if ($isInstalling)
                                                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                            @endif
                                            {{ $installationProgress['message'] }}
                                        </span>
                                        <span class="text-secondary">{{ $installationProgress['percent'] }}%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar {{ $installationProgress['error'] ? 'bg-danger' : ($installationProgress['complete'] ? 'bg-success' : '') }}"
                                            role="progressbar"
                                            style="width: {{ $installationProgress['percent'] }}%"></div>
                                    </div>
                                </div>

                                @if ($installationProgress['error'])
                                    <div class="alert alert-danger">
                                        <div class="d-flex">
                                            <div>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon"
                                                    width="24" height="24" viewBox="0 0 24 24"
                                                    stroke-width="2" stroke="currentColor" fill="none"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                                    <path d="M12 8v4" />
                                                    <path d="M12 16h.01" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="alert-title">Instalasi Gagal</h4>
                                                <div class="text-secondary">{{ $installationProgress['error'] }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Retry Button --}}
                                    <button class="w-100 btn btn-warning mb-3" wire:click="retryInstallation"
                                        wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="retryInstallation">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24"
                                                height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                                                <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                                            </svg>
                                            Coba Lagi
                                        </span>
                                        <span wire:loading wire:target="retryInstallation">
                                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                            Memulai ulang...
                                        </span>
                                    </button>
                                @endif

                                {{-- Installation Logs --}}
                                @if (count($installationProgress['logs']) > 0)
                                    <div class="mb-3">
                                        <label class="form-label">Log Instalasi</label>
                                        <div class="bg-dark text-light rounded p-3"
                                            style="max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;"
                                            id="installation-logs">
                                            @foreach ($installationProgress['logs'] as $log)
                                                <div class="{{ str_contains($log, 'ERROR') ? 'text-danger' : 'text-success-lt' }}">{{ $log }}</div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif

                            @if ($installationProgress['complete'])
                                <div class="alert alert-success">
                                    <div class="d-flex">
                                        <div>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon"
                                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                                <path d="M9 12l2 2l4 -4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="alert-title">Instalasi Selesai!</h4>
                                            <div class="text-secondary">Sistem LPPM-ITSNU Anda sekarang siap digunakan.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <a href="{{ route('login') }}" class="w-100 btn btn-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M9 8v-2a2 2 0 0 1 2 -2h7a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-7a2 2 0 0 1 -2 -2v-2" />
                                        <path d="M3 12h13l-3 -3" />
                                        <path d="M13 15l3 -3" />
                                    </svg>
                                    Ke Halaman Login
                                </a>
                            @endif

                            @if (!$installationProgress['complete'])
                                <div class="d-flex justify-content-between mt-4">
                                    <button class="btn btn-ghost-secondary" wire:click="previousStep"
                                        @disabled($isInstalling)>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2"
                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M5 12l14 0" />
                                            <path d="M5 12l6 6" />
                                            <path d="M5 12l6 -6" />
                                        </svg>
                                        Kembali
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="text-secondary mt-4 text-center">
                    <small>{{ config('app.name') }} &copy; {{ date('Y') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Notification Toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;" x-data="{ show: false, message: '', type: 'success' }"
        x-on:notify.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 5000)"
        x-show="show" x-transition x-cloak>
        <div class="toast show" role="alert">
            <div class="toast-header">
                <span class="avatar avatar-xs me-2" :class="type === 'success' ? 'bg-success' : 'bg-danger'">
                    <template x-if="type === 'success'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check"
                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 12l5 5l10 -10" />
                        </svg>
                    </template>
                    <template x-if="type !== 'success'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x" width="24"
                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M18 6l-12 12" />
                            <path d="M6 6l12 12" />
                        </svg>
                    </template>
                </span>
                <strong class="me-auto" x-text="type === 'success' ? 'Berhasil' : 'Gagal'"></strong>
                <button type="button" class="btn-close ms-2" @click="show = false"></button>
            </div>
            <div class="toast-body" x-text="message"></div>
        </div>
    </div>
</div>
