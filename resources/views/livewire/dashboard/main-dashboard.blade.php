<div>
    {{-- Modern 3D Infographic Showcase Section --}}
    @if ($dashboardTheme === 'modern')
        <div class="mb-4">
            <x-dashboard.modern-infographic :stats="$infographicStats" :userName="$user->name ?? 'Pengguna'" />
        </div>
    @endif

    {{-- Role Specific Management Views (Tables, Filters, Data) --}}
    <div class="dashboard-content-area">
        @livewire($dashboardComponent, key($dashboardComponent))
    </div>
</div>
