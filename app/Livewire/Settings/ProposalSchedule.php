<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\HasToast;
use App\Models\BudgetCap;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProposalSchedule extends Component
{
    use HasToast;

    public $research_start_date;

    public $research_end_date;

    public $research_revision_start_date;

    public $research_revision_end_date;

    public $research_final_report_start_date;

    public $research_final_report_end_date;

    public $community_service_start_date;

    public $community_service_end_date;

    public $community_service_revision_start_date;

    public $community_service_revision_end_date;

    public $community_service_final_report_start_date;

    public $community_service_final_report_end_date;

    /**
     * Convert stored value to HTML5 datetime-local format.
     */
    private static function toDatetimeLocal(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d\TH:i');
    }

    /**
     * Convert HTML5 datetime-local value to DB storage format.
     */
    private static function toStorageFormat(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function mount()
    {
        abort_unless(Auth::user()?->activeHasRole('admin lppm') || Auth::user()?->activeHasRole('superadmin'), 403);

        $this->research_start_date = self::toDatetimeLocal(Setting::where('key', 'research_proposal_start_date')->value('value'));
        $this->research_end_date = self::toDatetimeLocal(Setting::where('key', 'research_proposal_end_date')->value('value'));
        $this->research_revision_start_date = self::toDatetimeLocal(Setting::where('key', 'research_revision_start_date')->value('value'));
        $this->research_revision_end_date = self::toDatetimeLocal(Setting::where('key', 'research_revision_end_date')->value('value'));
        $this->research_final_report_start_date = self::toDatetimeLocal(Setting::where('key', 'research_final_report_start_date')->value('value'));
        $this->research_final_report_end_date = self::toDatetimeLocal(Setting::where('key', 'research_final_report_end_date')->value('value'));

        $this->community_service_start_date = self::toDatetimeLocal(Setting::where('key', 'community_service_proposal_start_date')->value('value'));
        $this->community_service_end_date = self::toDatetimeLocal(Setting::where('key', 'community_service_proposal_end_date')->value('value'));
        $this->community_service_revision_start_date = self::toDatetimeLocal(Setting::where('key', 'community_service_revision_start_date')->value('value'));
        $this->community_service_revision_end_date = self::toDatetimeLocal(Setting::where('key', 'community_service_revision_end_date')->value('value'));
        $this->community_service_final_report_start_date = self::toDatetimeLocal(Setting::where('key', 'community_service_final_report_start_date')->value('value'));
        $this->community_service_final_report_end_date = self::toDatetimeLocal(Setting::where('key', 'community_service_final_report_end_date')->value('value'));
    }

    public function save()
    {
        $this->validate([
            'research_start_date' => 'nullable|date',
            'research_end_date' => 'nullable|date|after_or_equal:research_start_date',
            'research_revision_start_date' => 'nullable|date',
            'research_revision_end_date' => 'nullable|date|after_or_equal:research_revision_start_date',
            'research_final_report_start_date' => 'nullable|date',
            'research_final_report_end_date' => 'nullable|date|after_or_equal:research_final_report_start_date',

            'community_service_start_date' => 'nullable|date',
            'community_service_end_date' => 'nullable|date|after_or_equal:community_service_start_date',
            'community_service_revision_start_date' => 'nullable|date',
            'community_service_revision_end_date' => 'nullable|date|after_or_equal:community_service_revision_start_date',
            'community_service_final_report_start_date' => 'nullable|date',
            'community_service_final_report_end_date' => 'nullable|date|after_or_equal:community_service_final_report_start_date',
        ]);

        Setting::updateOrCreate(['key' => 'research_proposal_start_date'], ['value' => self::toStorageFormat($this->research_start_date)]);
        Setting::updateOrCreate(['key' => 'research_proposal_end_date'], ['value' => self::toStorageFormat($this->research_end_date)]);
        Setting::updateOrCreate(['key' => 'research_revision_start_date'], ['value' => self::toStorageFormat($this->research_revision_start_date)]);
        Setting::updateOrCreate(['key' => 'research_revision_end_date'], ['value' => self::toStorageFormat($this->research_revision_end_date)]);
        Setting::updateOrCreate(['key' => 'research_final_report_start_date'], ['value' => self::toStorageFormat($this->research_final_report_start_date)]);
        Setting::updateOrCreate(['key' => 'research_final_report_end_date'], ['value' => self::toStorageFormat($this->research_final_report_end_date)]);

        Setting::updateOrCreate(['key' => 'community_service_proposal_start_date'], ['value' => self::toStorageFormat($this->community_service_start_date)]);
        Setting::updateOrCreate(['key' => 'community_service_proposal_end_date'], ['value' => self::toStorageFormat($this->community_service_end_date)]);
        Setting::updateOrCreate(['key' => 'community_service_revision_start_date'], ['value' => self::toStorageFormat($this->community_service_revision_start_date)]);
        Setting::updateOrCreate(['key' => 'community_service_revision_end_date'], ['value' => self::toStorageFormat($this->community_service_revision_end_date)]);
        Setting::updateOrCreate(['key' => 'community_service_final_report_start_date'], ['value' => self::toStorageFormat($this->community_service_final_report_start_date)]);
        Setting::updateOrCreate(['key' => 'community_service_final_report_end_date'], ['value' => self::toStorageFormat($this->community_service_final_report_end_date)]);

        $message = 'Jadwal proposal berhasil disimpan.';
        session()->flash('success', $message);
        $this->toastSuccess($message);
    }

    public function render()
    {
        return view('livewire.settings.proposal-schedule');
    }

    /**
     * Get budget cap for current year
     */
    #[Computed]
    public function currentYearBudgetCap()
    {
        $currentYear = (int) date('Y');

        return BudgetCap::where('year', $currentYear)->first();
    }
}
