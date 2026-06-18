<?php

namespace Database\Seeders;

use App\Enums\ProposalStatus;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Models\AdditionalOutput;
use App\Models\BudgetComponent;
use App\Models\BudgetGroup;
use App\Models\BudgetItem;
use App\Models\CommunityService;
use App\Models\FocusArea;
use App\Models\Keyword;
use App\Models\MandatoryOutput;
use App\Models\Partner;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalOutput;
use App\Models\ProposalReviewer;
use App\Models\ProposalStatusLog;
use App\Models\ResearchScheme;
use App\Models\ReviewCriteria;
use App\Models\ReviewLog;
use App\Models\ReviewScore;
use App\Models\ScienceCluster;
use App\Models\Theme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CommunityServiceSeeder extends Seeder
{
    public function run(): void
    {
        $dosenUsers = User::role('dosen')->get();
        $reviewerUsers = User::role('reviewer')->get();
        $dekanUsers = User::role('dekan')->get();
        $kepalaLppm = User::role('kepala lppm')->first();
        $adminLppm = User::role('admin lppm')->first();

        if ($dosenUsers->count() < 2) {
            $this->command->warn('Tidak cukup dosen untuk membuat proposal PKM');

            return;
        }

        $keywords = Keyword::all();
        $researchSchemes = ResearchScheme::all();
        $focusAreas = FocusArea::all();
        $themes = Theme::all();
        $partners = Partner::all();

        if ($keywords->isEmpty() || $researchSchemes->isEmpty() || $focusAreas->isEmpty()) {
            $this->command->warn('Master data tidak lengkap untuk membuat proposal PKM');

            return;
        }

        $pkamTitles = [
            'Digital Transformation' => [
                'Program Literasi Digital untuk UMKM di Kota Pekalongan',
                'Pelatihan Sistem Informasi Keuangan untuk Koperasi Simpan Pinjam Desa Jepara',
                'Workshop Platform E-Commerce untuk Pengrajin Batik Tradisional',
                'Program Pelatihan Digital Marketing dan Social Media untuk Pengusaha Muda',
            ],
            'Pertanian & Lingkungan' => [
                'Pendampingan Teknologi Precision Agriculture untuk Petani Modern',
                'Program Pelatihan Budidaya Organik Berkelanjutan untuk Petani Pekalongan',
                'Workshop Pengelolaan Limbah Pertanian Menjadi Produk Bernilai Tambah',
                'Pendampingan Sistem Irigasi Pintar untuk Optimasi Penggunaan Air',
            ],
            'Pendidikan & Pemberdayaan' => [
                'Program Coding dan Robotika untuk Anak Kurang Mampu',
                'Pelatihan Keterampilan STEM untuk Siswa SMP di Daerah Terpencil',
                'Workshop Kewirausahaan dan Business Plan untuk Anak Muda Pengangguran',
                'Program Mentoring Soft Skills dan Leadership untuk Mahasiswa Kurang Mampu',
            ],
        ];

        $flatTitles = array_reduce($pkamTitles, fn ($carry, $category) => array_merge($carry, $category), []);

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
                $focusArea = $focusAreas->random();
                $theme = $themes->where('focus_area_id', $focusArea->id)->first() ?? $themes->random();

                $cluster3 = ScienceCluster::where('level', 3)->inRandomOrder()->first();
                $cluster2 = $cluster3 ? ScienceCluster::find($cluster3->parent_id) : null;
                $cluster1 = $cluster2 ? ScienceCluster::find($cluster2->parent_id) : null;

                $partner = $partners->isNotEmpty() ? $partners->random() : Partner::factory()->create();

                // Base Date: 10 days ago
                $baseCreatedAt = Carbon::now()->subDays(10)->addHours(rand(1, 23));

                $communityService = CommunityService::factory()->create([
                    'partner_id' => $partner->id,
                    'created_at' => $baseCreatedAt,
                    'updated_at' => $baseCreatedAt,
                ]);

                $title = $flatTitles[$titleIndex % count($flatTitles)];
                $titleIndex++;

                $proposal = Proposal::factory()->create([
                    'title' => 'Pengabdian Masyarakat: '.$title,
                    'detailable_type' => CommunityService::class,
                    'detailable_id' => $communityService->id,
                    'submitter_id' => $submitter->id,
                    'research_scheme_id' => $researchSchemes->random()->id,
                    'focus_area_id' => $focusArea->id,
                    'theme_id' => $theme->id,
                    'cluster_level1_id' => $cluster1?->id,
                    'cluster_level2_id' => $cluster2?->id,
                    'cluster_level3_id' => $cluster3?->id,
                    'status' => $statusEnum,
                    'duration_in_years' => rand(1, 2),
                    'start_year' => (int) date('Y'),
                    'sbk_value' => rand(20, 150) * 1000000,
                    'summary' => fake()->paragraph(3),
                    'created_at' => $baseCreatedAt,
                    'updated_at' => $baseCreatedAt,
                ]);

                // Attach Mitra (Partner) to Proposal
                $proposal->partners()->attach($partner->id);

                $this->createStatusLogHistory($proposal, $statusEnum, $submitter, $dekanUsers, $kepalaLppm, $adminLppm);

                // Update proposal updated_at to match last log
                $lastLog = $proposal->statusLogs()->latest('at')->first();
                if ($lastLog) {
                    $proposal->update(['updated_at' => $lastLog->getAttribute('at')]);
                }

                // Team
                $proposal->teamMembers()->attach($submitter->id, [
                    'role' => 'ketua',
                    'status' => 'accepted',
                    'created_at' => $baseCreatedAt,
                    'updated_at' => $baseCreatedAt,
                ]);
                $teamMemberStatus = in_array($statusEnum, [ProposalStatus::DRAFT, ProposalStatus::SUBMITTED]) ? 'pending' : 'accepted';
                $availableMembers = $dosenUsers->where('id', '!=', $submitter->id)->random(min(rand(2, 3), $dosenUsers->count() - 1));

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
                    'type' => 'Video Kegiatan (Youtube)',
                    'target_status' => 'Uploaded',
                    'output_year' => 1,
                ]);

                $additionalTarget = ProposalOutput::factory()->create([
                    'proposal_id' => $proposal->id,
                    'category' => 'Tambahan',
                    'type' => 'Publikasi Media Massa',
                    'target_status' => 'Published',
                    'output_year' => 1,
                ]);

                // Reviewers
                if (in_array($statusEnum, [ProposalStatus::UNDER_REVIEW, ProposalStatus::REVIEWED, ProposalStatus::REVISION_NEEDED, ProposalStatus::COMPLETED])) {
                    $this->seedReviewers($proposal, $statusEnum, $reviewerUsers, $submitter, $availableMembers);
                }

                // Reports
                if (in_array($statusEnum, [ProposalStatus::REVIEWED, ProposalStatus::COMPLETED])) {
                    $this->seedReports($proposal, $mandatoryTarget, $additionalTarget, $submitter);
                }

                // Budget (RAB)
                $this->seedBudget($proposal);
            }
        }

        $this->command->info('CommunityServiceSeeder completed successfully.');
    }

    protected function seedBudget($proposal): void
    {
        $groups = BudgetGroup::all();
        if ($groups->isEmpty()) {
            return;
        }

        $totalSbk = $proposal->sbk_value ?: 15000000;
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

        // Logic for rounds: COMPLETED implies Round 2
        $currentRound = ($status === ProposalStatus::COMPLETED) ? 2 : 1;

        $reviewers = $reviewerUsers->random(min(2, $reviewerUsers->count()));
        $criterias = ReviewCriteria::where('type', 'community_service')->where('is_active', true)->get();

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

                // If we are in Round 2, simulate Round 1
                if ($currentRound === 2) {
                    $this->createScoresAndLog($assignment, 1, $criterias, 'revision_needed');
                }
            }
        }
    }

    protected function createScoresAndLog($assignment, $round, $criterias, $forcedRecommendation = null): void
    {
        $recommendation = $forcedRecommendation ?? $assignment->recommendation ?? 'approved';
        $notes = ($recommendation === 'revision_needed') ? 'Mohon lengkapi bagian profil mitra.' : ($assignment->review_notes ?? fake()->paragraph(2));
        $totalScore = 0;

        foreach ($criterias as $criteria) {
            $score = ($recommendation === 'approved') ? rand(4, 5) : rand(2, 3);
            $val = $score * $criteria->weight;
            $totalScore += $val;

            ReviewScore::create([
                'proposal_reviewer_id' => $assignment->id,
                'review_criteria_id' => $criteria->id,
                'acuan' => fake()->sentence(8),
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

        $report = ProgressReport::create([
            'proposal_id' => $proposal->id,
            'reporting_year' => date('Y'),
            'reporting_period' => 'semester_1',
            'status' => ReportStatus::SUBMITTED,
            'summary_update' => fake()->paragraph(2),
            'submitted_by' => $submitter->id,
            'submitted_at' => Carbon::parse($completionDate)->addDays(10),
        ]);

        MandatoryOutput::create([
            'progress_report_id' => $report->id,
            'proposal_output_id' => $mandatoryTarget->id,
            'status_type' => 'published',
            'author_status' => 'first_author',
            'video_url' => 'https://youtube.com/watch?v='.fake()->uuid,
            'platform' => 'YouTube',
            'publication_year' => date('Y'),
            'journal_title' => '-', // Dummy to satisfy DB
            'article_title' => 'Video Kegiatan: '.$proposal->title, // Satisfy DB
        ]);

        AdditionalOutput::create([
            'progress_report_id' => $report->id,
            'proposal_output_id' => $additionalTarget->id,
            'status' => 'published',
            'media_name' => 'Radar Pekalongan',
            'media_url' => 'https://radarpekalongan.co.id/pkm-itsnu',
            'publication_date' => date('Y-m-d'),
            'publication_year' => (int) date('Y'),
            'book_title' => '-', // Satisfy non-nullable
            'publisher_name' => '-', // Satisfy non-nullable
        ]);
    }

    protected function createStatusLogHistory($proposal, $finalStatus, $submitter, $dekanUsers, $kepalaLppm, $adminLppm): void
    {
        $baseTime = Carbon::parse($proposal->created_at);
        $facultyId = $submitter->identity?->faculty_id;
        $dekan = $dekanUsers->first(fn ($u) => $u->identity?->faculty_id === $facultyId) ?? $dekanUsers->first();

        $path = match ($finalStatus) {
            ProposalStatus::DRAFT => [],
            ProposalStatus::SUBMITTED => [['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0]],
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
                ['f' => ProposalStatus::UNDER_REVIEW, 't' => ProposalStatus::REVIEWED, 'u' => $adminLppm, 'd' => 12],
            ],
            ProposalStatus::REVISION_NEEDED => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
                ['f' => ProposalStatus::SUBMITTED, 't' => ProposalStatus::APPROVED, 'u' => $dekan, 'd' => 2],
                ['f' => ProposalStatus::APPROVED, 't' => ProposalStatus::WAITING_REVIEWER, 'u' => $kepalaLppm, 'd' => 4],
                ['f' => ProposalStatus::WAITING_REVIEWER, 't' => ProposalStatus::UNDER_REVIEW, 'u' => $adminLppm, 'd' => 5],
                ['f' => ProposalStatus::UNDER_REVIEW, 't' => ProposalStatus::REVIEWED, 'u' => $adminLppm, 'd' => 12],
                ['f' => ProposalStatus::REVIEWED, 't' => ProposalStatus::REVISION_NEEDED, 'u' => $kepalaLppm, 'd' => 13],
            ],
            ProposalStatus::COMPLETED => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
                ['f' => ProposalStatus::SUBMITTED, 't' => ProposalStatus::APPROVED, 'u' => $dekan, 'd' => 2],
                ['f' => ProposalStatus::APPROVED, 't' => ProposalStatus::WAITING_REVIEWER, 'u' => $kepalaLppm, 'd' => 4],
                ['f' => ProposalStatus::WAITING_REVIEWER, 't' => ProposalStatus::UNDER_REVIEW, 'u' => $adminLppm, 'd' => 5],
                ['f' => ProposalStatus::UNDER_REVIEW, 't' => ProposalStatus::REVIEWED, 'u' => $adminLppm, 'd' => 12],
                ['f' => ProposalStatus::REVIEWED, 't' => ProposalStatus::COMPLETED, 'u' => $kepalaLppm, 'd' => 15],
            ],
            ProposalStatus::REJECTED => [
                ['f' => ProposalStatus::DRAFT, 't' => ProposalStatus::SUBMITTED, 'u' => $submitter, 'd' => 0],
                ['f' => ProposalStatus::SUBMITTED, 't' => ProposalStatus::REJECTED, 'u' => $dekan, 'd' => 2],
            ],
            default => []
        };

        foreach ($path as $step) {
            $actor = $step['u'] ?? $adminLppm ?? $kepalaLppm ?? $dekan ?? $submitter;

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
