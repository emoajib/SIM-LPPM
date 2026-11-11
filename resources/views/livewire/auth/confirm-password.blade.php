<x-layouts.auth>
    <div class="py-4 container-tight">
        <div class="mb-4 text-center">
            <a href="." class="navbar-brand navbar-brand-autodark">
                <img src="/logo.png" alt="Logo" width="100" height="100">
            </a>
        </div>
        <div class="card card-md">
            <div class="card-body">
                <h2 class="mb-4 text-center h2">{{ __('Konfirmasi kata sandi') }}</h2>
                <p class="mb-4 text-secondary text-center">{{ __('Ini adalah area aman dari aplikasi. Silakan konfirmasi kata sandi Anda sebelum melanjutkan.') }}</p>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('password.confirm.store') }}" autocomplete="off" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">{{ __('Kata sandi') }}</label>
                        <div class="input-group input-group-flat" x-data="{ showPassword: false }">
                            <input x-bind:type="showPassword ? 'text' : 'password'" name="password"
                                class="form-control" placeholder="{{ __('Kata sandi') }}" autocomplete="current-password" required />
                            <span class="input-group-text">
                                <button type="button" class="bg-transparent p-0 border-0 link-secondary"
                                    x-on:click="showPassword = ! showPassword"
                                    x-bind:title="showPassword ? 'Hide password' : 'Show password'"
                                    data-bs-toggle="tooltip">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="icon">
                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                    </svg>
                                </button>
                            </span>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="w-100 btn btn-primary d-flex align-items-center gap-2 justify-content-center d-flex align-items-center gap-2" data-test="confirm-password-button">
                            <i class="ti ti-check me-1" aria-hidden="true"></i>
                            {{ __('Konfirmasi') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.auth>
