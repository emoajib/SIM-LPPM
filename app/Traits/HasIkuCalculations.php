<?php

namespace App\Traits;

use App\Models\AdditionalOutput;
use App\Models\Identity;
use App\Models\MandatoryOutput;
use App\Models\MasterIku;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * HasIkuCalculations
 *
 * Centralized logic for IKU calculations following Kepmen 358/M/KEP/2025.
 */
trait HasIkuCalculations
{
    /**
     * Get all IKU metrics for a given period.
     */
    public function getIkuMetrics(string $period): array
    {
        $ikus = MasterIku::where('is_active', true)->orderBy('code')->get();
        $metrics = [];

        foreach ($ikus as $iku) {
            $key = strtolower(str_replace('-', '', $iku->code)); // iku03, iku04, etc.

            // Check for Manual Override in Settings table (Zero Migration Approach)
            $isManual = (bool) Setting::where('key', "iku_manual_{$iku->code}")->value('value');

            if ($isManual) {
                $manualValue = (float) Setting::where('key', "iku_manual_value_{$iku->code}")->value('value');
                $metrics[$key] = [
                    'code' => $iku->code,
                    'name' => $iku->name,
                    'description' => $iku->description.' (Input Manual Aktif)',
                    'achievement' => $manualValue,
                    'target' => (float) $iku->target_percentage,
                    'is_manual' => true,
                ];

                continue;
            }

            // Fallback to Automated Calculation
            $metrics[$key] = $this->calculateAutomatedIku($iku, $period);
        }

        return $metrics;
    }

    /**
     * Dispatcher for automated calculations based on IKU code.
     */
    protected function calculateAutomatedIku($iku, string $period): array
    {
        $method = 'calculate'.Str::studly(str_replace('-', '', $iku->code));

        if (method_exists($this, $method)) {
            return $this->$method($period, $iku);
        }

        return [
            'code' => $iku->code,
            'name' => $iku->name,
            'description' => $iku->description,
            'achievement' => 0,
            'target' => (float) $iku->target_percentage,
            'is_manual' => false,
        ];
    }

    /**
     * IKU 3: Mahasiswa di Luar Kampus
     * Rumus: (Mhs Pengalaman Luar + Prestasi) / Total Mhs * 100
     */
    protected function calculateIku03(string $period, MasterIku $iku): array
    {
        // Placeholder total mhs (bisa diatur via setting jika tidak ada tabel mhs)
        $totalMhs = (int) Setting::where('key', 'total_mahasiswa_pt')->value('value') ?: 1000;

        // Mock data logic for now, using proposal involvements if any
        $involvedMhs = Proposal::query()->forAcademicYear($period)->count();

        $percentage = $totalMhs > 0 ? ($involvedMhs / $totalMhs) * 100 : 0;

        return [
            'code' => $iku->code,
            'name' => $iku->name,
            'description' => "$involvedMhs mahasiswa terlibat dalam riset/PKM dari estimasi $totalMhs mahasiswa.",
            'achievement' => $percentage,
            'target' => (float) $iku->target_percentage,
        ];
    }

    /**
     * IKU 4: Rekognisi Internasional Dosen
     * Rumus: (Dosen rekognisi int) / Total Dosen PT * 100
     */
    protected function calculateIku04(string $period, MasterIku $iku): array
    {
        $totalLecturers = Identity::where('type', 'dosen')->count();
        $lecturersWithRecognition = Identity::where('type', 'dosen')
            ->where(function ($q) {
                $q->whereNotNull('scopus_id')
                    ->orWhereNotNull('wos_id')
                    ->orWhereNotNull('sinta_id');
            })->count();

        $percentage = $totalLecturers > 0 ? ($lecturersWithRecognition / $totalLecturers) * 100 : 0;

        return [
            'code' => $iku->code,
            'name' => $iku->name,
            'description' => "$lecturersWithRecognition dari $totalLecturers dosen memiliki ID Rekognisi (Scopus/SINTA).",
            'achievement' => $percentage,
            'target' => (float) $iku->target_percentage,
        ];
    }

