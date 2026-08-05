<x-slot:pageActions>
    <div class="btn-list">
        @php
            $exportParams = ['period' => $period, 'semester' => $selectedSemester, 'search' => $search, 'scheme' => $selectedScheme, 'faculty' => $selectedFaculty];
        @endphp
        <a href="{{ route('reports.pkm.pdf', array_merge($exportParams, ['preview' => 1])) }}"
            class="btn btn-outline-info shadow-sm" target="_blank" title="Tinjau PDF">
            <i class="ti ti-eye me-2"></i>
            <span>{{ __('Tinjau PDF') }}</span>
        </a>
        <a href="{{ route('reports.pkm.excel', $exportParams) }}" class="btn btn-outline-success shadow-sm"
            data-navigate-ignore="true" title="Unduh Excel">
            <i class="ti ti-table me-2"></i>
            <span>{{ __('Unduh Excel') }}</span>
        </a>
        <a href="{{ route('reports.pkm.pdf', $exportParams) }}" class="btn btn-outline-danger shadow-sm"
            data-navigate-ignore="true" title="Unduh PDF">
            <i class="ti ti-file-type-pdf me-2"></i>
            <span>{{ __('Unduh PDF') }}</span>
        </a>
    </div>
</x-slot:pageActions>

<div>
    @include('livewire.reports.partials.institutional-report')
</div>
