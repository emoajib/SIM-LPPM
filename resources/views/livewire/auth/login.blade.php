<div class="page page-center overflow-hidden" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); position: relative;">
    <!-- Premium Mesh Gradient Background -->
    <div style="position: absolute; top: -10%; left: -10%; width: 40%; height: 40%; background: radial-gradient(circle, rgba(32, 107, 196, 0.1) 0%, transparent 70%); filter: blur(50px); z-index: 0;"></div>
    <div style="position: absolute; bottom: -10%; right: -10%; width: 50%; height: 50%; background: radial-gradient(circle, rgba(40, 167, 69, 0.08) 0%, transparent 70%); filter: blur(60px); z-index: 0;"></div>

    <div class="py-5 container-normal container" style="position: relative; z-index: 1;">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-8">
                <div class="mb-5 text-center">
                    <a href="." class="navbar-brand navbar-brand-autodark">
                        <img src="/logo.png" alt="Logo" width="120" style="filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
                    </a>
                </div>
                
                <div class="card card-md border-0" style="background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 1.25rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.12);">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="mb-5 text-center h2 fw-bold text-dark" style="letter-spacing: -0.02em;">{{ $loginTitle }}</h2>
                        
                        @error('general')
                            <div class="alert alert-danger alert-dismissible d-flex align-items-center py-2 px-3 mb-4" role="alert">
                                <x-lucide-alert-circle class="icon me-2 flex-shrink-0" style="width:1.25rem;height:1.25rem;" />
                                <span>{{ $message }}</span>
                                <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert"></button>
                            </div>
                        @enderror

                        <form method="post" wire:submit.prevent="login" autocomplete="off" novalidate>
                            <div class="mb-4">
                                <label class="form-label fw-medium text-secondary mb-2">Email atau ID Identitas</label>
                                <div class="input-group input-group-flat border rounded-3 focus-within-glow">
                                    <span class="input-group-text bg-transparent border-0 px-3">
                                        <x-lucide-user class="icon text-muted" />
                                    </span>
                                    <input type="text" wire:model="email"
                                        class="form-control border-0 ps-0 @error('email') is-invalid @enderror"
                                        placeholder="Email / NIDN / NIK / NIM" autocomplete="username" autofocus />
                                </div>
                                @error('email')
                                    <div class="invalid-feedback d-block mt-2 small">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Honeypot Field (Hidden) -->
                            <div style="display: none;">
                                <label for="username_honeypot">Username</label>
                                <input type="text" id="username_honeypot" wire:model="username_honeypot" name="username_honeypot">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium text-secondary mb-2 d-flex justify-content-between">
                                    Password
                                    <a href="{{ route('password.request') }}" class="small link-primary text-decoration-none fw-normal">Lupa password?</a>
                                </label>
                                <div class="input-group input-group-flat border rounded-3 focus-within-glow" x-data="{ showPassword: false }">
                                    <span class="input-group-text bg-transparent border-0 px-3">
                                        <x-lucide-lock class="icon text-muted" />
                                    </span>
                                    <input x-bind:type="showPassword ? 'text' : 'password'"
                                        class="form-control border-0 ps-0 @error('password') is-invalid @enderror"
                                        placeholder="Masukkan password" autocomplete="off" wire:model="password" />
                                    <span class="input-group-text bg-transparent border-0 pe-3">
                                        <button type="button" class="bg-transparent p-0 border-0 link-secondary"
                                            x-on:click="showPassword = ! showPassword"
                                            x-bind:title="showPassword ? 'Hide password' : 'Show password'">
                                            <template x-if="!showPassword">
                                                <x-lucide-eye class="icon-1 icon" />
                                            </template>
                                            <template x-if="showPassword">
                                                <x-lucide-eye-off class="icon-1 icon" />
                                            </template>
                                        </button>
                                    </span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block mt-2 small">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            @if(config('turnstile.site_key'))
                                <div class="mb-4 d-flex justify-content-center" wire:ignore>
                                    <div class="cf-turnstile" 
                                        data-sitekey="{{ config('turnstile.site_key') }}" 
                                        data-callback="onTurnstileFinished"></div>
                                </div>
                                <input type="hidden" id="captcha" wire:model="captcha">
                                @error('captcha')
                                    <div class="text-danger d-block mb-4 small text-center">{{ $message }}</div>
                                @enderror

                                <script>
                                    function onTurnstileFinished(token) {
                                        @this.set('captcha', token);
                                    }

                                    document.addEventListener('livewire:initialized', () => {
                                        Livewire.on('resetTurnstile', () => {
                                            if (typeof window.turnstile !== 'undefined') {
                                                window.turnstile.reset();
                                            }
                                        });
                                    });
                                </script>
                            @else
                                <div class="mb-4 bg-light p-3 rounded-3 border-dashed border-2">
                                    <label class="form-label fw-bold text-dark mb-2">Verifikasi Keamanan</label>
                                    <p class="small text-muted mb-2">Berapa hasil dari <strong>{{ $n1 }} + {{ $n2 }}</strong>?</p>
                                    <input type="number" wire:model="math_answer"
                                        class="form-control rounded-2 @error('math_answer') is-invalid @enderror"
                                        placeholder="Isi jawaban" autocomplete="off" />
                                    @error('math_answer')
                                        <div class="invalid-feedback d-block mt-2 small">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            @endif

                            <div class="mb-4">
                                <label class="form-check form-check-inline">
                                    <input type="checkbox" class="form-check-input" wire:model='remember' />
                                    <span class="form-check-label text-secondary small">Ingat saya di perangkat ini</span>
                                </label>
                            </div>

                            <div class="form-footer mt-4">
                                <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-bold shadow-sm" wire:loading.attr="disabled">
                                    <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    <span wire:loading.remove>Masuk ke Dashboard</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                @if(app()->environment('local'))
                    <div class="mt-4 pt-4 border-top">
                        <p class="text-center text-secondary small fw-bold mb-3">🔧 Developer Quick Login (Local Only)</p>
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            @php
                                $devRoles = ['superadmin', 'admin lppm', 'kepala lppm', 'dosen', 'reviewer', 'dekan', 'kaprodi', 'rektor'];
                            @endphp
                            @foreach($devRoles as $role)
                                <button type="button" wire:click="devLogin('{{ $role }}')" 
                                    class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                    {{ ucfirst($role) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <p class="text-center mt-4 text-secondary small">
                    &copy; {{ date('Y') }} SIM LPPM ITSNU Pekalongan. All rights reserved.
                </p>
            </div>
        </div>
    </div>
    
    <style>
        .focus-within-glow:focus-within {
            border-color: var(--tblr-primary) !important;
            box-shadow: 0 0 0 0.25rem rgba(var(--tblr-primary-rgb), 0.15) !important;
            transition: all 0.2s ease-in-out;
        }
        .icon-1 { width: 1.25rem; height: 1.25rem; }
        .border-dashed { border-style: dashed !important; }
    </style>
</div>