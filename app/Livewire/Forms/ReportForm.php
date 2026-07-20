<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\AdditionalOutput;
use App\Models\Keyword;
use App\Models\MandatoryOutput;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalOutput;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class ReportForm extends Form
{
    public ?ProgressReport $progressReport = null;

    public ?Proposal $proposal = null;

    public string $summaryUpdate = '';

    public string $keywordsInput = '';

    public int $reportingYear = 0;

    public string $reportingPeriod = 'semester_1';

    public array $mandatoryOutputs = [];

    public array $additionalOutputs = [];

    public ?int $editingMandatoryId = null;

    public ?int $editingAdditionalId = null;

    public array $tempMandatoryFiles = [];

    public array $tempAdditionalFiles = [];

    public array $tempAdditionalCerts = [];

    // File uploads (progress has 1, final has 3)
    public $substanceFile;

    public $realizationFile;

    public $presentationFile;

    public $signatureFile;

    // Report configuration
    public ?string $type = 'progress'; // 'progress' or 'final'

    protected array $fileValidationRules = [
        'substanceFile' => 'nullable|file|mimes:pdf,application/pdf|max:10240',
        'realizationFile' => 'nullable|file|mimes:pdf,docx|max:10240',
        'presentationFile' => 'nullable|file|mimes:pdf,ppt,pptx|max:51200',
        'signatureFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
    ];

    /**
     * Ensure progress report exists or create draft
     */
    protected function ensureReportExists(): void
    {
        if ($this->progressReport) {
            return;
        }

        $this->ensureProposalInitialized();

        // Create draft report
        $this->progressReport = ProgressReport::create([
            'proposal_id' => $this->proposal->id,
            'summary_update' => $this->summaryUpdate ?: ($this->proposal->summary ?? 'Draft Report'),
            'reporting_year' => $this->reportingYear,
            'reporting_period' => $this->reportingPeriod,
            'status' => 'draft',
        ]);

        $this->saveKeywords();
    }

    /**
     * Ensure proposal is initialized
     *
     * @throws \RuntimeException
     */
    protected function ensureProposalInitialized(): void
    {
        if ($this->proposal === null) {
            throw new \RuntimeException('Proposal must be initialized before using this method. Call initWithProposal() first.');
        }
    }

    /**
     * Initialize form with Proposal
     */
    public function initWithProposal(Proposal $proposal): void
    {
        $this->proposal = $proposal;
        $this->reportingYear = (int) date('Y');
        $this->keywordsInput = $proposal->keywords->pluck('name')->implode('; ');

        if ($this->type === 'final') {
            $this->reportingPeriod = 'final';
        } else {
            $this->reportingPeriod = 'semester_1';
        }
    }

    public function setReport(ProgressReport $report): void
    {
        $this->progressReport = $report;
        $this->summaryUpdate = $report->summary_update ?? '';
        $this->keywordsInput = $report->keywords->pluck('name')->implode('; ');
        $this->reportingYear = (int) $report->reporting_year;

        // Determine if we are cloning from a previous period
        $isCloning = false;

        // Force final period if type is final
        if ($this->type === 'final') {
            $this->reportingPeriod = 'final';
            if ($report->reporting_period !== 'final') {
                $isCloning = true;
            }
        } else {
            $this->reportingPeriod = $report->reporting_period;
        }

        // Load mandatory outputs
        foreach ($report->mandatoryOutputs as $output) {
            if (empty($output->proposal_output_id)) {
                continue;
            }

            $data = [
                'id' => $isCloning ? null : $output->id, // If cloning, reset ID to create new record
                'status_type' => $output->status_type,
                'author_status' => $output->author_status,
                // Journal
                'journal_title' => $output->journal_title,
                'issn' => $output->issn,
                'eissn' => $output->eissn,
                'indexing_body' => $output->indexing_body,
                'journal_url' => $output->journal_url,
                'article_title' => $output->article_title,
                'publication_year' => $output->publication_year,
                'volume' => $output->volume,
                'issue_number' => $output->issue_number,
                'page_start' => $output->page_start,
                'page_end' => $output->page_end,
                'article_url' => $output->article_url,
                'doi' => $output->doi,
                // Book
                'book_title' => $output->book_title,
                'isbn' => $output->isbn,
                'publisher' => $output->publisher,
                'total_pages' => $output->total_pages,
                // HKI
                'hki_type' => $output->hki_type,
                'registration_number' => $output->registration_number,
                'inventors' => $output->inventors,
                // Media / Video / Product
                'media_name' => $output->media_name,
                'media_url' => $output->media_url,
                'publication_date' => $output->publication_date
                    ? $output->publication_date->format('Y-m-d')
                    : null,
                'video_url' => $output->video_url,
                'platform' => $output->platform,
                'product_name' => $output->product_name,
                'description' => $output->description,
            ];

            // Add file status for final reports
            if ($this->type === 'final') {
                // If cloning, reset file status to force re-upload for final report
                // But retain metadata
                $data['document_file'] = $isCloning ? false : ($output->getFirstMedia('journal_article') ? true : false);
            }

            $this->mandatoryOutputs[$output->proposal_output_id] = $data;
        }

        // Load additional outputs
        foreach ($report->additionalOutputs as $output) {
            if (empty($output->proposal_output_id)) {
                continue;
            }

            $data = [
                'id' => $isCloning ? null : $output->id, // If cloning, reset ID
                'status' => $output->status,
                // Book
                'book_title' => $output->book_title,
                'publisher_name' => $output->publisher_name,
                'isbn' => $output->isbn,
                'publication_year' => $output->publication_year,
                'total_pages' => $output->total_pages,
                'publisher_url' => $output->publisher_url,
                'book_url' => $output->book_url,
                // Journal (if additional is journal)
                'journal_title' => $output->journal_title,
                'issn' => $output->issn,
                'eissn' => $output->eissn,
                'volume' => $output->volume,
                'issue_number' => $output->issue_number,
                'doi' => $output->doi,
                // HKI / Media / etc
                'hki_type' => $output->hki_type,
                'registration_number' => $output->registration_number,
                'inventors' => $output->inventors,
                'media_name' => $output->media_name,
                'media_url' => $output->media_url,
                'publication_date' => $output->publication_date
                    ? $output->publication_date->format('Y-m-d')
                    : null,
                'video_url' => $output->video_url,
                'platform' => $output->platform,
                'product_name' => $output->product_name,
                'description' => $output->description,
            ];

            // Add file status for final reports
            if ($this->type === 'final') {
                $data['document_file'] = $isCloning ? false : ($output->getFirstMedia('book_document') ? true : false);
                $data['publication_certificate'] = $isCloning ? false : ($output->getFirstMedia('publication_certificate') ? true : false);
            }

            $this->additionalOutputs[$output->proposal_output_id] = $data;
        }
    }

    public function initializeNewReport(): void
    {
        $this->ensureProposalInitialized();

        $this->summaryUpdate = $this->proposal->summary ?? '';
        $this->keywordsInput = $this->proposal->keywords->pluck('name')->implode('; ');

        foreach ($this->proposal->outputs->where('category', 'Wajib') as $output) {
            $this->mandatoryOutputs[$output->id] = $this->getEmptyMandatoryOutput();
        }

        foreach ($this->proposal->outputs->where('category', 'Tambahan') as $output) {
            $this->additionalOutputs[$output->id] = $this->getEmptyAdditionalOutput();
        }
    }

    protected function getEmptyMandatoryOutput(): array
    {
        $data = [
            'id' => null,
            'status_type' => '',
            'author_status' => '',
            'publication_year' => '',
            // Journal
            'journal_title' => '',
            'issn' => '',
            'eissn' => '',
            'indexing_body' => '',
            'rank' => '',
            'journal_url' => '',
            'article_title' => '',
            'volume' => '',
            'issue_number' => '',
            'page_start' => '',
            'page_end' => '',
            'article_url' => '',
            'doi' => '',
            // Book
            'book_title' => '',
            'isbn' => '',
            'publisher' => '',
            'total_pages' => '',
            // HKI
            'hki_type' => '',
            'registration_number' => '',
            'inventors' => '',
            // Media/Video/Product
            'media_name' => '',
            'media_url' => '',
            'publication_date' => '',
            'video_url' => '',
            'platform' => '',
            'product_name' => '',
            'description' => '',
        ];

        if ($this->type === 'final') {
            $data['document_file'] = false;
        }

        return $data;
    }

    protected function getEmptyAdditionalOutput(): array
    {
        $data = [
            'id' => null,
            'status' => '',
            'publication_year' => '',
            // Book
            'book_title' => '',
            'publisher_name' => '',
            'isbn' => '',
            'total_pages' => '',
            'publisher_url' => '',
            'book_url' => '',
            // Journal
            'journal_title' => '',
            'issn' => '',
            'eissn' => '',
            'indexing_body' => '',
            'rank' => '',
            'volume' => '',
            'issue_number' => '',
            'doi' => '',
            // HKI/Media/etc
            'hki_type' => '',
            'registration_number' => '',
            'inventors' => '',
            'media_name' => '',
            'media_url' => '',
            'publication_date' => '',
            'video_url' => '',
            'platform' => '',
            'product_name' => '',
            'description' => '',
        ];

        if ($this->type === 'final') {
            $data['document_file'] = false;
            $data['publication_certificate'] = false;
        }

        return $data;
    }

    public function rules(): array
    {
        $rules = [
            'summaryUpdate' => ['required', 'string', 'min:10'],
            'keywordsInput' => ['required', 'string'],
            'reportingYear' => ['required', 'integer', 'min:2020', 'max:2099'],
            'reportingPeriod' => ['required', 'string', Rule::in(['semester_1', 'semester_2', 'annual', 'final'])],
        ];

        if ($this->type === 'final') {
            $rules['reportingPeriod'] = ['required', 'string', 'in:final'];
        }

        return $rules;
    }

    public function validateReportData(): void
    {
        $this->validate($this->rules());
    }

    public function validateMandatoryOutput(int $outputId): void
    {
        $output = ProposalOutput::find($outputId);
        $type = strtolower($output->type ?? '');
        $group = strtolower($output->group ?? '');

        // Common validation
        $rules = [
            "mandatoryOutputs.{$outputId}.status_type" => 'required|in:draft,submitted,published,accepted,under_review,rejected',
            "mandatoryOutputs.{$outputId}.publication_year" => 'required|integer|between:2000,2030',
        ];

        // Specific validation based on type
        if (str_contains($type, 'jurnal') || str_contains($group, 'jurnal') || str_contains($type, 'prosiding') || str_contains($group, 'prosiding')) {
            $rules["mandatoryOutputs.{$outputId}.author_status"] = 'required|in:first_author,co_author,corresponding_author';
            $rules["mandatoryOutputs.{$outputId}.journal_title"] = 'required|string|max:255';
            $rules["mandatoryOutputs.{$outputId}.article_title"] = 'required|string|max:255';
            $rules["mandatoryOutputs.{$outputId}.issn"] = 'nullable|string|max:20';
            $rules["mandatoryOutputs.{$outputId}.eissn"] = 'nullable|string|max:20';
            $rules["mandatoryOutputs.{$outputId}.journal_url"] = 'nullable|url';
            $rules["mandatoryOutputs.{$outputId}.article_url"] = 'nullable|url';
            $rules["mandatoryOutputs.{$outputId}.doi"] = 'nullable|string|max:255';
        } elseif (str_contains($type, 'buku') || str_contains($group, 'buku') || str_contains($type, 'modul') || str_contains($type, 'pedoman')) {
            $rules["mandatoryOutputs.{$outputId}.book_title"] = 'required|string|max:255';
            $rules["mandatoryOutputs.{$outputId}.publisher"] = 'required|string|max:255';
            $rules["mandatoryOutputs.{$outputId}.isbn"] = 'nullable|string|max:20';
        } elseif (str_contains($type, 'hki') || str_contains($type, 'paten') || str_contains($type, 'hak cipta') || str_contains($group, 'hki')) {
            $rules["mandatoryOutputs.{$outputId}.hki_type"] = 'required|string|max:255';
            $rules["mandatoryOutputs.{$outputId}.registration_number"] = 'nullable|string|max:255';
            $rules["mandatoryOutputs.{$outputId}.inventors"] = 'nullable|string|max:255';
        } elseif (str_contains($type, 'media') || str_contains($group, 'media')) {
            $rules["mandatoryOutputs.{$outputId}.media_name"] = 'required|string|max:255';
            $rules["mandatoryOutputs.{$outputId}.media_url"] = 'required|url';
            $rules["mandatoryOutputs.{$outputId}.publication_date"] = 'required|date';
        } elseif (str_contains($type, 'video') || str_contains($group, 'video')) {
            $rules["mandatoryOutputs.{$outputId}.video_url"] = 'required|url';
            $rules["mandatoryOutputs.{$outputId}.platform"] = 'nullable|string|max:50';
        } elseif (str_contains($type, 'produk') || str_contains($type, 'jasa') || str_contains($type, 'sistem') || str_contains($type, 'ttg') || str_contains($type, 'purwarupa') || str_contains($type, 'prototipe') || str_contains($type, 'model') || str_contains($group, 'produk')) {
            $rules["mandatoryOutputs.{$outputId}.product_name"] = 'required|string|max:255';
            $rules["mandatoryOutputs.{$outputId}.description"] = 'nullable|string';
        }

        $this->validate($rules);
    }

    public function validateAdditionalOutput(int $outputId): void
    {
        $output = ProposalOutput::find($outputId);
        $type = strtolower($output->type ?? '');
        $group = strtolower($output->group ?? '');

        // Common validation
        $rules = [
            "additionalOutputs.{$outputId}.status" => 'required|in:draft,submitted,published,accepted,under_review,rejected,review,editing',
            "additionalOutputs.{$outputId}.publication_year" => 'nullable|integer|between:2000,2030',
        ];

        // Specific validation
        if (str_contains($type, 'buku') || str_contains($group, 'buku')) {
            $rules["additionalOutputs.{$outputId}.book_title"] = 'required|string|max:255';
            $rules["additionalOutputs.{$outputId}.publisher_name"] = 'required|string|max:255';
            $rules["additionalOutputs.{$outputId}.isbn"] = 'nullable|string|max:20';
            $rules["additionalOutputs.{$outputId}.total_pages"] = 'nullable|integer|min:1';
            $rules["additionalOutputs.{$outputId}.publisher_url"] = 'nullable|url';
            $rules["additionalOutputs.{$outputId}.book_url"] = 'nullable|url';
        } elseif (str_contains($type, 'jurnal') || str_contains($group, 'jurnal')) {
            $rules["additionalOutputs.{$outputId}.journal_title"] = 'required|string|max:255';
            $rules["additionalOutputs.{$outputId}.issn"] = 'nullable|string|max:20';
            $rules["additionalOutputs.{$outputId}.doi"] = 'nullable|string|max:255';
        } elseif (str_contains($type, 'hki') || str_contains($group, 'hki')) {
            $rules["additionalOutputs.{$outputId}.hki_type"] = 'required|string|max:255';
        } elseif (str_contains($type, 'media') || str_contains($group, 'media')) {
            $rules["additionalOutputs.{$outputId}.media_name"] = 'required|string|max:255';
            $rules["additionalOutputs.{$outputId}.media_url"] = 'required|url';
        }

        $this->validate($rules);
    }

    public function save(?ProgressReport $existingReport = null): ProgressReport
    {
        $this->ensureProposalInitialized();

        // Force reporting period to 'final' before validation if type is final
        if ($this->type === 'final') {
            $this->reportingPeriod = 'final';
        }

        $this->validateReportData();

        DB::beginTransaction();

        try {
            $reportData = [
                'summary_update' => $this->summaryUpdate,
                'reporting_year' => $this->reportingYear,
                'reporting_period' => $this->reportingPeriod,
            ];

            // Check if existing report matches the target period
            // If we are saving 'final' but existing is 'semester_1', we MUST create new
            if ($existingReport && $existingReport->reporting_period === $this->reportingPeriod) {
                $existingReport->update($reportData);
                $report = $existingReport;
            } else {
                // Create NEW report if periods differ (or no existing)
                $report = ProgressReport::create(array_merge($reportData, [
                    'proposal_id' => $this->proposal->id,
                    'status' => 'draft',
                ]));
            }

            $this->progressReport = $report;
            $this->saveKeywords();
            $this->saveMandatoryOutputs();
            $this->saveAdditionalOutputs();

            $this->saveReportFiles($report);

            // Reload report to sync IDs and state
            $this->setReport($report);

            DB::commit();

            return $report;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function submit(?ProgressReport $existingReport = null): ProgressReport
    {
        $report = $this->save($existingReport);

        $report->update([
            'status' => 'submitted',
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
        ]);

        return $report;
    }

    protected function saveKeywords(): void
    {
        if (empty($this->keywordsInput)) {
            return;
        }

        $keywordNames = array_map('trim', explode(';', $this->keywordsInput));
        $keywords = [];

        foreach ($keywordNames as $name) {
            if (empty($name)) {
                continue;
            }

            $keyword = Keyword::firstOrCreate(['name' => $name]);
            $keywords[] = $keyword->id;
        }

        $this->progressReport->keywords()->sync($keywords);
    }

    protected function saveMandatoryOutputs(): void
    {
        foreach ($this->mandatoryOutputs as $proposalOutputId => $data) {
            if (empty($proposalOutputId)) {
                continue;
            }

            if (empty($data['status_type']) && empty($data['journal_title'])) {
                continue;
            }

            $outputData = [
                // 'progress_report_id' and 'proposal_output_id' are in the lookup array
                'status_type' => ! empty($data['status_type']) ? $data['status_type'] : null,
                'author_status' => ! empty($data['author_status']) ? $data['author_status'] : null,
                'journal_title' => $data['journal_title'] ?? null,
                'issn' => $data['issn'] ?? null,
                'eissn' => $data['eissn'] ?? null,
                'indexing_body' => $data['indexing_body'] ?? null,
                'rank' => $data['rank'] ?? null,
                'journal_url' => $data['journal_url'] ?? null,
                'article_title' => $data['article_title'] ?? null,
                'publication_year' => ! empty($data['publication_year']) ? $data['publication_year'] : null,
                'volume' => $data['volume'] ?? null,
                'issue_number' => $data['issue_number'] ?? null,
                'page_start' => ! empty($data['page_start']) ? (int) $data['page_start'] : null,
                'page_end' => ! empty($data['page_end']) ? (int) $data['page_end'] : null,
                'article_url' => $data['article_url'] ?? null,
                'doi' => $data['doi'] ?? null,
                // Book
                'book_title' => $data['book_title'] ?? null,
                'isbn' => $data['isbn'] ?? null,
                'publisher' => $data['publisher'] ?? null,
                'total_pages' => ! empty($data['total_pages']) ? (int) $data['total_pages'] : null,
                // HKI
                'hki_type' => $data['hki_type'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'inventors' => $data['inventors'] ?? null,
                // Media / Video / Product
                'media_name' => $data['media_name'] ?? null,
                'media_url' => $data['media_url'] ?? null,
                'publication_date' => ! empty($data['publication_date']) ? $data['publication_date'] : null,
                'video_url' => $data['video_url'] ?? null,
                'platform' => $data['platform'] ?? null,
                'product_name' => $data['product_name'] ?? null,
                'description' => $data['description'] ?? null,
            ];

            MandatoryOutput::updateOrCreate(
                [
                    'progress_report_id' => $this->progressReport->id,
                    'proposal_output_id' => $proposalOutputId,
                ],
                $outputData
            );
        }
    }

    protected function saveAdditionalOutputs(): void
    {
        foreach ($this->additionalOutputs as $proposalOutputId => $data) {
            if (empty($proposalOutputId)) {
                continue;
            }

            if (empty($data['status']) && empty($data['book_title'])) {
                continue;
            }

            $outputData = [
                // Lookup keys
                'status' => ! empty($data['status']) ? $data['status'] : null,
                'book_title' => $data['book_title'] ?? null,
                'publisher_name' => $data['publisher_name'] ?? null,
                'isbn' => $data['isbn'] ?? null,
                'publication_year' => ! empty($data['publication_year']) ? $data['publication_year'] : null,
                'total_pages' => ! empty($data['total_pages']) ? (int) $data['total_pages'] : null,
                'publisher_url' => $data['publisher_url'] ?? null,
                'book_url' => $data['book_url'] ?? null,
                // Journal
                'journal_title' => $data['journal_title'] ?? null,
                'issn' => $data['issn'] ?? null,
                'eissn' => $data['eissn'] ?? null,
                'indexing_body' => $data['indexing_body'] ?? null,
                'rank' => $data['rank'] ?? null,
                'volume' => $data['volume'] ?? null,
                'issue_number' => $data['issue_number'] ?? null,
                'doi' => $data['doi'] ?? null,
                // HKI / Media
                'hki_type' => $data['hki_type'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'inventors' => $data['inventors'] ?? null,
                'media_name' => $data['media_name'] ?? null,
                'media_url' => $data['media_url'] ?? null,
                'publication_date' => ! empty($data['publication_date']) ? $data['publication_date'] : null,
                'video_url' => $data['video_url'] ?? null,
                'platform' => $data['platform'] ?? null,
                'product_name' => $data['product_name'] ?? null,
                'description' => $data['description'] ?? null,
            ];

            AdditionalOutput::updateOrCreate(
                [
                    'progress_report_id' => $this->progressReport->id,
                    'proposal_output_id' => $proposalOutputId,
                ],
                $outputData
            );
        }
    }

    public function editMandatoryOutput(int $proposalOutputId): void
    {
        $this->editingMandatoryId = $proposalOutputId;

        if (! isset($this->mandatoryOutputs[$proposalOutputId])) {
            $this->mandatoryOutputs[$proposalOutputId] = $this->getEmptyMandatoryOutput();
        }
    }

    public function saveMandatoryOutput(int $proposalOutputId): void
    {
        $this->validateMandatoryOutput($proposalOutputId);
        $this->saveMandatoryOutputWithFile($proposalOutputId);
    }

    public function editAdditionalOutput(int $proposalOutputId): void
    {
        $this->editingAdditionalId = $proposalOutputId;

        if (! isset($this->additionalOutputs[$proposalOutputId])) {
            $this->additionalOutputs[$proposalOutputId] = $this->getEmptyAdditionalOutput();
        }
    }

    public function saveAdditionalOutput(int $proposalOutputId): void
    {
        $this->validateAdditionalOutput($proposalOutputId);
        $this->saveAdditionalOutputWithFile($proposalOutputId);
    }

    public function closeMandatoryModal(): void
    {
        $this->reset(['editingMandatoryId']);
    }

    public function closeAdditionalModal(): void
    {
        $this->reset(['editingAdditionalId']);
    }

    public function resetForm(): void
    {
        $this->reset([
            'summaryUpdate',
            'keywordsInput',
            'reportingYear',
            'reportingPeriod',
            'mandatoryOutputs',
            'additionalOutputs',
            'editingMandatoryId',
            'editingAdditionalId',
            'tempMandatoryFiles',
            'tempAdditionalFiles',
            'tempAdditionalCerts',
            'substanceFile',
            'realizationFile',
            'presentationFile',
        ]);

        $this->progressReport = null;
    }

    /**
     * Save all report files to media collections
     */
    public function saveReportFiles(ProgressReport $report): void
    {
        // Save substance file (all report types have this)
        if ($this->substanceFile instanceof UploadedFile && $this->substanceFile->isValid()) {
            $this->saveFileToCollection($report, $this->substanceFile, 'substance_file');
        }

        // Save realization file (final reports only)
        if ($this->realizationFile instanceof UploadedFile && $this->realizationFile->isValid()) {
            $this->saveFileToCollection($report, $this->realizationFile, 'realization_file');
        }

        // Save presentation file (final reports only)
        if ($this->presentationFile instanceof UploadedFile && $this->presentationFile->isValid()) {
            $this->saveFileToCollection($report, $this->presentationFile, 'presentation_file');
        }

        // Save signature file (if physical mode selected)
        if ($this->signatureFile instanceof UploadedFile && $this->signatureFile->isValid()) {
            $this->saveFileToCollection($report, $this->signatureFile, 'signature_page');
        }
    }

    /**
     * Save a single file to media collection
     */
    protected function saveFileToCollection(
        ProgressReport $report,
        $file,
        string $collectionName
    ): void {
        if (! $file || ! $file instanceof TemporaryUploadedFile) {
            return;
        }

        try {
            $report->clearMediaCollection($collectionName);
            $report
                ->addMedia($file->getRealPath())
                ->usingName($file->getClientOriginalName())
                ->usingFileName($file->hashName())
                ->withCustomProperties([
                    'uploaded_by' => Auth::id(),
                    'proposal_id' => $this->proposal->id,
                    'report_type' => $this->type,
                ])
                ->toMediaCollection($collectionName);
        } catch (\Exception $e) {
            \Log::error('Upload report file failed ('.$collectionName.'): '.$e->getMessage());
        }
    }

    /**
     * Validate all files
     */
    public function validateFiles(): void
    {
        $this->validate($this->fileValidationRules);
    }

    /**
     * Handle file upload and auto-save
     * Note: Validation should be done at component level before calling this method
     */
    public function handleFileUpload(string $fileProperty): void
    {
        // Create draft report if doesn't exist
        if (! $this->progressReport && $this->{$fileProperty}) {
            $this->progressReport = ProgressReport::create([
                'proposal_id' => $this->proposal->id,
                'summary_update' => $this->summaryUpdate ?: $this->proposal->summary,
                'reporting_year' => $this->reportingYear,
                'reporting_period' => $this->reportingPeriod,
                'status' => 'draft',
            ]);
            $this->saveKeywords();
        }

        // Save the file
        if ($this->progressReport) {
            $this->saveReportFiles($this->progressReport);
        }
    }

    /**
     * Save mandatory output with file
     */
    public function saveMandatoryOutputWithFile(int $proposalOutputId, bool $validate = true): void
    {
        if ($validate) {
            $this->validateMandatoryOutput($proposalOutputId);
        }

        $this->ensureReportExists();

        $data = $this->mandatoryOutputs[$proposalOutputId] ?? [];

        DB::transaction(function () use ($proposalOutputId, $data) {
            // Find or create mandatory output
            $output = MandatoryOutput::where('progress_report_id', $this->progressReport->id)
                ->where('proposal_output_id', $proposalOutputId)
                ->first();

            $outputData = [
                'progress_report_id' => $this->progressReport->id,
                'proposal_output_id' => $proposalOutputId,
                'status_type' => ! empty($data['status_type']) ? $data['status_type'] : null,
                'publication_year' => ! empty($data['publication_year']) ? (int) $data['publication_year'] : null,
                // Journal
                'author_status' => ! empty($data['author_status']) ? $data['author_status'] : null,
                'journal_title' => $data['journal_title'] ?? null,
                'issn' => $data['issn'] ?? null,
                'eissn' => $data['eissn'] ?? null,
                'indexing_body' => $data['indexing_body'] ?? null,
                'rank' => $data['rank'] ?? null,
                'journal_url' => $data['journal_url'] ?? null,
                'article_title' => $data['article_title'] ?? null,
                'volume' => $data['volume'] ?? null,
                'issue_number' => $data['issue_number'] ?? null,
                'page_start' => ! empty($data['page_start']) ? (int) $data['page_start'] : null,
                'page_end' => ! empty($data['page_end']) ? (int) $data['page_end'] : null,
                'article_url' => $data['article_url'] ?? null,
                'doi' => $data['doi'] ?? null,
                // Book
                'book_title' => $data['book_title'] ?? null,
                'isbn' => $data['isbn'] ?? null,
                'publisher' => $data['publisher'] ?? null,
                'total_pages' => ! empty($data['total_pages']) ? (int) $data['total_pages'] : null,
                // HKI
                'hki_type' => $data['hki_type'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'inventors' => $data['inventors'] ?? null,
                // Media / Video / Product
                'media_name' => $data['media_name'] ?? null,
                'media_url' => $data['media_url'] ?? null,
                'publication_date' => ! empty($data['publication_date']) ? $data['publication_date'] : null,
                'video_url' => $data['video_url'] ?? null,
                'platform' => $data['platform'] ?? null,
                'product_name' => $data['product_name'] ?? null,
                'description' => $data['description'] ?? null,
            ];

            if ($output) {
                $output->update($outputData);
            } else {
                $output = MandatoryOutput::create($outputData);
            }

            // Save file if uploaded
            if (
                isset($this->tempMandatoryFiles[$proposalOutputId]) &&
                $this->tempMandatoryFiles[$proposalOutputId] instanceof UploadedFile
            ) {
                $file = $this->tempMandatoryFiles[$proposalOutputId];

                $output->clearMediaCollection('journal_article');
                $output
                    ->addMedia($file->getRealPath())
                    ->usingName($file->getClientOriginalName())
                    ->usingFileName($file->hashName())
                    ->withCustomProperties([
                        'uploaded_by' => Auth::id(),
                        'proposal_id' => $this->proposal->id,
                        'report_type' => $this->type,
                    ])
                    ->toMediaCollection('journal_article');

                unset($this->tempMandatoryFiles[$proposalOutputId]);
            }
        });
    }

    /**
     * Save additional output with files
     */
    public function saveAdditionalOutputWithFile(int $proposalOutputId, bool $validate = true): void
    {
        if ($validate) {
            $this->validateAdditionalOutput($proposalOutputId);
        }

        $this->ensureReportExists();

        $data = $this->additionalOutputs[$proposalOutputId] ?? [];

        DB::transaction(function () use ($proposalOutputId, $data) {
            // Find or create additional output
            $output = AdditionalOutput::where('progress_report_id', $this->progressReport->id)
                ->where('proposal_output_id', $proposalOutputId)
                ->first();

            $outputData = [
                'progress_report_id' => $this->progressReport->id,
                'proposal_output_id' => $proposalOutputId,
                'status' => ! empty($data['status']) ? $data['status'] : null,
                'publication_year' => ! empty($data['publication_year']) ? (int) $data['publication_year'] : null,
                // Book
                'book_title' => $data['book_title'] ?? null,
                'publisher_name' => $data['publisher_name'] ?? null,
                'isbn' => $data['isbn'] ?? null,
                'total_pages' => ! empty($data['total_pages']) ? (int) $data['total_pages'] : null,
                'publisher_url' => $data['publisher_url'] ?? null,
                'book_url' => $data['book_url'] ?? null,
                // Journal
                'journal_title' => $data['journal_title'] ?? null,
                'issn' => $data['issn'] ?? null,
                'eissn' => $data['eissn'] ?? null,
                'indexing_body' => $data['indexing_body'] ?? null,
                'rank' => $data['rank'] ?? null,
                'volume' => $data['volume'] ?? null,
                'issue_number' => $data['issue_number'] ?? null,
                'doi' => $data['doi'] ?? null,
                // HKI / Media / etc
                'hki_type' => $data['hki_type'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'inventors' => $data['inventors'] ?? null,
                'media_name' => $data['media_name'] ?? null,
                'media_url' => $data['media_url'] ?? null,
                'publication_date' => ! empty($data['publication_date']) ? $data['publication_date'] : null,
                'video_url' => $data['video_url'] ?? null,
                'platform' => $data['platform'] ?? null,
                'product_name' => $data['product_name'] ?? null,
                'description' => $data['description'] ?? null,
            ];

            if ($output) {
                $output->update($outputData);
            } else {
                $output = AdditionalOutput::create($outputData);
            }

            // Save document file if uploaded
            if (
                isset($this->tempAdditionalFiles[$proposalOutputId]) &&
                $this->tempAdditionalFiles[$proposalOutputId] instanceof UploadedFile
            ) {
                $file = $this->tempAdditionalFiles[$proposalOutputId];

                $output->clearMediaCollection('book_document');
                $output
                    ->addMedia($file->getRealPath())
                    ->usingName($file->getClientOriginalName())
                    ->usingFileName($file->hashName())
                    ->withCustomProperties([
                        'uploaded_by' => Auth::id(),
                        'proposal_id' => $this->proposal->id,
                        'report_type' => $this->type,
                    ])
                    ->toMediaCollection('book_document');

                unset($this->tempAdditionalFiles[$proposalOutputId]);
            }

            // Save certificate file if uploaded
            if (
                isset($this->tempAdditionalCerts[$proposalOutputId]) &&
                $this->tempAdditionalCerts[$proposalOutputId] instanceof UploadedFile
            ) {
                $file = $this->tempAdditionalCerts[$proposalOutputId];

                $output->clearMediaCollection('publication_certificate');
                $output
                    ->addMedia($file->getRealPath())
                    ->usingName($file->getClientOriginalName())
                    ->usingFileName($file->hashName())
                    ->withCustomProperties([
                        'uploaded_by' => Auth::id(),
                        'proposal_id' => $this->proposal->id,
                        'report_type' => $this->type,
                    ])
                    ->toMediaCollection('publication_certificate');

                unset($this->tempAdditionalCerts[$proposalOutputId]);
            }
        });
    }
}
