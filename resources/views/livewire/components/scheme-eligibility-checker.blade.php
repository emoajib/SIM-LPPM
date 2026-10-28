@php
    $hasEligibleSchemes = $eligibleSchemes->count() > 0;
    $totalSchemes = $eligibleSchemes->count() + $ineligibleSchemes->count();
@endphp

<div class="mb-6">
    <!-- Eligibility Summary Card -->
    <div class="border rounded-lg p-4 mb-4 {{ $hasEligibleSchemes ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }}">
        <div class="flex items-start gap-3">
            <div class="{{ $hasEligibleSchemes ? 'text-green-600' : 'text-amber-600' }}">
                @if ($hasEligibleSchemes)
                    <x-lucide-check-circle class="w-6 h-6" />
                @else
                    <x-lucide-alert-circle class="w-6 h-6" />
                @endif
            </div>
            <div class="flex-1">
                <h3 class="font-semibold {{ $hasEligibleSchemes ? 'text-green-900' : 'text-amber-900' }}">
                    Status Kelayakan Anda
                </h3>
                @if ($hasEligibleSchemes)
                    <p class="text-sm {{ $hasEligibleSchemes ? 'text-green-800' : 'text-amber-800' }} mt-1">
                        Anda eligible untuk {{ $eligibleSchemes->count() }} dari {{ $totalSchemes }} skema penelitian.
                        @if ($ineligibleSchemes->count() > 0)
                            Anda tidak eligible untuk {{ $ineligibleSchemes->count() }} skema.
                        @endif
                    </p>
                @else
                    <p class="text-sm text-amber-800 mt-1">
                        Saat ini Anda tidak eligible untuk skema penelitian yang tersedia.
                        Periksa persyaratan di bawah untuk mengetahui apa yang perlu ditingkatkan.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- Eligible Schemes Section -->
    @if ($hasEligibleSchemes)
        <div class="mb-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">
                ✅ Skema yang Anda Layak Ikuti ({{ $eligibleSchemes->count() }})
            </h4>
            <div class="grid grid-cols-1 gap-2">
                @foreach ($eligibleSchemes as $scheme)
                    <button
                        type="button"
                        wire:click="checkEligibility('{{ $scheme->id }}')"
                        class="text-left p-3 rounded-lg border border-green-200 bg-white hover:bg-green-50 transition"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ $scheme->name }}</p>
                                <p class="text-sm text-gray-600">{{ $scheme->description }}</p>
                            </div>
                            <x-lucide-chevron-right class="w-5 h-5 text-green-600 flex-shrink-0" />
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Ineligible Schemes Section -->
    @if ($ineligibleSchemes->count() > 0)
        <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-3">
                ❌ Skema Tidak Layak ({{ $ineligibleSchemes->count() }})
            </h4>
            <div class="space-y-2">
                @foreach ($ineligibleSchemes as $item)
                    <details class="border rounded-lg p-3 bg-white">
                        <summary class="cursor-pointer flex items-start justify-between">
                            <div class="flex-1 text-left">
                                <p class="font-medium text-gray-900">{{ $item['scheme']->name }}</p>
                                <p class="text-xs text-red-600 mt-1">
                                    {{ count($item['failed_checks']) }} persyaratan tidak terpenuhi
                                </p>
                            </div>
                            <x-lucide-chevron-down class="w-4 h-4 text-gray-400" />
                        </summary>
                        <div class="mt-3 pt-3 border-t space-y-2">
                            @foreach ($item['failed_checks'] as $check)
                                <div class="text-sm">
                                    <p class="font-medium text-gray-700">{{ $check['name'] }}</p>
                                    <p class="text-gray-600">{{ $check['message'] }}</p>
                                    @if ($check['type'] === 'numeric')
                                        <p class="text-xs text-gray-500 mt-1">
                                            <span class="inline-block mr-2">Diperlukan: <span class="font-semibold">{{ $check['required'] }}</span></span>
                                            <span class="inline-block">Anda punya: <span class="font-semibold">{{ $check['current'] }}</span></span>
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    @endif
</div>

<!-- Eligibility Check Modal -->
@if ($showEligibilityModal && $selectedSchemeId)
    @php
        $selectedScheme = $this->schemeType === 'research'
            ? \App\Models\ResearchScheme::find($selectedSchemeId)
            : \App\Models\CommunityServiceScheme::find($selectedSchemeId);
    @endphp

    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="$set('showEligibilityModal', false)">
        <div class="bg-white rounded-lg max-w-md w-full mx-4 p-6" @click.stop>
            <!-- Header -->
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Hasil Pengecekan Kelayakan</h3>
                    <p class="text-sm text-gray-600">{{ $selectedScheme->name }}</p>
                </div>
                <button
                    type="button"
                    wire:click="$set('showEligibilityModal', false)"
                    class="text-gray-400 hover:text-gray-600" title="Tutup" aria-label="Tutup">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>

            <!-- Status -->
            <div class="mb-6 p-3 rounded-lg {{ $eligibilityStatus['eligible'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                <div class="flex items-center gap-2">
                    @if ($eligibilityStatus['eligible'])
                        <x-lucide-check-circle class="w-5 h-5 text-green-600" />
                        <p class="font-semibold text-green-900">Anda eligible untuk skema ini!</p>
                    @else
                        <x-lucide-x-circle class="w-5 h-5 text-red-600" />
                        <p class="font-semibold text-red-900">Anda tidak eligible</p>
                    @endif
                </div>
            </div>

            <!-- Passed Checks -->
            @if (!empty($eligibilityStatus['passed_checks']))
                <div class="mb-4">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Persyaratan Terpenuhi:</p>
                    <div class="space-y-2">
                        @foreach ($eligibilityStatus['passed_checks'] as $check)
                            <div class="flex items-start gap-2 p-2 rounded bg-green-50">
                                <x-lucide-check class="w-4 h-4 text-green-600 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $check['name'] }}</p>
                                    @if ($check['type'] === 'numeric')
                                        <p class="text-xs text-gray-600">
                                            {{ $check['current'] }}/{{ $check['required'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Failed Checks -->
            @if (!empty($eligibilityStatus['failed_checks']))
                <div class="mb-6">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Persyaratan Tidak Terpenuhi:</p>
                    <div class="space-y-2">
                        @foreach ($eligibilityStatus['failed_checks'] as $check)
                            <div class="flex items-start gap-2 p-2 rounded bg-red-50">
                                <x-lucide-x class="w-4 h-4 text-red-600 flex-shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ $check['name'] }}</p>
                                    <p class="text-xs text-gray-600">{{ $check['message'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="flex gap-3 mt-6">
                <button
                    type="button"
                    wire:click="$set('showEligibilityModal', false)"
                    class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                    Batal
                </button>
                @if ($eligibilityStatus['eligible'])
                    <button
                        type="button"
                        wire:click="selectScheme('{{ $selectedSchemeId }}')"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"
                    >
                        Pilih Skema
                    </button>
                @else
                    <button
                        type="button"
                        wire:click="$set('showEligibilityModal', false)"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        OK
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif
