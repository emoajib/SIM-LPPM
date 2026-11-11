<x-layouts.auth>
    <div class="py-4 container-tight">
        <div class="mb-4 text-center">
            <a href="." class="navbar-brand navbar-brand-autodark">
                <img src="/logo.png" alt="Logo" width="100" height="100">
            </a>
        </div>
        <div class="card card-md">
            <div class="card-body">
                <div x-cloak x-data="{ showRecoveryInput: @js($errors->has('recovery_code')) }">
                    <div x-show="!showRecoveryInput">
                        <h2 class="mb-4 text-center card-title card-title-lg">{{ __('Authenticate Your Account') }}</h2>
                        <p class="my-4 text-center">
                            {{ __('Please confirm your account by entering the authorization code sent to your device.') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('two-factor.login.store') }}" id="twoFactorForm">
                        @csrf
                        <input type="hidden" name="code" id="combinedCode">

                        <div x-show="!showRecoveryInput" class="my-5">
                            <div class="row g-4">
                                <div class="col">
                                    <div class="row g-2">
                                        <div class="col">
                                            <input type="text"
                                                class="px-3 py-3 text-center form-control form-control-lg"
                                                maxlength="1" inputmode="numeric" pattern="[0-9]*" data-code-input>
                                        </div>
                                        <div class="col">
                                            <input type="text"
                                                class="px-3 py-3 text-center form-control form-control-lg"
                                                maxlength="1" inputmode="numeric" pattern="[0-9]*" data-code-input>
                                        </div>
                                        <div class="col">
                                            <input type="text"
                                                class="px-3 py-3 text-center form-control form-control-lg"
                                                maxlength="1" inputmode="numeric" pattern="[0-9]*" data-code-input>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="row g-2">
                                        <div class="col">
                                            <input type="text"
                                                class="px-3 py-3 text-center form-control form-control-lg"
                                                maxlength="1" inputmode="numeric" pattern="[0-9]*" data-code-input>
                                        </div>
                                        <div class="col">
                                            <input type="text"
                                                class="px-3 py-3 text-center form-control form-control-lg"
                                                maxlength="1" inputmode="numeric" pattern="[0-9]*" data-code-input>
                                        </div>
                                        <div class="col">
                                            <input type="text"
                                                class="px-3 py-3 text-center form-control form-control-lg"
                                                maxlength="1" inputmode="numeric" pattern="[0-9]*" data-code-input>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @error('code')
                                <div class="d-block mt-3 text-danger text-center">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- <div x-show="showRecoveryInput">
                            <div class="mb-3">
                                <input type="text" name="recovery_code" class="form-control form-control-lg"
                                    autocomplete="one-time-code" placeholder="{{ __('Enter recovery code') }}" />
                            </div>

                            @error('recovery_code')
                                <div class="d-block mb-3 text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="my-4">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input">
                                <span
                                    class="form-check-label">{{ __("Don't ask for codes again on this device") }}</span>
                            </label>
                        </div>

                        <div class="form-footer">
                            <div class="flex-nowrap btn-list">
                                <a href="{{ route('login') }}" class="w-100 btn btn-3 d-flex align-items-center gap-2 justify-content-center d-flex align-items-center gap-2">
                                    <i class="ti ti-logout me-1" aria-hidden="true"></i>
                                    {{ __('Cancel') }}
                                </a>
                                <button type="submit" class="w-100 btn btn-primary btn-3 d-flex align-items-center gap-2 justify-content-center d-flex align-items-center gap-2">
                                    <i class="ti ti-check me-1" aria-hidden="true"></i>
                                    <span x-show="!showRecoveryInput">{{ __('Verify') }}</span>
                                    <span x-show="showRecoveryInput">{{ __('Continue') }}</span>
                                </button>
                            </div>
                        </div> --}}

                        {{-- <div class="mt-3 text-secondary text-center">
                            <span>{{ __('or you can') }}</span>
                            <div class="d-inline">
                                <a href="#" class="link-primary" x-show="!showRecoveryInput"
                                    @click.prevent="showRecoveryInput = true">{{ __('use recovery code') }}</a>
                                <a href="#" class="link-primary" x-show="showRecoveryInput"
                                    @click.prevent="showRecoveryInput = false">{{ __('use authentication code') }}</a>
                            </div>
                        </div> --}}
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var inputs = document.querySelectorAll("[data-code-input]");

            function combineAndSubmit() {
                var code = Array.from(inputs).map(input => input.value).join('');
                if (code.length === 6) {
                    document.getElementById('combinedCode').value = code;
                    document.getElementById('twoFactorForm').submit();
                }
            }

            // Attach an event listener to each input element
            for (let i = 0; i < inputs.length; i++) {
                inputs[i].addEventListener("input", function(e) {
                    // If the input field has a character, and there is a next input field, focus it
                    if (e.target.value.length === e.target.maxLength && i + 1 < inputs.length) {
                        inputs[i + 1].focus();
                    }
                    // Check if all fields are filled and auto-submit
                    combineAndSubmit();
                });

                inputs[i].addEventListener("keydown", function(e) {
                    // If the input field is empty and the keyCode for Backspace (8) is detected, and there is a previous input field, focus it
                    if (e.target.value.length === 0 && e.keyCode === 8 && i > 0) {
                        inputs[i - 1].focus();
                    }
                });

                // Handle paste event
                inputs[i].addEventListener("paste", function(e) {
                    e.preventDefault();
                    var pastedText = (e.clipboardData || window.clipboardData).getData('text');
                    var digits = pastedText.replace(/\D/g, '').split('');

                    for (let j = 0; j < Math.min(digits.length, inputs.length); j++) {
                        inputs[j].value = digits[j];
                    }

                    // Focus the next empty field or submit
                    if (digits.length >= inputs.length) {
                        combineAndSubmit();
                    } else {
                        inputs[digits.length].focus();
                    }
                });
            }
        });
    </script>
</x-layouts.auth>
