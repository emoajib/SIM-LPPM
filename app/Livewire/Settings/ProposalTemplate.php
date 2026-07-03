<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\HasToast;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Livewire component for the Proposal Template settings page.
 *
 * File uploads are handled by ProposalTemplateUploadController via
 * traditional HTML form POST (enctype=multipart/form-data) to bypass
 * WAF blocks on Livewire's /livewire/upload-file endpoint.
 *
 * This component is responsible for:
 * - Displaying currently uploaded template files (via #[Computed] properties)
 * - Managing approval mode settings (digital/upload/both)
 */
class ProposalTemplate extends Component
{
    use HasToast;

    public string $proposal_approval_mode = 'digital'; // 'digital', 'upload', 'both'

    public string $report_approval_mode = 'digital'; // 'digital', 'upload', 'both'

    public string $logbook_approval_mode = 'digital'; // 'digital', 'upload', 'both'

    public function mount(): void
    {
        abort_unless(Auth::user()?->activeHasRole('admin lppm') || Auth::user()?->activeHasRole('superadmin'), 403);

        $this->proposal_approval_mode = Setting::where('key', 'proposal_approval_mode')->value('value') ?? 'digital';
        $this->report_approval_mode = Setting::where('key', 'report_approval_mode')->value('value') ?? 'digital';
        $this->logbook_approval_mode = Setting::where('key', 'logbook_approval_mode')->value('value') ?? 'digital';
    }

    public function saveApprovalSettings(): void
    {
        $this->validate([
            'proposal_approval_mode' => 'required|in:digital,upload,both',
            'report_approval_mode' => 'required|in:digital,upload,both',
            'logbook_approval_mode' => 'required|in:digital,upload,both',
        ]);

        Setting::updateOrCreate(['key' => 'proposal_approval_mode'], ['value' => $this->proposal_approval_mode]);
        Setting::updateOrCreate(['key' => 'report_approval_mode'], ['value' => $this->report_approval_mode]);
        Setting::updateOrCreate(['key' => 'logbook_approval_mode'], ['value' => $this->logbook_approval_mode]);

        $this->toastSuccess('Pengaturan metode persetujuan berhasil disimpan.');
    }

    #[Computed]
    public function researchTemplateMedia()
    {
        $setting = Setting::where('key', 'research_proposal_template')->first();

        return $setting ? $setting->getFirstMedia('template') : null;
    }

    #[Computed]
    public function communityServiceTemplateMedia()
    {
        $setting = Setting::where('key', 'community_service_proposal_template')->first();

        return $setting ? $setting->getFirstMedia('template') : null;
    }

    #[Computed]
    public function monevBeritaAcaraTemplateMedia()
    {
        $setting = Setting::where('key', 'monev_berita_acara_template')->first();

        return $setting ? $setting->getFirstMedia('template') : null;
    }

    #[Computed]
    public function monevBorangTemplateMedia()
    {
        $setting = Setting::where('key', 'monev_borang_template')->first();

        return $setting ? $setting->getFirstMedia('template') : null;
    }

    #[Computed]
    public function monevRekapPenilaianTemplateMedia()
    {
        $setting = Setting::where('key', 'monev_rekap_penilaian_template')->first();

        return $setting ? $setting->getFirstMedia('template') : null;
    }

    #[Computed]
    public function researchProgressReportTemplateMedia()
    {
        return Setting::where('key', 'research_progress_report_template')->first()?->getFirstMedia('template');
    }

    #[Computed]
    public function researchFinalReportTemplateMedia()
    {
        return Setting::where('key', 'research_final_report_template')->first()?->getFirstMedia('template');
    }

    #[Computed]
    public function communityServiceProgressReportTemplateMedia()
    {
        return Setting::where('key', 'community_service_progress_report_template')->first()?->getFirstMedia('template');
    }

    #[Computed]
    public function communityServiceFinalReportTemplateMedia()
    {
        return Setting::where('key', 'community_service_final_report_template')->first()?->getFirstMedia('template');
    }

    #[Computed]
    public function partnerCommitmentTemplateMedia()
    {
        return Setting::where('key', 'partner_commitment_template')->first()?->getFirstMedia('template');
    }

    #[Computed]
    public function proposalApprovalPageTemplateMedia()
    {
        return Setting::where('key', 'proposal_approval_page_template')->first()?->getFirstMedia('template');
    }

    #[Computed]
    public function reportApprovalPageTemplateMedia()
    {
        return Setting::where('key', 'report_approval_page_template')->first()?->getFirstMedia('template');
    }

    public function render()
    {
        return view('livewire.settings.proposal-template');
    }
}