    /**
     * IKU 5: Luaran Kerja Sama Mitra
     * Rumus: (Jumlah Luaran Kerjasama) / Total Dosen PT * 100
     */
    protected function calculateIku05(string $period, MasterIku $iku): array
    {
        $totalDosen = Identity::where('type', 'dosen')->count();
        $proposalsWithPartners = Proposal::query()->forAcademicYear($period)
            ->whereHas('partners')
            ->count();

        $percentage = $totalDosen > 0 ? ($proposalsWithPartners / $totalDosen) * 100 : 0;

        return [
            'code' => $iku->code,
            'name' => $iku->name,
            'description' => "$proposalsWithPartners luaran kerjasama terdeteksi dari $totalDosen dosen tetap.",
            'achievement' => $percentage,
            'target' => (float) $iku->target_percentage,
        ];
    }

    /**
     * IKU 6: Publikasi Global
     * Rumus: (Bobot Publikasi + Bonus Kolab) / Total Publikasi PT * 100
     */
    protected function calculateIku06(string $period, MasterIku $iku): array
    {
        $mandatoryQuery = MandatoryOutput::whereHas('progressReport.proposal', fn ($q) => Proposal::applyAcademicYearFilter($q, $period));
        $additionalQuery = AdditionalOutput::whereHas('progressReport.proposal', fn ($q) => Proposal::applyAcademicYearFilter($q, $period));

        $totalOutputs = $mandatoryQuery->count() + $additionalQuery->count();
        $currentWeight = 0;

        foreach ($mandatoryQuery->where('is_verified', true)->get() as $output) {
            $currentWeight += $this->getJournalWeightKepmen($output);
        }

        foreach ($additionalQuery->where('is_verified', true)->get() as $output) {
            $currentWeight += $this->getJournalWeightKepmen($output);
        }

        $percentage = $totalOutputs > 0 ? ($currentWeight / $totalOutputs) * 100 : 0;

        return [
            'code' => $iku->code,
            'name' => $iku->name,
            'description' => 'Total bobot publikasi terverifikasi: '.round($currentWeight, 2)." dari $totalOutputs artikel.",
            'achievement' => $percentage,
            'target' => (float) $iku->target_percentage,
        ];
    }

    private function getJournalWeightKepmen($output): float
    {
        $rank = strtoupper((string) $output->rank);
        $weight = match ($rank) {
            'Q1' => 1.0,
            'Q2' => 0.75,
            'Q3' => 0.50,
            'Q4' => 0.25,
            'PROSIDING' => 0.25,
            default => 0,
        };

        // Bonus International Collaboration (Check if partner exists)
        if ($output->progressReport->proposal->partners()->where('country', '!=', 'Indonesia')->exists()) {
            $weight += 0.25;
        }

        return $weight;
    }

    /**
     * IKU 7: Keterlibatan SDGs
     */
    protected function calculateIku07(string $period, MasterIku $iku): array
    {
        $totalProposals = Proposal::query()->forAcademicYear($period)->count();
        $sdgProposals = Proposal::query()->forAcademicYear($period)->has('sdgs')->count();

        $percentage = $totalProposals > 0 ? ($sdgProposals / $totalProposals) * 100 : 0;

        return [
            'code' => $iku->code,
            'name' => $iku->name,
            'description' => "$sdgProposals kegiatan Tri Dharma mendukung target SDGs.",
            'achievement' => $percentage,
            'target' => (float) $iku->target_percentage,
        ];
    }

    /**
     * IKU 8: SDM Terlibat Penyusunan Kebijakan
     */
    protected function calculateIku08(string $period, MasterIku $iku): array
    {
        $totalDosen = User::role('dosen')->count();
        $policyDosen = User::role('dosen')
            ->whereHas('policyInvolvements', fn ($q) => $q->where('status', 'verified'))
            ->count();

        $percentage = $totalDosen > 0 ? ($policyDosen / $totalDosen) * 100 : 0;

        return [
            'code' => $iku->code,
            'name' => $iku->name,
            'description' => "$policyDosen dari $totalDosen dosen mendapatkan rekognisi pakar/kebijakan.",
            'achievement' => $percentage,
            'target' => (float) $iku->target_percentage,
        ];
    }

