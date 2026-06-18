<?php

namespace Database\Seeders;

use App\Models\AdditionalOutput;
use App\Models\BudgetItem;
use App\Models\CommunityService;
use App\Models\DailyNote;
use App\Models\MandatoryOutput;
use App\Models\MonevReview;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalActivity;
use App\Models\ProposalMonev;
use App\Models\ProposalOutput;
use App\Models\ProposalReviewer;
use App\Models\ProposalStatusLog;
use App\Models\Research;
use App\Models\ReviewLog;
use App\Models\ReviewScore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanDummyDataSeeder extends Seeder
{
    /**
     * Hapus semua data dummy (proposal, penelitian, pengabdian, dll)
     * tapi PERTAHANKAN data master (users, roles, skema, dll)
     */
    public function run(): void
    {
        echo "🗑️  Membersihkan data dummy...\n";

        // Disable foreign key checks untuk truncate
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL DEFERRED;');
        }

        // Urutan penting: hapus child tables dulu, baru parent tables

        // 1. Review & scoring data
        $reviewScores = ReviewScore::count();
        ReviewScore::truncate();
        echo "  ✅ ReviewScores: {$reviewScores} dihapus\n";

        $reviewLogs = ReviewLog::count();
        ReviewLog::truncate();
        echo "  ✅ ReviewLogs: {$reviewLogs} dihapus\n";

        $proposalReviewers = ProposalReviewer::count();
        ProposalReviewer::truncate();
        echo "  ✅ ProposalReviewers: {$proposalReviewers} dihapus\n";

        // 2. Status logs
        $statusLogs = ProposalStatusLog::count();
        ProposalStatusLog::truncate();
        echo "  ✅ ProposalStatusLogs: {$statusLogs} dihapus\n";

        // 3. Outputs
        $additionalOutputs = AdditionalOutput::count();
        AdditionalOutput::truncate();
        echo "  ✅ AdditionalOutputs: {$additionalOutputs} dihapus\n";

        $mandatoryOutputs = MandatoryOutput::count();
        MandatoryOutput::truncate();
        echo "  ✅ MandatoryOutputs: {$mandatoryOutputs} dihapus\n";

        $proposalOutputs = ProposalOutput::count();
        ProposalOutput::truncate();
        echo "  ✅ ProposalOutputs: {$proposalOutputs} dihapus\n";

        // 4. Reports & notes
        $progressReports = ProgressReport::count();
        ProgressReport::truncate();
        echo "  ✅ ProgressReports: {$progressReports} dihapus\n";

        $dailyNotes = DailyNote::count();
        DailyNote::truncate();
        echo "  ✅ DailyNotes: {$dailyNotes} dihapus\n";

        // 5. Activities & monev
        $proposalActivities = ProposalActivity::count();
        ProposalActivity::truncate();
        echo "  ✅ ProposalActivities: {$proposalActivities} dihapus\n";

        $proposalMonevs = ProposalMonev::count();
        ProposalMonev::truncate();
        echo "  ✅ ProposalMonevs: {$proposalMonevs} dihapus\n";

        $monevReviews = MonevReview::count();
        MonevReview::truncate();
        echo "  ✅ MonevReviews: {$monevReviews} dihapus\n";

        // 6. Budget items
        $budgetItems = BudgetItem::count();
        BudgetItem::truncate();
        echo "  ✅ BudgetItems: {$budgetItems} dihapus\n";

        // 7. Proposals (detach team members first)
        $proposals = Proposal::count();
        foreach (Proposal::cursor() as $proposal) {
            $proposal->teamMembers()->detach();
        }
        Proposal::truncate();
        echo "  ✅ Proposals: {$proposals} dihapus\n";

        // 8. Detail tables (Research & CommunityService)
        $research = Research::count();
        Research::truncate();
        echo "  ✅ Research: {$research} dihapus\n";

        $communityService = CommunityService::count();
        CommunityService::truncate();
        echo "  ✅ CommunityService: {$communityService} dihapus\n";

        // Enable foreign key checks kembali
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL IMMEDIATE;');
        }

        echo "\n✨ Data dummy berhasil dibersihkan!\n";
        echo "📊 Data master (users, roles, skema, budget groups, dll) tetap aman.\n";
    }
}
