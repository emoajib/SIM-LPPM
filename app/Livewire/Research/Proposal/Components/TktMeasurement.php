<?php

namespace App\Livewire\Research\Proposal\Components;

use App\Models\TktIndicator;
use App\Models\TktLevel;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * TKT Measurement Component
 * Allows researchers to assess their technology readiness level by selecting indicators
 */
class TktMeasurement extends Component
{
    public $tktType;

    public $strata; // Research scheme strata: Dasar, Terapan, Pengembangan, PKM

    public $levels = [];

    // Stores individual indicator scores: [indicator_id => score]
    public $indicatorScores = [];

    // Stores calculated average for each level: [level_id => average]
    public $levelAverages = [];

    /**
     * Open the modal with the specified TKT type
     */
    public function mount($tktType = null, $initialScores = [])
    {
        if ($tktType) {
            $this->loadLevels($tktType);
        }

        if (! empty($initialScores)) {
            $this->indicatorScores = $initialScores;
            $this->calculateAllAverages();
        }
    }

    /**
     * Handle tkt-type-selected event from parent component
     */
    #[On('tkt-type-selected')]
    public function handleTypeSelected($tktType, $existingScores = [], $strata = null)
    {
        $this->strata = $strata;
        $this->loadLevels($tktType);

        if (! empty($existingScores)) {
            $this->indicatorScores = $existingScores;
            $this->calculateAllAverages();
        }
    }

    /**
     * Load TKT levels and indicators for the selected type
     */
    public function loadLevels($type)
    {
        $this->tktType = $type;
        $this->levels = TktLevel::where('type', $type)
            ->with('indicators')
            ->orderBy('level')
            ->get();

        // Initialize scores if empty
        if (empty($this->indicatorScores)) {
            /** @var TktLevel $level */
            foreach ($this->levels as $level) {
                /** @var TktIndicator $indicator */
                foreach ($level->indicators as $indicator) {
                    // Default to 0 if not set
                    if (! isset($this->indicatorScores[$indicator->id])) {
                        $this->indicatorScores[$indicator->id] = 0;
                    }
                }
            }
        }

        $this->calculateAllAverages();
    }

    /**
     * Calculate average for a specific level
     */
    public function calculateLevelAverage($levelId)
    {
        $level = $this->levels->firstWhere('id', $levelId);
        if (! $level) {
            return 0;
        }

        $totalIndicators = $level->indicators->count();
        if ($totalIndicators === 0) {
            return 0;
        }

        $totalScore = 0;
        foreach ($level->indicators as $indicator) {
            $totalScore += (float) ($this->indicatorScores[$indicator->id] ?? 0);
        }

        $average = $totalScore / $totalIndicators;
        $this->levelAverages[$levelId] = round($average, 2);

        return $this->levelAverages[$levelId];
    }

    /**
     * Calculate averages for all levels
     */
    public function calculateAllAverages()
    {
        foreach ($this->levels as $level) {
            $this->calculateLevelAverage($level->id);
        }
    }

    /**
     * Check if a level is passed (Average >= 80)
     */
    public function isLevelPassed($levelId)
    {
        return ($this->levelAverages[$levelId] ?? 0) >= 80;
    }

    /**
     * Get target TKT range for a given strata
     * Returns [min, max] or null for PKM
     */
    public static function getTktRangeForStrata(?string $strata): ?array
    {
        return match ($strata) {
            'Reguler' => [1, 3],
            'Kolaborasi Internal' => [4, 6],
            'Kerja Sama Antar PT' => [7, 9],
            default => null, // PKM or unknown
        };
    }

    /**
     * Get the highest achieved (passed) TKT level
     */
    public function getAchievedTktLevel(): int
    {
        $achieved = 0;
        foreach ($this->levels as $level) {
            if ($this->isLevelPassed($level->id)) {
                $achieved = max($achieved, $level->level);
            } else {
                // Stop at first non-passed level (progressive)
                break;
            }
        }

        return $achieved;
    }

    /**
     * Check if achieved TKT is within target range for the strata
     */
    public function isTktWithinTarget(): ?bool
    {
        $range = self::getTktRangeForStrata($this->strata);
        if (! $range) {
            return null;
        } // N/A for PKM

        $achieved = $this->getAchievedTktLevel();

        return $achieved >= $range[0] && $achieved <= $range[1];
    }

    /**
     * Check if a level is locked (Previous level not passed)
     */
    public function isLevelLocked($level)
    {
        // Level 1 is never locked
        if ($level->level == 1) {
            return false;
        }

        // Find previous level
        $prevLevel = $this->levels->firstWhere('level', $level->level - 1);
        if (! $prevLevel) {
            return false;
        } // Should not happen if data is correct

        // Locked if previous level is not passed
        return ! $this->isLevelPassed($prevLevel->id);
    }

    /**
     * Update score when user changes input
     */
    /**
     * Update score when user changes input
     */
    public function updatedIndicatorScores($value, $key)
    {
        $this->calculateAllAverages();

        // Auto-switch to next level if current level is passed
        // $key is like "indicator_id"
        $indicatorId = $key;

        // Find which level this indicator belongs to
        $currentLevel = null;
        foreach ($this->levels as $level) {
            if ($level->indicators->contains('id', $indicatorId)) {
                $currentLevel = $level;
                break;
            }
        }

        if ($currentLevel && $this->isLevelPassed($currentLevel->id)) {
            // Find next level
            $nextLevel = $this->levels->firstWhere('level', $currentLevel->level + 1);

            if ($nextLevel && ! $this->isLevelLocked($nextLevel)) {
                // Dispatch event to switch tab
                $this->dispatch('switch-level', level: $nextLevel->level);
            }
        }
    }

    /**
     * Save the TKT assessment results
     */
    public function save()
    {
        $this->calculateAllAverages();

        $levelResults = [];
        $indicatorResults = $this->indicatorScores;

        foreach ($this->levels as $level) {
            $average = $this->levelAverages[$level->id] ?? 0;

            // Only include levels with actual progress (percentage > 0)
            // This prevents showing badge when no meaningful TKT data exists
            if ($average > 0) {
                $levelResults[$level->id] = [
                    'percentage' => $average,
                ];
            }
        }

        // Dispatch event to parent component with both level summaries and detailed indicator scores
        $this->dispatch(
            'tkt-calculated',
            levelResults: $levelResults,
            indicatorScores: $indicatorResults
        );
    }

    public function render()
    {
        return view('livewire.research.proposal.components.tkt-measurement');
    }
}
