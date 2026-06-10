<?php

namespace App\Livewire\Reports;

use App\Models\InstitutionalReport;
use Livewire\Component;
use Livewire\WithPagination;

class InstitutionalReportMonitoring extends Component
{
    use WithPagination;

    public $search = '';

    public $type = 'all';

    public $status = 'all';

    public $year = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => 'all'],
        'status' => ['except' => 'all'],
        'year' => ['except' => 'all'],
    ];

    public function updated($property)
    {
        if (in_array($property, ['search', 'type', 'status', 'year'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = InstitutionalReport::query()
            ->with(['submitter.identity', 'approver.identity'])
            ->latest('updated_at');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('notes', 'like', '%'.$this->search.'%')
                    ->orWhereHas('submitter', fn ($u) => $u->where('name', 'like', '%'.$this->search.'%'));
            });
        }

        if ($this->type !== 'all') {
            $query->where('type', $this->type);
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->year !== 'all') {
            $query->where('year', $this->year);
        }

        $reports = $query->paginate(10);
        $availableYears = InstitutionalReport::distinct()->pluck('year')->sortDesc();

        return view('livewire.reports.institutional-report-monitoring', [
            'reports' => $reports,
            'availableYears' => $availableYears,
            'types' => [
                'research' => 'Penelitian',
                'pkm' => 'Pengabdian (PKM)',
                'output' => 'Luaran',
                'partner' => 'Kerjasama Mitra',
                'iku' => 'Rekap IKU',
                'monev' => 'Monitoring & Evaluasi (Monev)',
                'reviewer' => 'Penugasan Reviewer',
            ],
        ])->layout('components.layouts.app', [
            'pageTitle' => 'Monitoring Laporan Institusi',
            'pageSubtitle' => 'Lacak status pengajuan, revisi, dan persetujuan laporan ke Rektor',
        ]);
    }

    public function getStatusLabel($status)
    {
        return $status->label();
    }

    public function getStatusColor($status)
    {
        return $status->color();
    }
}
