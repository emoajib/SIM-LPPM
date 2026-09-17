<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use Livewire\Attributes\On;
use Livewire\Attributes\Url;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
trait ReportFilters
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $selectedYear = '';

    #[Url]
    public string $roleFilter = 'ketua';

    #[Url]
    public string $statusFilter = 'all';

    #[Url]
    public string $schemeFilter = 'all';

    #[On('resetFilters')]
    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedYear', 'roleFilter', 'statusFilter', 'schemeFilter']);
    }
}