    /**
     * IKU 9: Pendapatan Non-UKT
     */
    protected function calculateIku09(string $period, MasterIku $iku): array
    {
        return [
            'code' => $iku->code,
            'name' => $iku->name,
            'description' => 'Pendapatan dari hibah eksternal dan kerjasama (Mode Manual Disarankan).',
            'achievement' => 0,
            'target' => (float) $iku->target_percentage,
        ];
    }

    /**
     * IKU 11c: Integritas Akademik
     */
    protected function calculateIku11c(string $period, MasterIku $iku): array
    {
        return [
            'code' => $iku->code,
            'name' => $iku->name,
            'description' => 'Pencegahan plagiasi dan pelanggaran etik akademik.',
            'achievement' => 100, // Default 100% jika tidak ada pelanggaran
            'target' => (float) $iku->target_percentage,
        ];
    }

    /**
     * Get detailed data for a specific IKU metric.
     */
    public function getIkuDetails(string $ikuCode, string $period, string $search = ''): array
    {
        // Add support for new IKUs in details view if needed
        return match (strtoupper(str_replace('-', '', $ikuCode))) {
            'IKU04' => $this->getIku4Details($period, $search),
            'IKU05' => $this->getIku5Details($period, $search),
            'IKU06' => $this->getIku6Details($period, $search),
            'IKU07' => $this->getIku7Details($period, $search),
            'IKU08' => $this->getIku8Details($period, $search),
            default => [],
        };
    }

    protected function getIku4Details(string $period, string $search = ''): array
    {
        return Identity::where('type', 'dosen')
            ->where(function ($q) {
                $q->whereNotNull('scopus_id')
                    ->orWhereNotNull('wos_id')
                    ->orWhereNotNull('sinta_id');
            })
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhere('identity_id', 'like', "%{$search}%");
            })
            ->with('user')
            ->get()
            ->map(fn ($id) => [
                'name' => $id->user->name ?? 'N/A',
                'id_number' => $id->identity_id,
                'scopus' => $id->scopus_id,
                'sinta' => $id->sinta_id,
                'wos' => $id->wos_id,
            ])
            ->toArray();
    }

    protected function getIku5Details(string $period, string $search = ''): array
    {
        return Proposal::query()->forAcademicYear($period)
            ->with(['partners', 'submitter'])
            ->whereHas('partners')
            ->when($search, function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('submitter', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            })
            ->get()
            ->map(fn ($p) => [
                'title' => $p->title,
                'submitter' => $p->submitter->name,
                'partners' => $p->partners->pluck('name')->implode(', '),
                'weight' => 1.0,
            ])
            ->toArray();
    }

    protected function getIku6Details(string $period, string $search = ''): array
    {
        $mandatory = MandatoryOutput::whereHas('progressReport.proposal', fn ($q) => Proposal::applyAcademicYearFilter($q, $period))
            ->where('is_verified', true)->with('progressReport.proposal')->get();
        $additional = AdditionalOutput::whereHas('progressReport.proposal', fn ($q) => Proposal::applyAcademicYearFilter($q, $period))
            ->where('is_verified', true)->with('progressReport.proposal')->get();

        return $mandatory->concat($additional)->map(fn ($o) => [
            'title' => $o->article_title ?? 'N/A',
            'journal' => $o->journal_title ?? 'N/A',
            'rank' => $o->rank ?? 'N/A',
            'indexing' => $o->indexing_body ?? 'N/A',
            'weight' => $this->getJournalWeightKepmen($o),
        ])->toArray();
    }

    protected function getIku7Details(string $period, string $search = ''): array
    {
        return Proposal::query()->forAcademicYear($period)->has('sdgs')->with(['sdgs', 'submitter'])->get()
            ->map(fn ($p) => [
                'title' => $p->title,
                'submitter' => $p->submitter->name,
                'sdgs' => $p->sdgs->pluck('name')->implode(', '),
            ])->toArray();
    }

    protected function getIku8Details(string $period, string $search = ''): array
    {
        return User::role('dosen')
            ->whereHas('policyInvolvements', fn ($q) => $q->where('status', 'verified'))
            ->with('policyInvolvements')
            ->get()
            ->map(fn ($u) => [
                'name' => $u->name,
                'policies' => $u->policyInvolvements->pluck('title')->implode(', '),
            ])->toArray();
    }
}
