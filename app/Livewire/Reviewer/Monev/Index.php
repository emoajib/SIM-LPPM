<?php

namespace App\Livewire\Reviewer\Monev;

use App\Livewire\Concerns\HasToast;
use App\Models\MonevReview;
use App\Models\Research;
use App\Models\ReviewCriteria;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use HasToast, WithFileUploads, WithPagination;

    public $search = '';

    public $showReviewModal = false;

    public $selectedReview;

    // Evaluation fields
    public $score;

    public $notes;

    public $status; // Sangat Baik, Baik, Cukup

    public $berita_acara;

    public $borang_data = []; // Structured criteria: [ 'criteria_key_score' => 80, 'criteria_key_notes' => '...' ]

    public function mount()
    {
        if (! Auth::user()->hasRole('reviewer')) {
            abort(403);
        }

        // Self-Healing Database: Ensure columns exist to avoid 500 errors
        try {
            if (! Schema::hasColumn('proposal_monevs', 'academic_year')) {
                Schema::table('proposal_monevs', function (Blueprint $table) {
                    $table->string('academic_year')->nullable()->after('proposal_id');
                    $table->string('semester', 10)->nullable()->after('academic_year');
                });

                // Populate initial data
                DB::statement("
                    UPDATE proposal_monevs
                    SET academic_year = (
                        SELECT start_year FROM proposals WHERE proposals.id = proposal_monevs.proposal_id
                    ),
                        semester = COALESCE((
                            SELECT semester FROM proposals WHERE proposals.id = proposal_monevs.proposal_id
                        ), 'ganjil')
                    WHERE academic_year IS NULL
                ");
            }
        } catch (\Exception $e) {
            Log::error('Reviewer Monev Self-Healing Failed: '.$e->getMessage());
        }
    }

    public function selectReview($id)
    {
        $this->selectedReview = MonevReview::with([
            'proposal.submitter.identity',
            'proposal.progressReports.mandatoryOutputs.proposalOutput',
            'proposal.progressReports.additionalOutputs.proposalOutput',
            'proposal.progressReports.media',
            'proposal.detailable',
            'proposal.teamMembers.identity',
            'proposal.outputs',
            'proposal.partners',
            'proposal.dailyNotes.media',
        ])->findOrFail($id);
        $this->score = $this->selectedReview->score;
        $this->notes = $this->selectedReview->notes;
        $this->status = $this->selectedReview->status ?? 'baik';

        if ($this->selectedReview->borang_data) {
            $this->borang_data = $this->selectedReview->borang_data;
        } else {
            $this->borang_data = [];
            foreach ($this->activeCriteria() as $criteria) {
                $key = Str::snake($criteria->criteria);
                $this->borang_data[$key.'_score'] = 0;
                $this->borang_data[$key.'_notes'] = '';
            }
        }

        $this->showReviewModal = true;
    }

    public function saveReview()
    {
        $this->validate([
            'score' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:sangat_baik,baik,cukup',
            'notes' => 'nullable|string',
        ]);

        $this->selectedReview->update([
            'score' => $this->score,
            'status' => $this->status,
            'notes' => $this->notes,
            'borang_data' => $this->borang_data,
        ]);

        $this->toastSuccess('Draft evaluasi berhasil disimpan.');
    }

    public function submitReview()
    {
        $this->validate([
            'score' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:sangat_baik,baik,cukup',
            'notes' => 'required|string|min:10',
        ], [
            'notes.required' => 'Catatan reviewer wajib diisi sebelum mengajukan.',
            'notes.min' => 'Catatan reviewer minimal 10 karakter.',
        ]);

        // Final recalculation check
        $this->calculateTotalScore();

        $this->selectedReview->update([
            'score' => $this->score,
            'status' => $this->status,
            'notes' => $this->notes,
            'borang_data' => $this->borang_data,
            'reviewed_at' => now(), // Stempel Digital
        ]);

        $this->toastSuccess('Evaluasi Monev berhasil diajukan secara digital.');
        $this->showReviewModal = false;
    }

    public function updatedBorangData($value, $key)
    {
        if (str_ends_with($key, '_score')) {
            $this->calculateTotalScore();
        }
    }

    protected function calculateTotalScore()
    {
        $this->score = $this->totalScore();
    }

    #[Computed]
    public function totalScore()
    {
        $total = 0;
        foreach ($this->activeCriteria() as $criteria) {
            $key = Str::snake($criteria->criteria).'_score';
            $val = $this->borang_data[$key] ?? 0;
            if (is_numeric($val) && $val > 0) {
                $total += ($val * $criteria->weight / 100);
            }
        }

        return round($total, 2);
    }

    #[Computed]
    public function assignments()
    {
        return MonevReview::query()
            ->where('reviewer_id', Auth::id())
            ->with([
                'proposal.submitter',
                'proposal.detailable',
                'proposal.progressReports',
            ])
            ->when($this->search, function ($query) {
                $query->whereHas('proposal', function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhereHas('submitter', function ($sq) {
                            $sq->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function activeReport()
    {
        if (! $this->selectedReview) {
            return null;
        }

        $period = $this->selectedReview->semester === 'ganjil' ? 'semester_1' : 'semester_2';
        $year = $this->selectedReview->academic_year;

        // Try to find matching progress report, fallback to final if available
        return $this->selectedReview->proposal->progressReports
            ->where('reporting_year', $year)
            ->where('reporting_period', $period)
            ->first() ?? $this->selectedReview->proposal->progressReports->where('reporting_period', 'final')->first();
    }

    #[Computed]
    public function activeCriteria()
    {
        if (! $this->selectedReview) {
            return collect();
        }

        $type = $this->selectedReview->proposal->detailable_type === Research::class
            ? 'monev_research'
            : 'monev_community_service';

        return ReviewCriteria::where('type', $type)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    public function render()
    {
        return view('livewire.reviewer.monev.index');
    }
}
