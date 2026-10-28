<div class="space-y-6">
    <!-- Page Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Kelayakan Peneliti</h1>
        <p class="text-gray-600 mt-1">Lihat kelayakan dosen untuk setiap skema penelitian</p>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @php
            $totalDosen = collect($allSchemesStats)->sum('total_dosen');
            $avgEligibility = collect($allSchemesStats)->count() > 0
                ? round(collect($allSchemesStats)->avg('eligible_percentage'), 1)
                : 0;
        @endphp

        <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Dosen</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $totalDosen }}</p>
                </div>
                
            </div>
        </div>

        <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Skema Penelitian Aktif</p>
                    <p class="text-3xl font-bold text-gray-900">{{ count($allSchemesStats) }}</p>
                </div>
                
            </div>
        </div>

        <div class="bg-white rounded-lg border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Rata-rata Kelayakan</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $avgEligibility }}%</p>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Schemes Overview -->
    <div class="bg-white rounded-lg border">
        <div class="p-6 border-b">
            <h2 class="text-lg font-bold text-gray-900">Ringkasan Kelayakan Per Skema</h2>
        </div>

        <div class="divide-y">
            @forelse ($allSchemesStats as $schemeStat)
                <div class="p-4 hover:bg-gray-50 cursor-pointer transition" wire:click="selectScheme('{{ $schemeStat['id'] }}')">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">{{ $schemeStat['name'] }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $schemeStat['description'] }}</p>

                            <div class="mt-3 grid grid-cols-3 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500">Total Dosen</p>
                                    <p class="text-lg font-semibold text-gray-900">{{ $schemeStat['total_dosen'] }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Eligible</p>
                                    <p class="text-lg font-semibold text-green-600">{{ $schemeStat['eligible_count'] }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Tidak Eligible</p>
                                    <p class="text-lg font-semibold text-red-600">{{ $schemeStat['ineligible_count'] }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0">
                            <div class="text-2xl font-bold {{ $schemeStat['eligible_percentage'] >= 50 ? 'text-green-600' : 'text-orange-600' }}">
                                {{ $schemeStat['eligible_percentage'] }}%
                            </div>
                            <p class="text-xs text-gray-500 mt-1">eligible</p>

                            <button
                                type="button"
                                wire:click="selectScheme('{{ $schemeStat['id'] }}')"
                                class="mt-3 px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700"
                            >
                                Detail
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-4 w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                        <div class="bg-green-600 h-2 transition-all @if($schemeStat['eligible_percentage'] >= 75) w-full @elseif($schemeStat['eligible_percentage'] >= 50) w-1/2 @elseif($schemeStat['eligible_percentage'] >= 25) w-1/4 @else w-1/12 @endif"></div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-gray-500">
                    Belum ada skema penelitian yang aktif
                </div>
            @endforelse
        </div>
    </div>

    <!-- Detail Panel for Selected Scheme -->
    @if ($selectedSchemeId)
        @php
            $selectedScheme = collect($allSchemesStats)
                ->first(fn($s) => $s['id'] === $selectedSchemeId);
        @endphp

        @if ($selectedScheme)
            <div class="bg-white rounded-lg border">
                <div class="p-6 border-b flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">{{ $selectedScheme['name'] }} - Detail Kelayakan</h2>
                    <button
                        type="button"
                        wire:click="$set('selectedSchemeId', null)"
                        class="text-gray-400 hover:text-gray-600" title="Tutup" aria-label="Tutup">
                        
                    </button>
                </div>

                <!-- Ineligibility Breakdown -->
                <div class="p-6 border-b">
                    <h3 class="font-semibold text-gray-900 mb-4">Alasan Ketidaklayakan</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @php
                            $ineligibleReasons = $selectedScheme['breakdown']['ineligible_by_reason'];
                        @endphp

                        @foreach ($ineligibleReasons as $reason => $count)
                            @if ($count > 0)
                                <div class="bg-red-50 rounded-lg p-4">
                                    <p class="text-sm text-gray-600">{{ $reason }}</p>
                                    <p class="text-2xl font-bold text-red-600 mt-2">{{ $count }}</p>
                                </div>
                            @endif
                        @endforeach

                        @if (collect($ineligibleReasons)->sum() === 0)
                            <div class="col-span-full text-center py-4 text-gray-500">
                                Semua dosen yang tidak eligible memiliki catatan alasan yang jelas
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Tabs: Eligible vs Ineligible -->
                <div class="border-b">
                    <div class="flex">
                        <button
                            type="button"
                            @click="$wire.set('activeTab', 'eligible')"
                            class="flex-1 px-6 py-3 font-medium text-center {{ $activeTab === 'eligible' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-600 hover:text-gray-900' }}"
                        >
                            Eligible ({{ $eligibleDosenForSelectedScheme->count() }})
                        </button>
                        <button
                            type="button"
                            @click="$wire.set('activeTab', 'ineligible')"
                            class="flex-1 px-6 py-3 font-medium text-center {{ $activeTab === 'ineligible' ? 'text-red-600 border-b-2 border-red-600' : 'text-gray-600 hover:text-gray-900' }}"
                        >
                            Tidak Eligible ({{ $ineligibleDosenForSelectedScheme->count() }})
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    @if ($activeTab === 'eligible')
                        @php
                            $eligibleDosen = $eligibleDosenForSelectedScheme;
                        @endphp

                        @if ($eligibleDosen->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-900">Nama</th>
                                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-900">NIP</th>
                                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-900">Persyaratan Terpenuhi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($eligibleDosen as $dosen)
                                            <tr class="hover:bg-green-50">
                                                <td class="py-3 px-4 text-sm text-gray-900">{{ $dosen['name'] }}</td>
                                                <td class="py-3 px-4 text-sm text-gray-600">{{ $dosen['nip'] }}</td>
                                                <td class="py-3 px-4 text-center">
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-green-100 text-green-800 text-xs font-medium">
                                                        
                                                        {{ count($dosen['passed_checks']) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                Tidak ada dosen yang eligible untuk skema ini
                            </div>
                        @endif
                    @else
                        @php
                            $ineligibleDosen = $ineligibleDosenForSelectedScheme;
                        @endphp

                        @if ($ineligibleDosen->count() > 0)
                            <div class="space-y-3">
                                @foreach ($ineligibleDosen as $dosen)
                                    <details class="border rounded-lg p-4">
                                        <summary class="cursor-pointer flex items-start justify-between">
                                            <div class="flex-1">
                                                <p class="font-semibold text-gray-900">{{ $dosen['name'] }}</p>
                                                <p class="text-sm text-gray-600">{{ $dosen['nip'] }}</p>
                                            </div>
                                            <div class="flex-shrink-0 text-right">
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-red-100 text-red-800 text-xs font-medium">
                                                    {{ count($dosen['failed_checks']) }} tidak terpenuhi
                                                </span>
                                            </div>
                                        </summary>

                                        <div class="mt-4 pt-4 border-t space-y-3">
                                            @foreach ($dosen['failed_checks'] as $check)
                                                <div class="bg-red-50 rounded p-3">
                                                    <p class="text-sm font-semibold text-gray-900">{{ $check['name'] }}</p>
                                                    <p class="text-sm text-gray-700 mt-1">{{ $check['message'] }}</p>
                                                    @if ($check['type'] === 'numeric')
                                                        <div class="flex gap-4 mt-2 text-xs text-gray-600">
                                                            <span>Diperlukan: <span class="font-semibold">{{ $check['required'] }}</span></span>
                                                            <span>Dimiliki: <span class="font-semibold">{{ $check['current'] }}</span></span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                Semua dosen eligible untuk skema ini!
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Export Button -->
                <div class="p-6 border-t bg-gray-50 flex justify-end">
                    <button
                        type="button"
                        wire:click="exportEligibilityReport('{{ $selectedSchemeId }}')"
                        class="inline-flex items-center gap-2 px-4 py-2 font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                    >
                        
                        Export CSV
                    </button>
                </div>
            </div>
        @endif
    @endif
</div>

<!-- Alpine JS for tab switching -->
<script>
    document.addEventListener('livewire:init', () => {
        Alpine.data('eligibilityDashboard', () => ({
            activeTab: 'eligible',
        }));
    });
</script>
