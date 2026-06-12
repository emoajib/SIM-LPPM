<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed in correct order to respect foreign key constraints
        $this->call([
            // 1. Roles & Permissions
            RoleSeeder::class,

            // 2. Master Data (No Dependencies)
            InstitutionSeeder::class,
            ResearchSchemeSeeder::class,
            OfficialSchemeSeeder::class,
            TktSeeder::class,
            FocusAreaSeeder::class,
            NationalPrioritySeeder::class,
            KeywordSeeder::class,
            MacroResearchGroupSeeder::class,
            BudgetGroupSeeder::class,
            BudgetComponentSeeder::class,
            ReviewCriteriaSeeder::class,
            IkuOutputTypeSeeder::class,
            MasterIkuSeeder::class,
            SdgSeeder::class,

            // 3. Hierarchical Data (Self-referencing)
            ScienceClusterSeeder::class,

            // 4. Dependent Master Data
            FacultySeeder::class,      // depends on institutions
            StudyProgramSeeder::class, // depends on institutions and faculties
            ThemeSeeder::class,        // depends on focus_areas
            TopicSeeder::class,        // depends on themes

            // 5. Users & Identities
            AdminUserSeeder::class,
            ManualBookSeeder::class,
            LetterModuleSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([
                PartnerSeeder::class,
                // 5. Users & Identities
                UserSeeder::class,

                // 6. Proposals & Related Data (depends on users and master data)
                ProposalSeeder::class, // Creates proposals with research/community service + all related data
                // DailyNoteSeeder is now handled inside Research/CommunityService seeders for better date logic

                // 7. Additional Seeders (if not already included in ProposalSeeder)
                ActivityScheduleSeeder::class,
                BudgetItemSeeder::class,
                HistoricalDataSeeder::class,
                PolicyInvolvementSeeder::class,
                ProposalOutputSeeder::class,
                QuotaMessageSeeder::class,
                ResearchStageSeeder::class,
            ]);
        }
    }
}
