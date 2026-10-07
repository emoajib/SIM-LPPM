<?php

namespace App\Livewire\BudgetAmendment;

use App\Livewire\Concerns\HasToast;
use App\Models\BudgetAmendment;
use App\Models\BudgetGroup;
use App\Models\Proposal;
use App\Services\BudgetAmendmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Kelola amandemen RAB dari halaman logbook ( dipakai penelitian & pengabdian ).
 */
class Manager extends Component
{
    use HasToast;

    public string $proposalId = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public string $reason = '';

    public string $decisionNotes = '';

    public function mount(string $proposalId): void
    {
        $this->proposalId = $proposalId;
    }

    protected function proposal(): Proposal
    {
        return Proposal::findOrFail($this->proposalId);
    }

    protected function service(): BudgetAmendmentService
    {
        return app(BudgetAmendmentService::class);
    }

    protected function canManage(): bool
    {
        $proposal = $this->proposal();
        $userId = Auth::id();

        return $proposal->submitter_id === $userId
            || $proposal->teamMembers()->where('user_id', $userId)->exists();
    }

    protected function canDecide(): bool
    {
        return (bool) Auth::user()?->activeHasAnyRole(['kepala lppm', 'admin lppm', 'superadmin']);
    }

    public function openRequestModal(): void
    {
        $proposal = $this->proposal();

        if (! $this->canManage()) {
            abort(403);
        }

        $check = $this->service()->canRequest($proposal, Auth::user());
        if (! $check['ok']) {
            $this->toastError($check['reason']);

            return;
        }

        $this->items = $proposal->activeBudgetItems()
            ->orderBy('budget_group_id')
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => [
                'budget_group_id' => $item->budget_group_id,
                'budget_component_id' => $item->budget_component_id,
                'year' => $item->year ?? 1,
                'group' => $item->group,
                'component' => $item->component,
                'item_description' => $item->item_description,
                'volume' => $item->volume,
                'unit_price' => $item->unit_price,
            ])
            ->toArray();

        if ($this->items === []) {
            $this->items[] = $this->emptyRow();
        }

        $this->reason = '';
        $this->dispatch('open-modal', modalId: 'budget-amendment-modal');
    }

    protected function emptyRow(): array
    {
        return [
            'budget_group_id' => null,
            'budget_component_id' => null,
            'year' => 1,
            'group' => null,
            'component' => null,
            'item_description' => '',
            'volume' => 1,
            'unit_price' => 0,
        ];
    }

    public function addItemRow(): void
    {
        $this->items[] = $this->emptyRow();
    }

    public function removeItemRow(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function submitRequest(): void
    {
        if (! $this->canManage()) {
            abort(403);
        }

        try {
            $this->service()->request($this->proposal(), Auth::user(), $this->items, $this->reason);
            $this->dispatch('close-modal', modalId: 'budget-amendment-modal');
            $this->toastSuccess('Amandemen RAB berhasil diajukan ke LPPM.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Gagal mengajukan amandemen: '.$e->getMessage());
        }
    }

    public function cancelAmendment(string $amendmentId): void
    {
        if (! $this->canManage()) {
            abort(403);
        }

        try {
            $amendment = BudgetAmendment::findOrFail($amendmentId);
            $this->assertSameProposal($amendment);
            $this->service()->cancel($amendment, Auth::user());
            $this->toastSuccess('Pengajuan amandemen dibatalkan.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Gagal membatalkan: '.$e->getMessage());
        }
    }

    public function approveAmendment(string $amendmentId): void
    {
        if (! $this->canDecide()) {
            abort(403);
        }

        try {
            $amendment = BudgetAmendment::findOrFail($amendmentId);
            $this->assertSameProposal($amendment);
            $this->service()->approve($amendment, Auth::user(), $this->decisionNotes ?: null);
            $this->reset('decisionNotes');
            $this->toastSuccess('Amandemen RAB disetujui dan RAB aktif diperbarui.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Gagal menyetujui: '.$e->getMessage());
        }
    }

    public function rejectAmendment(string $amendmentId): void
    {
        if (! $this->canDecide()) {
            abort(403);
        }

        try {
            $amendment = BudgetAmendment::findOrFail($amendmentId);
            $this->assertSameProposal($amendment);
            $this->service()->reject($amendment, Auth::user(), $this->decisionNotes);
            $this->reset('decisionNotes');
            $this->toastSuccess('Amandemen RAB ditolak.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Gagal menolak: '.$e->getMessage());
        }
    }

    protected function assertSameProposal(BudgetAmendment $amendment): void
    {
        if ($amendment->proposal_id !== $this->proposalId) {
            abort(403);
        }
    }

    public function render()
    {
        $proposal = $this->proposal();

        return view('livewire.budget-amendment.manager', [
            'proposal' => $proposal,
            'amendments' => $proposal->budgetAmendments()->with(['items', 'requester'])->get(),
            'budgetGroups' => BudgetGroup::where('is_active', true)->orderBy('name')->get(),
            'canManage' => $this->canManage(),
            'canDecide' => $this->canDecide(),
            'pendingExists' => $proposal->hasPendingBudgetAmendment(),
        ]);
    }
}
