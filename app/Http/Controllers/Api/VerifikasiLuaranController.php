<?php

namespace App\Http\Controllers\Api;

use App\Models\AdditionalOutput;
use App\Models\MandatoryOutput;
use App\Models\StudyProgram;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifikasiLuaranController
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('search', '');
        $status = $request->get('status', 'all'); // all, verified, unverified
        $type = $request->get('type', 'all'); // all, mandatory, additional
        $module = $request->get('module', 'research'); // research, community-service
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);

        $isVerifier = $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor']);
        $isDosen = $user->activeHasRole('dosen');

        $detailableType = in_array($module, ['community-service', 'communityService', 'community_service']) ? 'App\\Models\\CommunityService' : 'App\\Models\\Research';

        $mandatoryQuery = MandatoryOutput::with([
            'progressReport.proposal.submitter.identity',
            'proposalOutput',
            'media',
        ])->whereHas('progressReport.proposal', fn ($q) => $q->where('detailable_type', $detailableType));

        $additionalQuery = AdditionalOutput::with([
            'progressReport.proposal.submitter.identity',
            'proposalOutput',
            'media',
        ])->whereHas('progressReport.proposal', fn ($q) => $q->where('detailable_type', $detailableType));

        // Role-based filtering
        if ($user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'superadmin'])) {
            // global access
        } elseif ($user->activeHasRole('dekan')) {
            if ($user->identity?->faculty_id) {
                $mandatoryQuery->whereHas('progressReport.proposal.submitter.identity', fn ($si) => $si->where('faculty_id', $user->identity->faculty_id));
                $additionalQuery->whereHas('progressReport.proposal.submitter.identity', fn ($si) => $si->where('faculty_id', $user->identity->faculty_id));
            } else {
                $mandatoryQuery->whereRaw('1=0');
                $additionalQuery->whereRaw('1=0');
            }
        } elseif ($user->activeHasRole('kaprodi')) {
            $kaprodiProdiId = StudyProgram::where('kaprodi_user_id', $user->id)->value('id') ?? $user->identity?->study_program_id;
            if ($kaprodiProdiId) {
                $mandatoryQuery->whereHas('progressReport.proposal.submitter.identity', fn ($si) => $si->where('study_program_id', $kaprodiProdiId));
                $additionalQuery->whereHas('progressReport.proposal.submitter.identity', fn ($si) => $si->where('study_program_id', $kaprodiProdiId));
            } else {
                $mandatoryQuery->whereRaw('1=0');
                $additionalQuery->whereRaw('1=0');
            }
        } elseif ($isDosen) {
            $mandatoryQuery->whereHas('progressReport.proposal', fn ($q) => $q->where('submitter_id', $user->id));
            $additionalQuery->whereHas('progressReport.proposal', fn ($q) => $q->where('submitter_id', $user->id));
        } else {
            $mandatoryQuery->whereRaw('1=0');
            $additionalQuery->whereRaw('1=0');
        }

        // Filter pencarian
        if ($search) {
            $mandatoryQuery->where(function ($q) use ($search) {
                $q->where('journal_title', 'like', "%{$search}%")
                    ->orWhere('article_title', 'like', "%{$search}%")
                    ->orWhereHas('progressReport.proposal', fn ($pq) => $pq->where('title', 'like', "%{$search}%"));
            });
            $additionalQuery->where(function ($q) use ($search) {
                $q->where('journal_title', 'like', "%{$search}%")
                    ->orWhere('book_title', 'like', "%{$search}%")
                    ->orWhereHas('progressReport.proposal', fn ($pq) => $pq->where('title', 'like', "%{$search}%"));
            });
        }

        // Filter status verifikasi
        if ($status === 'verified') {
            $mandatoryQuery->where('is_verified', true);
            $additionalQuery->where('is_verified', true);
        } elseif ($status === 'unverified') {
            $mandatoryQuery->where('is_verified', false);
            $additionalQuery->where('is_verified', false);
        }

        // Filter tipe output
        if ($type === 'mandatory') {
            $additionalQuery->whereRaw('1=0');
        } elseif ($type === 'additional') {
            $mandatoryQuery->whereRaw('1=0');
        }

        $mandatory = $mandatoryQuery->get()->map(fn ($item) => $this->formatOutputItem($item, 'mandatory', $user));
        $additional = $additionalQuery->get()->map(fn ($item) => $this->formatOutputItem($item, 'additional', $user));

        $combined = $mandatory->concat($additional)->sortByDesc('created_at');

        // Pagination manual
        $total = $combined->count();
        $items = $combined->slice(($page - 1) * $perPage, $perPage)->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => (int) $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'total' => $total,
                'per_page' => (int) $perPage,
            ],
            'can_verify' => $isVerifier,
            'is_dosen' => $isDosen,
        ]);
    }

    public function verify(Request $request, string $id)
    {
        $user = Auth::user();
        if (! $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $modelType = $request->get('model_type', 'mandatory');
        $model = match ($modelType) {
            'mandatory' => MandatoryOutput::find($id),
            'additional' => AdditionalOutput::find($id),
            default => null,
        };

        if (! $model) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $model->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Data berhasil diverifikasi.',
            'data' => $this->formatOutputItem($model->fresh(), $modelType, $user),
        ]);
    }

    public function unverify(Request $request, string $id)
    {
        $user = Auth::user();
        if (! $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $modelType = $request->get('model_type', 'mandatory');
        $model = match ($modelType) {
            'mandatory' => MandatoryOutput::find($id),
            'additional' => AdditionalOutput::find($id),
            default => null,
        };

        if (! $model) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $model->update([
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        return response()->json([
            'message' => 'Verifikasi dibatalkan.',
            'data' => $this->formatOutputItem($model->fresh(), $modelType, $user),
        ]);
    }

    public function updateRank(Request $request, string $id)
    {
        $user = Auth::user();
        if (! $user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'model_type' => 'required|in:mandatory,additional',
            'rank' => 'nullable|string|in:S1,S2,S3,S4,S5,S6,Q1,Q2,Q3,Q4',
        ]);

        $model = match ($request->model_type) {
            'mandatory' => MandatoryOutput::find($id),
            'additional' => AdditionalOutput::find($id),
            default => null,
        };

        if (! $model) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $model->update(['rank' => $request->rank]);

        return response()->json([
            'message' => 'Peringkat diperbarui.',
            'data' => $this->formatOutputItem($model->fresh(), $request->model_type, $user),
        ]);
    }

    public function updateStatus(Request $request, string $id)
    {
        $user = Auth::user();
        if (! $user->activeHasRole('dosen')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'model_type' => 'required|in:mandatory,additional',
            'status_type' => 'sometimes|in:draft,submitted,published,accepted,under_review,rejected',
            'author_status' => 'sometimes|in:first_author,co_author,corresponding_author',
        ]);

        $model = match ($request->model_type) {
            'mandatory' => MandatoryOutput::find($id),
            'additional' => AdditionalOutput::find($id),
            default => null,
        };

        if (! $model) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Dosen hanya bisa mengedit output milik proposalnya sendiri
        if ($model->progressReport?->proposal?->submitter_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $update = [];
        if ($request->has('status_type')) {
            $update['status_type'] = $request->status_type;
        }
        if ($request->has('author_status')) {
            $update['author_status'] = $request->author_status;
        }

        if (! empty($update)) {
            $model->update($update);
        }

        return response()->json([
            'message' => 'Status output diperbarui.',
            'data' => $this->formatOutputItem($model->fresh(), $request->model_type, $user),
        ]);
    }

    public function updateEvidence(Request $request, string $id)
    {
        $user = Auth::user();

        $request->validate([
            'model_type' => 'required|in:mandatory,additional',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'url' => 'nullable|url|max:500',
        ]);

        $model = match ($request->model_type) {
            'mandatory' => MandatoryOutput::find($id),
            'additional' => AdditionalOutput::find($id),
            default => null,
        };

        if (! $model) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Dosen hanya bisa upload bukti untuk output miliknya sendiri
        $proposal = $model->progressReport?->proposal;
        if ($user->activeHasRole('dosen') && $proposal?->submitter_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Blokir upload jika sudah verified (kecuali unverify dulu)
        if ($model->is_verified && $user->activeHasRole('dosen')) {
            return response()->json(['message' => 'Output sudah diverifikasi, tidak bisa diubah.'], 403);
        }

        $update = [];
        if ($request->has('url')) {
            $update['article_url'] = $request->url;
        }

        if ($request->hasFile('file')) {
            $compressionService = app(ImageCompressionService::class);
            $filePath = $compressionService->compressIfImage($request->file('file'));
            $model->addMedia($filePath)
                ->usingName($request->file('file')->getClientOriginalName())
                ->usingFileName($request->file('file')->getClientOriginalName())
                ->toMediaCollection('evidence');
            $update['document_file'] = $request->file('file')->getClientOriginalName();
        }

        if (! empty($update)) {
            $model->update($update);
        }

        return response()->json([
            'message' => 'Bukti luaran berhasil diperbarui.',
            'data' => $this->formatOutputItem($model->fresh(), $request->model_type, $user),
        ]);
    }

    public function updateDetail(Request $request, string $id)
    {
        $user = Auth::user();

        $request->validate([
            'model_type' => 'required|in:mandatory,additional',
            'category' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'status_type' => 'nullable|string|in:draft,submitted,published,accepted,under_review,rejected',
            'author_status' => 'nullable|string|in:first_author,co_author,corresponding_author',
            'journal_title' => 'nullable|string|max:255',
            'article_title' => 'nullable|string|max:255',
            'book_title' => 'nullable|string|max:255',
            'issn' => 'nullable|string|max:50',
            'eissn' => 'nullable|string|max:50',
            'indexing_body' => 'nullable|string|max:100',
            'volume' => 'nullable|string|max:50',
            'issue_number' => 'nullable|string|max:50',
            'page_start' => 'nullable|string|max:50',
            'page_end' => 'nullable|string|max:50',
            'article_url' => 'nullable|url|max:500',
            'doi' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer|between:1900,2100',
            'publication_date' => 'nullable|date',
            'publisher' => 'nullable|string|max:255',
            'publisher_url' => 'nullable|url|max:500',
            'product_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'readiness_level' => 'nullable|string|max:100',
            'implementation_location' => 'nullable|string|max:255',
            'media_name' => 'nullable|string|max:255',
            'media_url' => 'nullable|url|max:500',
            'video_url' => 'nullable|url|max:500',
            'platform' => 'nullable|string|max:255',
            'document_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $model = match ($request->model_type) {
            'mandatory' => MandatoryOutput::find($id),
            'additional' => AdditionalOutput::find($id),
            default => null,
        };

        if (! $model) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Dosen hanya bisa mengedit output miliknya sendiri
        $proposal = $model->progressReport?->proposal;
        if ($user->activeHasRole('dosen') && $proposal?->submitter_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Blokir edit jika sudah verified (kecuali unverify dulu)
        if ($model->is_verified && $user->activeHasRole('dosen')) {
            return response()->json(['message' => 'Output sudah diverifikasi, tidak bisa diubah.'], 403);
        }

        $update = [];

        // Status dan author status
        if ($request->has('status_type')) {
            $update['status_type'] = $request->status_type;
        }
        if ($request->has('author_status')) {
            $update['author_status'] = $request->author_status;
        }

        // Journal fields (MandatoryOutput & AdditionalOutput)
        if ($request->has('journal_title')) {
            $update['journal_title'] = $request->journal_title;
        }
        if ($request->has('issn')) {
            $update['issn'] = $request->issn;
        }
        if ($request->has('eissn')) {
            $update['eissn'] = $request->eissn;
        }
        if ($request->has('indexing_body')) {
            $update['indexing_body'] = $request->indexing_body;
        }
        if ($request->has('journal_url')) {
            $update['journal_url'] = $request->journal_url;
        }
        if ($request->has('article_title')) {
            $update['article_title'] = $request->article_title;
        }
        if ($request->has('publication_year')) {
            $update['publication_year'] = $request->publication_year;
        }
        if ($request->has('volume')) {
            $update['volume'] = $request->volume;
        }
        if ($request->has('issue_number')) {
            $update['issue_number'] = $request->issue_number;
        }
        if ($request->has('page_start')) {
            $update['page_start'] = $request->page_start;
        }
        if ($request->has('page_end')) {
            $update['page_end'] = $request->page_end;
        }
        if ($request->has('article_url')) {
            $update['article_url'] = $request->article_url;
        }
        if ($request->has('doi')) {
            $update['doi'] = $request->doi;
        }

        // Book fields
        if ($request->has('book_title')) {
            $update['book_title'] = $request->book_title;
        }
        if ($request->has('publisher')) {
            $update['publisher'] = $request->publisher;
        }
        if ($request->has('isbn')) {
            $update['isbn'] = $request->isbn;
        }
        if ($request->has('publisher_url')) {
            $update['publisher_url'] = $request->publisher_url;
        }
        if ($request->has('book_url')) {
            $update['book_url'] = $request->book_url;
        }
        if ($request->has('total_pages')) {
            $update['total_pages'] = $request->total_pages;
        }

        // HKI fields
        if ($request->has('hki_type')) {
            $update['hki_type'] = $request->hki_type;
        }
        if ($request->has('registration_number')) {
            $update['registration_number'] = $request->registration_number;
        }
        if ($request->has('inventors')) {
            $update['inventors'] = $request->inventors;
        }

        // Product fields
        if ($request->has('product_name')) {
            $update['product_name'] = $request->product_name;
        }
        if ($request->has('description')) {
            $update['description'] = $request->description;
        }
        if ($request->has('readiness_level')) {
            $update['readiness_level'] = $request->readiness_level;
        }
        if ($request->has('implementation_location')) {
            $update['implementation_location'] = $request->implementation_location;
        }

        // Media fields
        if ($request->has('media_name')) {
            $update['media_name'] = $request->media_name;
        }
        if ($request->has('media_url')) {
            $update['media_url'] = $request->media_url;
        }

        // Video fields
        if ($request->has('video_url')) {
            $update['video_url'] = $request->video_url;
        }
        if ($request->has('platform')) {
            $update['platform'] = $request->platform;
        }

        // Publication date
        if ($request->has('publication_date')) {
            $update['publication_date'] = $request->publication_date;
        }

        // Document file upload
        if ($request->hasFile('document_file')) {
            $compressionService = app(ImageCompressionService::class);
            $filePath = $compressionService->compressIfImage($request->file('document_file'));
            $model->addMedia($filePath)
                ->usingName($request->file('document_file')->getClientOriginalName())
                ->usingFileName($request->file('document_file')->getClientOriginalName())
                ->toMediaCollection('evidence');
            $update['document_file'] = $request->file('document_file')->getClientOriginalName();
        }

        if (! empty($update)) {
            $model->update($update);
        }

        return response()->json([
            'message' => 'Detail output berhasil diperbarui.',
            'data' => $this->formatOutputItem($model->fresh(), $request->model_type, $user),
        ]);
    }

    private function formatOutputItem($item, string $modelType, $user = null): array
    {
        $proposal = $item->progressReport?->proposal;

        return [
            'id' => $item->id,
            'model_type' => $modelType,
            'display_title' => $item->journal_title ?? $item->article_title ?? $item->book_title ?? $item->product_name ?? $item->title,
            'category' => $item->proposalOutput->category ?? null,
            'status_type' => $item->status_type,
            'author_status' => $item->author_status,
            'is_verified' => $item->is_verified,
            'verified_at' => $item->verified_at?->format('d M Y H:i'),
            'verified_by' => $item->verified_by,
            'rank' => $item->rank,
            'submitter_name' => $proposal->submitter->name ?? 'Unknown',
            'submitter_id' => $proposal?->submitter_id,
            'is_team_member' => $user ? ($proposal?->teamMembers()->where('user_id', $user->id)->exists() ?? false) : false,
            'proposal_title' => $proposal->title ?? 'Unknown',
            'document_url' => $item->document_file ? asset('storage/'.$item->document_file) : null,
            'article_url' => $item->article_url,
            'journal_url' => $item->journal_url,
            'media_url' => $item->media_url,
            'video_url' => $item->video_url,
            'evidence' => $item->getMedia('evidence')->map(function ($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->file_name,
                    'url' => $m->getUrl(),
                ];
            }),
            'created_at' => $item->created_at?->format('d M Y'),
        ];
    }
}
