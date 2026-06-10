<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Dashboard extends Component
{
    public $user;

    public $roleName;

    public $dashboardComponent;

    public function mount()
    {
        $this->user = Auth::user();
        $this->roleName = active_role();

        $this->dashboardComponent = match ($this->roleName) {
            'superadmin', 'admin lppm', 'admin lppm saintek', 'admin lppm dekabita' => 'dashboard.admin-dashboard',
            'kepala lppm' => 'dashboard.kepala-lppm-dashboard',
            'dosen' => 'dashboard.dosen-dashboard',
            'reviewer' => 'dashboard.reviewer-dashboard',
            'rektor', 'dekan', 'kaprodi' => 'dashboard.exec-dashboard',
            default => 'dashboard.default-dashboard',
        };
    }

    public function render()
    {
        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
        $titles = match ($this->roleName) {
            'superadmin', 'admin lppm', 'admin lppm saintek', 'admin lppm dekabita' => [
                'title' => 'Dashboard Admin',
                'subtitle' => 'Ikhtisar performa dan aktivitas LPPM.',
            ],
            'kepala lppm' => [
                'title' => 'Dashboard Kepala LPPM',
                'subtitle' => 'Monitoring dan evaluasi strategi penelitian & PKM.',
            ],
            'dosen' => [
                'title' => 'Dashboard Dosen',
                'subtitle' => 'Kelola portofolio penelitian dan pengabdian masyarakat Anda.',
            ],
            'reviewer' => [
                'title' => 'Dashboard Reviewer',
                'subtitle' => 'Pantau tugas review proposal yang sedang berjalan.',
            ],
            'rektor', 'dekan', 'kaprodi' => [
                'title' => 'Dashboard Eksekutif',
                'subtitle' => 'Laporan ringkasan institusional dan pencapaian target.',
            ],
            default => [
                'title' => 'Dashboard Utama',
                'subtitle' => 'Selamat datang di Sistem Informasi LPPM.',
            ],
        };

        return view('livewire.dashboard.main-dashboard', [
            'pageTitle' => $titles['title'],
            'pageSubtitle' => $titles['subtitle'],
        ])->layoutData([
            'pageTitle' => $titles['title'],
            'pageSubtitle' => $titles['subtitle'],
        ]);
    }
}
