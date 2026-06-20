<?php

namespace Database\Seeders;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Models\AdditionalOutput;
use App\Models\BudgetComponent;
use App\Models\BudgetGroup;
use App\Models\BudgetItem;
use App\Models\DailyNote;
use App\Models\FocusArea;
use App\Models\Keyword;
use App\Models\MacroResearchGroup;
use App\Models\MandatoryOutput;
use App\Models\NationalPriority;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalOutput;
use App\Models\ProposalReviewer;
use App\Models\ProposalStatusLog;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\ReviewCriteria;
use App\Models\ReviewLog;
use App\Models\ReviewScore;
use App\Models\ScienceCluster;
use App\Models\Theme;
use App\Models\TktLevel;
use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ResearchSeeder extends Seeder
{
    public function run(): void
    {
        // Retrieve all necessary roles
        $dosenUsers = User::role('dosen')->get();
        $reviewerUsers = User::role('reviewer')->get();
        $dekanUsers = User::role('dekan')->get();
        $kepalaLppm = User::role('kepala lppm')->first();
        $adminLppm = User::role('admin lppm')->first();

        if ($dosenUsers->count() < 2) {
            $this->command->warn('Tidak cukup dosen untuk membuat proposal penelitian');

            return;
        }

        if (! $kepalaLppm || ! $adminLppm) {
            $this->command->warn('Role Kepala LPPM atau Admin LPPM belum ada. Jalankan RoleSeeder dulu.');

            return;
        }

        $keywords = Keyword::all();
        $researchSchemes = ResearchScheme::all();
        $focusAreas = FocusArea::all();
        $themes = Theme::all();
        $topics = Topic::all();
        $nationalPriorities = NationalPriority::all();

        if ($keywords->isEmpty() || $researchSchemes->isEmpty() || $focusAreas->isEmpty()) {
            $this->command->warn('Master data tidak lengkap untuk membuat proposal');

            return;
        }

        $researchTitles = [
            'Artificial Intelligence & Machine Learning' => [
                'Pengembangan Model Machine Learning untuk Prediksi Penyakit Berbasis Data Klinis',
                'Sistem Rekomendasi Produk Menggunakan Deep Learning dan Collaborative Filtering',
                'Analisis Sentimen Media Sosial dengan Natural Language Processing untuk Monitoring Merek',
                'Deteksi Fraud E-Commerce Menggunakan Ensemble Machine Learning Methods',
            ],
            'Cloud Computing & IoT' => [
                'Implementasi IoT untuk Monitoring Efisiensi Energi Terbarukan di Smart Grid',
                'Arsitektur Sistem Manajemen Informasi Akademik Berbasis Cloud Computing Microservices',
                'Platform IoT Terdistribusi untuk Precision Agriculture di Indonesia',
                'Sistem Monitoring Kualitas Udara Real-time Menggunakan Sensor Jaringan IoT',
            ],
            'Cybersecurity & Blockchain' => [
                'Teknologi Blockchain untuk Transparansi Supply Chain Produk Halal Indonesia',
                'Sistem Keamanan Multi-Layer pada Transaksi E-Commerce Menggunakan Cryptography Modern',
                'Smart Contract untuk Verifikasi Kepemilikan Aset Digital di Sektor Keuangan',
                'Analisis Vulnerabilitas dan Protokol Keamanan Sistem Informasi Publik',
            ],
            'Data Science & Analytics' => [
                'Analisis Big Data untuk Optimasi Performa Database dengan Query Optimization Techniques',
                'Sistem Business Intelligence untuk Prediksi Tren Pasar Saham Indonesia',
                'Data Mining pada Log Server untuk Deteksi Anomali Sistem Informasi',
                'Analisis Pola Migrasi Penduduk Menggunakan Spatial Data Analysis',
            ],
            'Software Engineering' => [
                'Metodologi DevOps untuk Continuous Integration/Continuous Deployment di Perusahaan Startup',
                'Testing Automation Framework untuk Meningkatkan Kualitas Software Development',
                'Refactoring Legacy Code dengan Design Patterns Modern',
                'Sistem Versioning Terdistribusi untuk Collaborative Development Teams',
            ],
        ];

        $flatTitles = array_reduce($researchTitles, fn ($carry, $category) => array_merge($carry, $category), []);

        $validStatuses = [
            ProposalStatus::DRAFT,
            ProposalStatus::SUBMITTED,
            ProposalStatus::APPROVED,
            ProposalStatus::WAITING_REVIEWER,
            ProposalStatus::UNDER_REVIEW,
            ProposalStatus::REVIEWED,
            ProposalStatus::REVISION_NEEDED,
            ProposalStatus::COMPLETED,
            ProposalStatus::REJECTED,
        ];

        $titleIndex = 0;

        foreach ($dosenUsers->take(5) as $submitter) {
            foreach ($validStatuses as $statusEnum) {
                // Determine TKT based on scheme strata
                $scheme = $researchSchemes->random();
                $tktTarget = match ($scheme->strata) {
                    'Reguler' => rand(1, 3),
                    'Kolaborasi Internal' => rand(4, 6),
                    'Kerja Sama Antar PT', 'PKM-Reguler', 'PKM-KI', 'PKM-KE' => rand(7, 9),
                    default => rand(1, 3)
                };

                $focusArea = $focusAreas->random();
                $theme = $themes->where('focus_area_id', $focusArea->id)->first() ?? $themes->random();
                $topic = $topics->where('theme_id', $theme->id)->first() ?? $topics->random();

                $cluster3 = ScienceCluster::where('level', 3)->inRandomOrder()->first();
                $cluster2 = $cluster3 ? ScienceCluster::find($cluster3->parent_id) : null;
                $cluster1 = $cluster2 ? ScienceCluster::find($cluster2->parent_id) : null;

                // Base Date: 10 days ago
                $baseCreatedAt = Carbon::now()->subDays(10)->addHours(rand(1, 23));

                $research = Research::factory()->create([
                    'macro_research_group_id' => MacroResearchGroup::inRandomOrder()->first()?->id,
                    'created_at' => $baseCreatedAt,
                    'updated_at' => $baseCreatedAt,
                ]);

                // Attach TKT Level
                $tktLevel = TktLevel::where('level', $tktTarget)->first();
                if ($tktLevel) {
                    $research->tktLevels()->attach($tktLevel->id, ['percentage' => 100]);
                }

                $title = $flatTitles[$titleIndex % count($flatTitles)];
                $titleIndex++;

                $proposal = Proposal::factory()->create([
                    'title' => $title,
                    'detailable_type' => Research::class,
                    'detailable_id' => $research->id,
                    'submitter_id' => $submitter->id,
                    'research_scheme_id' => $scheme->id,
                    'focus_area_id' => $focusArea->id,
                    'theme_id' => $theme->id,
                    'topic_id' => $topic->id,
                    'national_priority_id' => $nationalPriorities->isNotEmpty() ? $nationalPriorities->random()->id : null,
                    'cluster_level1_id' => $cluster1?->id,
                    'cluster_level2_id' => $cluster2?->id,
                    'cluster_level3_id' => $cluster3?->id,
                    'status' => $statusEnum,
                    'duration_in_years' => rand(1, 3),
                    'start_year' => (int) date('Y'),
                    'sbk_value' => rand(50, 300) * 1000000,
                    'summary' => fake()->paragraph(3),
                    'created_at' => $baseCreatedAt,
                    'updated_at' => $baseCreatedAt,
                ]);

                // Create Status Log History (Sequential)
                $this->createStatusLogHistory($proposal, $statusEnum, $submitter, $dekanUsers, $kepalaLppm, $adminLppm);

                // Update proposal updated_at to match last log
                $lastLog = $proposal->statusLogs()->latest('at')->first();
                if ($lastLog) {
                    $proposal->update(['updated_at' => $lastLog->getAttribute('at')]);
                }

                // Team Members (Ketua + Members)
                $proposal->teamMembers()->attach($submitter->id, [
                    'role' => 'ketua',
                    'status' => 'accepted',
                    'tasks' => 'Penanggung jawab utama penelitian.',
                    'created_at' => $baseCreatedAt,
                    'updated_at' => $baseCreatedAt,
                ]);

                $teamMemberStatus = in_array($statusEnum, [ProposalStatus::DRAFT, ProposalStatus::SUBMITTED]) ? 'pending' : 'accepted';
                $availableMembers = $dosenUsers->where('id', '!=', $submitter->id)->random(min(rand(1, 2), $dosenUsers->count() - 1));

                foreach ($availableMembers as $member) {
                    $proposal->teamMembers()->attach($member->id, [
                        'role' => 'anggota',
                        'status' => $teamMemberStatus,
                        'tasks' => fake()->sentence(10),
                        'created_at' => $baseCreatedAt,
                        'updated_at' => $baseCreatedAt,
                    ]);
                }

                // Targets
                $mandatoryTarget = ProposalOutput::factory()->create([
                    'proposal_id' => $proposal->id,
                    'category' => 'Wajib',
                    'type' => $scheme->strata === 'Kolaborasi Internal' ? 'Purwarupa/Prototipe' : 'Jurnal Nasional Sinta 1-2',
                    'target_status' => 'Published',
                    'output_year' => $proposal->duration_in_years,
                ]);

                $additionalTarget = ProposalOutput::factory()->create([
                    'proposal_id' => $proposal->id,
                    'category' => 'Tambahan',
                    'type' => 'Prosiding Seminar Internasional',
                    'target_status' => 'Accepted',
                    'output_year' => 1,
                ]);

                // Reviewers & Rounds
                if (in_array($statusEnum, [
                    ProposalStatus::UNDER_REVIEW,
                    ProposalStatus::REVIEWED,
                    ProposalStatus::REVISION_NEEDED,
                    ProposalStatus::COMPLETED,
                ])) {
                    $this->seedReviewers($proposal, $statusEnum, $reviewerUsers, $submitter, $availableMembers);
                }

                // Progress Reports & Realization
                if (in_array($statusEnum, [ProposalStatus::REVIEWED, ProposalStatus::COMPLETED])) {
                    $this->seedReports($proposal, $mandatoryTarget, $additionalTarget, $submitter);
                }

                // Budget (RAB)
                $this->seedBudget($proposal);

                // Daily Notes (Must be after approval/completion)
                if ($statusEnum === ProposalStatus::COMPLETED) {
                    $approvalDate = $proposal->statusLogs()->where('status_after', ProposalStatus::APPROVED)->value('at') ?? $baseCreatedAt;
                    for ($i = 0; $i < 5; $i++) {
                        DailyNote::create([
                            'proposal_id' => $proposal->id,
                            'activity_date' => Carbon::parse($approvalDate)->addDays(rand(1, 20)),
                            'activity_description' => fake()->sentence(20),
                            'progress_percentage' => ($i + 1) * 20,
                        ]);
                    }
                }
            }
        }

        $this->command->info('ResearchSeeder completed successfully.');
    }

    protected function seedBudget($proposal): void
    {
        $groups = BudgetGroup::all();
        if ($groups->isEmpty()) {
            return;
        }

        $totalSbk = $proposal->sbk_value ?: 20000000;
        $remainingBudget = $totalSbk;

        foreach ($groups as $group) {
            $components = BudgetComponent::where('budget_group_id', $group->id)->inRandomOrder()->take(2)->get();

            foreach ($components as $component) {
                $targetPercentage = $group->percentage / 2 / 100; // Divide group percentage by number of components
                $amount = round($totalSbk * $targetPercentage);

                if ($amount > $remainingBudget) {
                    $amount = $remainingBudget;
                }

                if ($amount <= 0) {
                    continue;
                }

                BudgetItem::create([
                    'proposal_id' => $proposal->id,
                    'year' => 1,
                    'budget_group_id' => $group->id,
                    'budget_component_id' => $component->id,
                    'group' => $group->name,
                    'component' => $component->name,
                    'item_description' => 'Kebutuhan '.$component->name,
                    'volume' => 1,
                    'unit_price' => $amount,
                    'total_price' => $amount,
                ]);

                $remainingBudget -= $amount;
            }
        }
    }

    protected function seedReviewers($proposal, $status, $reviewerUsers, $submitter, $teamMembers): void
    {
        if ($reviewerUsers->isEmpty()) {
            return;
        }

        // Logic for rounds: COMPLETED status implies Round 2 (Round 1 was revision)
        $currentRound = ($status === ProposalStatus::COMPLETED) ? 2 : 1;

        $reviewers = $reviewerUsers->random(min(2, $reviewerUsers->count()));
        $criterias = ReviewCriteria::where('type', 'research')->where('is_active', true)->get();

        // Find assignment date from logs
        $assignedAt = $proposal->statusLogs()->where('status_after', ProposalStatus::UNDER_REVIEW)->value('at')
            ?? $proposal->created_at->addDays(3);

        foreach ($reviewers as $reviewer) {
            $isCompleted = ! in_array($status, [
                ProposalStatus::UNDER_REVIEW,
                ProposalStatus::WAITING_REVIEWER,
                ProposalStatus::APPROVED,
                ProposalStatus::SUBMITTED,
                ProposalStatus::DRAFT,
            ]);

            $recommendation = $isCompleted ? ($status === ProposalStatus::REVISION_NEEDED ? 'revision_needed' : 'approved') : null;
            $notes = $isCompleted ? fake()->paragraph(2) : null;
            $completedAt = $isCompleted ? Carbon::parse($assignedAt)->addDays(3) : null;

            $assignment = ProposalReviewer::create([
                'proposal_id' => $proposal->id,
                'user_id' => $reviewer->id,
                'status' => $isCompleted ? ReviewStatus::COMPLETED : ReviewStatus::PENDING,
                'review_notes' => $notes,
                'recommendation' => $recommendation,
                'round' => $currentRound,
                'assigned_at' => $assignedAt,
                'started_at' => $isCompleted ? Carbon::parse($assignedAt)->addDays(1) : null,
                'completed_at' => $completedAt,
                'deadline_at' => Carbon::parse($assignedAt)->addDays(14),
            ]);

            if ($isCompleted) {
                $this->createScoresAndLog($assignment, $currentRound, $criterias);

                // If we are in Round 2, we must simulate the previous Round 1 (revision_needed)
                if ($currentRound === 2) {
                    $this->createScoresAndLog($assignment, 1, $criterias, 'revision_needed');
                }
            }
        }
    }

    protected function createScoresAndLog($assignment, $round, $criterias, $forcedRecommendation = null): void
    {
        $recommendation = $forcedRecommendation ?? $assignment->recommendation ?? 'approved';
        $notes = ($recommendation === 'revision_needed') ? 'Mohon perbaiki bagian metodologi dan luaran.' : ($assignment->review_notes ?? fake()->paragraph(2));
        $totalScore = 0;

        foreach ($criterias as $criteria) {
            // Lower scores for revision_needed, higher for approved
            $score = ($recommendation === 'approved') ? rand(4, 5) : rand(2, 3);
            $val = $score * $criteria->weight;
            $totalScore += $val;

            ReviewScore::create([
                'proposal_reviewer_id' => $assignment->id,
                'review_criteria_id' => $criteria->id,
                'acuan' => fake()->sentence(10),
                'score' => $score,
                'weight_snapshot' => $criteria->weight,
                'value' => $val,
                'round' => $round,
            ]);
        }

        ReviewLog::create([
            'proposal_reviewer_id' => $assignment->id,
            'proposal_id' => $assignment->proposal_id,
            'user_id' => $assignment->user_id,
            'round' => $round,
            'review_notes' => $notes,
            'recommendation' => $recommendation,
            'total_score' => $totalScore,
            'started_at' => Carbon::parse($assignment->assigned_at)->subDays(($round == 1 && $assignment->round == 2) ? 10 : 0)->addDay(),
            'completed_at' => Carbon::parse($assignment->assigned_at)->subDays(($round == 1 && $assignment->round == 2) ? 10 : 0)->addDays(3),
        ]);
    }

    protected function seedReports($proposal, $mandatoryTarget, $additionalTarget, $submitter): void
    {
        $completionDate = $proposal->statusLogs()->where('status_after', ProposalStatus::COMPLETED)->value('at')
            ?? $proposal->statusLogs()->where('status_after', ProposalStatus::REVIEWED)->value('at')
            ?? Carbon::now();

        // Semester 1 Report (10 days after completion/review)
        $report = ProgressReport::create([
            'proposal_id' => $proposal->id,
            'reporting_year' => date('Y'),
            'reporting_period' => 'semester_1',
            'status' => ReportStatus::SUBMITTED,
            'summary_update' => fake()->paragraph(2),
            'submitted_by' => $submitter->id,
            'submitted_at' => Carbon::parse($completionDate)->addDays(10),
        ]);

        // Realize Mandatory Output
        MandatoryOutput::create([
            'progress_report_id' => $report->id,
            'proposal_output_id' => $mandatoryTarget->id,
            'status_type' => 'published',
            'author_status' => 'corresponding_author',
            'journal_title' => 'International Journal of AI',
            'article_title' => 'Advanced implementation of '.$proposal->title,
            'publication_year' => date('Y'),
            'volume' => '12',
            'issue_number' => '3',
            'doi' => '10.1234/ai.'.rand(100, 999),
        ]);

        // Realize Additional Output
        AdditionalOutput::create([
            'progress_report_id' => $report->id,
            'proposal_output_id' => $additionalTarget->id,
            'status' => 'published',
            'book_title' => 'Buku Hasil Penelitian: '.$proposal->title,
            'publisher_name' => 'ITSNU Press',
            'isbn' => fake()->isbn13(),
            'publication_year' => (int) date('Y'),
            'total_pages' => rand(50, 200),
        ]);

        if ($proposal->status === ProposalStatus::COMPLETED) {
            // Final Report (20 days after completion)
            ProgressReport::create([
                'proposal_id' => $proposal->id,
                'reporting_year' => date('Y'),
                'reporting_period' => 'final',
                'status' => ReportStatus::SUBMITTED,
                'summary_update' => 'Penelitian telah diselesaikan dengan hasil memuaskan.',
                'submitted_by' => $submitter->id,
                'submitted_at' => Carbon::parse($completionDate)->addDays(20),
            ]);
        }
    }

    protected function createStatusLogHistory(Proposal $proposal, ProposalStatus $finalStatus, User $submitter, Collection $dekanUsers, User $kepalaLppm, User $adminLppm): void
    {
        $baseTime = Carbon::parse($proposal->created_at);
        $facultyId = $submitter->identity?->faculty_id;
        $dekan = $dekanUsers->first(fn ($u) => $u->identity?->faculty_id === $facultyId) ?? $dekanUsers->first();

        $path = match ($finalStatus) {
            ProposalStatus::DRAFT => [],
            ProposalStatus::SUBMITTED => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
            ],
            ProposalStatus::NEED_ASSIGNMENT => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
                ['f' => ProposalStatus::SUBMITTED, 't' => ProposalStatus::NEED_ASSIGNMENT, 'u' => $submitter, 'd' => 1],
            ],
            ProposalStatus::APPROVED => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
                ['f' => ProposalStatus::SUBMITTED, 't' => ProposalStatus::APPROVED, 'u' => $dekan, 'd' => 2],
            ],
            ProposalStatus::WAITING_REVIEWER => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
                ['f' => ProposalStatus::SUBMITTED, 't' => ProposalStatus::APPROVED, 'u' => $dekan, 'd' => 2],
                ['f' => ProposalStatus::APPROVED, 't' => ProposalStatus::WAITING_REVIEWER, 'u' => $kepalaLppm, 'd' => 4],
            ],
            ProposalStatus::UNDER_REVIEW => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
                ['f' => ProposalStatus::SUBMITTED, 't' => ProposalStatus::APPROVED, 'u' => $dekan, 'd' => 2],
                ['f' => ProposalStatus::APPROVED, 't' => ProposalStatus::WAITING_REVIEWER, 'u' => $kepalaLppm, 'd' => 4],
                ['f' => ProposalStatus::WAITING_REVIEWER, 't' => ProposalStatus::UNDER_REVIEW, 'u' => $adminLppm, 'd' => 5],
            ],
            ProposalStatus::REVIEWED => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
                ['f' => ProposalStatus::SUBMITTED, 't' => ProposalStatus::APPROVED, 'u' => $dekan, 'd' => 2],
                ['f' => ProposalStatus::APPROVED, 't' => ProposalStatus::WAITING_REVIEWER, 'u' => $kepalaLppm, 'd' => 4],
                ['f' => ProposalStatus::WAITING_REVIEWER, 't' => ProposalStatus::UNDER_REVIEW, 'u' => $adminLppm, 'd' => 5],
                ['f' => ProposalStatus::UNDER_REVIEW, 't' => ProposalStatus::REVIEWED, 'u' => $adminLppm, 'd' => 15],
            ],
            ProposalStatus::REVISION_NEEDED => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
                ['f' => ProposalStatus::SUBMITTED, 't' => ProposalStatus::APPROVED, 'u' => $dekan, 'd' => 2],
                ['f' => ProposalStatus::APPROVED, 't' => ProposalStatus::WAITING_REVIEWER, 'u' => $kepalaLppm, 'd' => 4],
                ['f' => ProposalStatus::WAITING_REVIEWER, 't' => ProposalStatus::UNDER_REVIEW, 'u' => $adminLppm, 'd' => 5],
                ['f' => ProposalStatus::UNDER_REVIEW, 't' => ProposalStatus::REVIEWED, 'u' => $adminLppm, 'd' => 15],
                ['f' => ProposalStatus::REVIEWED, 't' => ProposalStatus::REVISION_NEEDED, 'u' => $kepalaLppm, 'd' => 16],
            ],
            ProposalStatus::COMPLETED => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
                ['f' => ProposalStatus::SUBMITTED, 't' => ProposalStatus::APPROVED, 'u' => $dekan, 'd' => 2],
                ['f' => ProposalStatus::APPROVED, 't' => ProposalStatus::WAITING_REVIEWER, 'u' => $kepalaLppm, 'd' => 4],
                ['f' => ProposalStatus::WAITING_REVIEWER, 't' => ProposalStatus::UNDER_REVIEW, 'u' => $adminLppm, 'd' => 5],
                ['f' => ProposalStatus::UNDER_REVIEW, 't' => ProposalStatus::REVIEWED, 'u' => $adminLppm, 'd' => 15],
                ['f' => ProposalStatus::REVIEWED, 't' => ProposalStatus::COMPLETED, 'u' => $kepalaLppm, 'd' => 18],
            ],
            ProposalStatus::REJECTED => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
                ['f' => ProposalStatus::SUBMITTED, 't' => ProposalStatus::REJECTED, 'u' => $dekan, 'd' => 2],
            ],
        };

        foreach ($path as $step) {
            $actor = $step['u'] ?? $adminLppm;

            ProposalStatusLog::create([
                'proposal_id' => $proposal->id,
                'user_id' => $actor->id,
                'status_before' => $step['f'],
                'status_after' => $step['t'],
                'at' => $baseTime->copy()->addDays($step['d']),
            ]);
        }
    }
}
