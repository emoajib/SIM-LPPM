<?php

namespace App\Http\Controllers\Api;

use App\Models\BudgetGroup;
use App\Models\DailyNote;
use App\Models\Proposal;
use App\Models\StudyProgram;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DailyNoteController
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $proposalId = $request->query('proposal_id');

        if (! $proposalId) {
            return response()->json(['message' => 'proposal_id wajib diisi.'], 400);
        }

        $proposal = Proposal::find($proposalId);
        if (! $proposal) {
            return response()->json(['message' => 'Proposal tidak ditemukan.'], 404);
        }

        if (! $this->canAccess($user, $proposal)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notes = $proposal->dailyNotes()
            ->with(['budgetGroup', 'media'])
            ->orderBy('activity_date', 'desc')
            ->get()
            ->map(fn ($note) => $this->formatNote($note));

        return response()->json([
            'proposal' => [
                'id' => $proposal->id,
                'title' => $proposal->title,
                'status' => $proposal->status->value,
                'status_label' => $proposal->status->label(),
                'total_proposed_budget' => (float) $proposal->activeBudgetItems()->sum('total_price'),
                'total_actual' => (float) $proposal->dailyNotes->sum('amount'),
            ],
            'budget_groups' => $proposal->activeBudgetItems()
                ->pluck('budget_group_id')
                ->unique()
                ->map(function ($bgId) use ($proposal) {
                    $bg = BudgetGroup::find($bgId);
                    if (! $bg) {
                        return null;
                    }
                    $allocated = (float) $proposal->activeBudgetItems()
                        ->where('budget_group_id', $bgId)
                        ->sum('total_price');
                    $used = (float) $proposal->dailyNotes()
                        ->where('budget_group_id', $bgId)
                        ->sum('amount');

                    return [
                        'id' => $bg->id,
                        'code' => $bg->code,
                        'name' => $bg->name,
                        'allocated' => $allocated,
                        'used' => $used,
                        'remaining' => $allocated - $used,
                    ];
                })
                ->filter()
                ->values(),
            'notes' => $notes,
            'can_manage' => $this->canManage($user, $proposal),
            'is_editable' => ! $proposal->logbook_approved_at,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'proposal_id' => 'required|exists:proposals,id',
            'activity_date' => 'required|date|before_or_equal:today',
            'activity_description' => 'required|string|min:10',
            'progress_percentage' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string',
            'budget_group_id' => 'nullable|exists:budget_groups,id',
            'amount' => 'nullable|numeric|min:0',
            'evidence.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $proposal = Proposal::find($request->proposal_id);
        if (! $this->canManage($user, $proposal)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($proposal->logbook_approved_at) {
            return response()->json([
                'message' => 'LPJ sudah disahkan LPPM dan terkunci.',
            ], 403);
        }

        $amount = (float) ($request->amount ?? 0);

        // Budget constraint check
        if ($request->budget_group_id && $amount > 0) {
            $allocated = (float) $proposal->activeBudgetItems()
                ->where('budget_group_id', $request->budget_group_id)
                ->sum('total_price');
            $used = (float) $proposal->dailyNotes()
                ->where('budget_group_id', $request->budget_group_id)
                ->sum('amount');

            if ($amount > $allocated - $used) {
                return response()->json([
                    'message' => 'Nominal pengeluaran melebihi sisa anggaran untuk kategori ini.',
                ], 422);
            }
        }

        $note = DailyNote::create([
            'proposal_id' => $proposal->id,
            'activity_date' => $request->activity_date,
            'activity_description' => $request->activity_description,
            'progress_percentage' => $request->progress_percentage,
            'notes' => $request->notes ?? '',
            'budget_group_id' => $request->budget_group_id,
            'amount' => $amount,
        ]);

        if ($request->hasFile('evidence')) {
            $compressionService = app(ImageCompressionService::class);
            foreach ($request->file('evidence') as $file) {
                $filePath = $compressionService->compressIfImage($file);
                $note->addMedia($filePath)
                    ->usingName($file->getClientOriginalName())
                    ->usingFileName($file->getClientOriginalName())
                    ->toMediaCollection('evidence');
            }
        }

        $note->load(['budgetGroup', 'media']);

        return response()->json([
            'message' => 'Catatan harian berhasil disimpan.',
            'data' => $this->formatNote($note),
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $note = DailyNote::find($id);
        if (! $note) {
            return response()->json(['message' => 'Catatan harian tidak ditemukan.'], 404);
        }

        $proposal = $note->proposal;
        if (! $this->canManage($user, $proposal)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($proposal->logbook_approved_at) {
            return response()->json([
                'message' => 'LPJ sudah disahkan LPPM dan terkunci.',
            ], 403);
        }

        $request->validate([
            'activity_date' => 'sometimes|required|date|before_or_equal:today',
            'activity_description' => 'sometimes|required|string|min:10',
            'progress_percentage' => 'sometimes|required|integer|min:0|max:100',
            'notes' => 'nullable|string',
            'budget_group_id' => 'nullable|exists:budget_groups,id',
            'amount' => 'nullable|numeric|min:0',
            'evidence.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $data = [];
        if ($request->has('activity_date')) {
            $data['activity_date'] = $request->activity_date;
        }
        if ($request->has('activity_description')) {
            $data['activity_description'] = $request->activity_description;
        }
        if ($request->has('progress_percentage')) {
            $data['progress_percentage'] = $request->progress_percentage;
        }
        if ($request->has('notes')) {
            $data['notes'] = $request->notes;
        }
        if ($request->has('budget_group_id')) {
            $data['budget_group_id'] = $request->budget_group_id;
        }
        if ($request->has('amount')) {
            $data['amount'] = (float) $request->amount;
        }

        // Budget constraint check on update
        if (isset($data['budget_group_id']) || isset($data['amount'])) {
            $bgId = $data['budget_group_id'] ?? $note->budget_group_id;
            $amount = $data['amount'] ?? $note->amount;
            if ($bgId && $amount > 0) {
                $allocated = (float) $proposal->activeBudgetItems()
                    ->where('budget_group_id', $bgId)
                    ->sum('total_price');
                $used = (float) $proposal->dailyNotes()
                    ->where('budget_group_id', $bgId)
                    ->where('id', '!=', $note->id)
                    ->sum('amount');

                if ($amount > $allocated - $used) {
                    return response()->json([
                        'message' => 'Nominal pengeluaran melebihi sisa anggaran untuk kategori ini.',
                    ], 422);
                }
            }
        }

        $note->update($data);

        if ($request->hasFile('evidence')) {
            $compressionService = app(ImageCompressionService::class);
            foreach ($request->file('evidence') as $file) {
                $filePath = $compressionService->compressIfImage($file);
                $note->addMedia($filePath)
                    ->usingName($file->getClientOriginalName())
                    ->usingFileName($file->getClientOriginalName())
                    ->toMediaCollection('evidence');
            }
        }

        $note->load(['budgetGroup', 'media']);

        return response()->json([
            'message' => 'Catatan harian berhasil diperbarui.',
            'data' => $this->formatNote($note),
        ]);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        $note = DailyNote::find($id);
        if (! $note) {
            return response()->json(['message' => 'Catatan harian tidak ditemukan.'], 404);
        }

        $proposal = $note->proposal;
        if (! $this->canManage($user, $proposal)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($proposal->logbook_approved_at) {
            return response()->json([
                'message' => 'LPJ sudah disahkan LPPM dan terkunci.',
            ], 403);
        }

        $note->delete();

        return response()->json(['message' => 'Catatan harian berhasil dihapus.']);
    }

    public function deleteEvidence(Request $request, string $mediaId)
    {
        $user = Auth::user();
        $media = Media::find($mediaId);
        if (! $media) {
            return response()->json(['message' => 'File tidak ditemukan.'], 404);
        }

        $note = DailyNote::find($media->model_id);
        if (! $note || $note->proposal_id !== $request->proposal_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $proposal = $note->proposal;
        if (! $this->canManage($user, $proposal)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($proposal->logbook_approved_at) {
            return response()->json([
                'message' => 'LPJ sudah disahkan LPPM dan terkunci.',
            ], 403);
        }

        $media->delete();

        return response()->json(['message' => 'File bukti berhasil dihapus.']);
    }

    private function canAccess($user, Proposal $proposal): bool
    {
        if ($user->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'superadmin'])) {
            return true;
        }
        if ($user->activeHasRole('dekan')) {
            return (bool) ($user->identity?->faculty_id && $user->identity->faculty_id === $proposal->submitter->identity?->faculty_id);
        }
        if ($user->activeHasRole('kaprodi')) {
            $kaprodiProdiId = StudyProgram::where('kaprodi_user_id', $user->id)->value('id')
                ?? $user->identity?->study_program_id;

            return (bool) ($kaprodiProdiId && $kaprodiProdiId === $proposal->submitter->identity?->study_program_id);
        }

        return $proposal->submitter_id === $user->id ||
            $proposal->teamMembers()->where('user_id', $user->id)->exists();
    }

    private function canManage($user, Proposal $proposal): bool
    {
        return $proposal->submitter_id === $user->id ||
            $proposal->teamMembers()->where('user_id', $user->id)->exists();
    }

    private function formatNote(DailyNote $note): array
    {
        return [
            'id' => $note->id,
            'activity_date' => $note->activity_date?->format('Y-m-d'),
            'activity_description' => $note->activity_description,
            'progress_percentage' => $note->progress_percentage,
            'notes' => $note->notes,
            'budget_group_id' => $note->budget_group_id,
            'budget_group_name' => $note->budgetGroup?->name,
            'budget_group_code' => $note->budgetGroup?->code,
            'amount' => (float) $note->amount,
            'evidence' => $note->getMedia('evidence')->map(function ($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->file_name,
                    'url' => $m->getUrl(),
                ];
            }),
            'created_at' => $note->created_at?->format('d M Y H:i'),
        ];
    }
}
