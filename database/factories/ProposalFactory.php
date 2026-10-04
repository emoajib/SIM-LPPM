<?php

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\FocusArea;
use App\Models\NationalPriority;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\ScienceCluster;
use App\Models\Theme;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $researchSchemeId = ResearchScheme::query()->inRandomOrder()->value('id');
        $focusAreaId = FocusArea::query()->inRandomOrder()->value('id');
        $themeId = Theme::query()->inRandomOrder()->value('id');
        $topicId = Topic::query()->inRandomOrder()->value('id');
        $nationalPriorityId = NationalPriority::query()->inRandomOrder()->value('id');

        return [
            'title' => fake()->sentence(8),
            'submitter_id' => User::factory(),
            'detailable_type' => fake()->randomElement([
                Research::class,
                CommunityService::class,
            ]),
            'detailable_id' => null, // Will be set by morph relationship
            'research_scheme_id' => $researchSchemeId ?? ResearchScheme::factory(),
            'focus_area_id' => $focusAreaId ?? FocusArea::factory(),
            'theme_id' => $themeId ?? Theme::factory(),
            'topic_id' => $topicId ?? Topic::factory(),
            'national_priority_id' => $nationalPriorityId ?? NationalPriority::factory(),
            'cluster_level1_id' => null,
            'cluster_level2_id' => null,
            'cluster_level3_id' => null,
            'sbk_value' => fake()->randomFloat(2, 5000000, 50000000),
            'duration_in_years' => fake()->numberBetween(1, 3),
            'start_year' => (int) date('Y'),
            'summary' => fake()->paragraphs(3, true),
            'status' => fake()->randomElement(ProposalStatus::values()),
            'version' => 1,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Proposal $proposal) {
            if (! $proposal->cluster_level3_id) {
                $cluster3 = ScienceCluster::where('level', 3)->inRandomOrder()->first();
                if ($cluster3) {
                    $proposal->cluster_level3_id = $cluster3->id;
                    $proposal->cluster_level2_id = $cluster3->parent_id;

                    $cluster2 = ScienceCluster::find($cluster3->parent_id);
                    if ($cluster2) {
                        $proposal->cluster_level1_id = $cluster2->parent_id;
                    }
                } else {
                    // Fallback to level 1 if no level 3 found
                    $cluster1 = ScienceCluster::where('level', 1)->inRandomOrder()->first();
                    if ($cluster1) {
                        $proposal->cluster_level1_id = $cluster1->id;
                    }
                }
            }
        });
    }
}
