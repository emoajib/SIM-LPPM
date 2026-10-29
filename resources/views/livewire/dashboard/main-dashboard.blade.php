<div>
    <!-- Dashboard View Mode Switcher -->
    <div class="d-flex align-items-center justify-content-between mb-3 bg-white dark:bg-dark p-2 px-3 rounded shadow-sm border border-secondary-subtle flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="text-secondary small font-weight-bold d-none d-sm-inline">Tampilan Dashboard:</span>
            <div class="btn-group btn-group-sm" role="group" aria-label="Dashboard View Switcher">
                <button type="button" 
                    wire:click="setDashboardTheme('modern')"
                    class="btn {{ $dashboardTheme === 'modern' ? 'btn-primary active font-weight-bold' : 'btn-outline-secondary' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                    Modern (3D Infografis)
                </button>
                <button type="button" 
                    wire:click="setDashboardTheme('classic')"
                    class="btn {{ $dashboardTheme === 'classic' ? 'btn-primary active font-weight-bold' : 'btn-outline-secondary' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Klasik (Standar)
                </button>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-blue-lt">LPPM ITSNU Pekalongan</span>
        </div>
    </div>

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
