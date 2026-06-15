<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use App\Services\LetterService;
use Illuminate\Support\Facades\Log;

trait HasTeamSearch
{
    public $searchQuery = '';

    public $searchResults = [];

    public $team = [];

    public function updatedSearchQuery(): void
    {
        try {
            $query = (string) $this->searchQuery;
            if (strlen($query) < 2) {
                $this->searchResults = [];

                return;
            }

            $service = new LetterService;
            $this->searchResults = $service->searchDosen($query)->toArray();
        } catch (\Exception $e) {
            Log::error('Search dosen failed in team search', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->searchResults = [];
        }
    }

    public function addTeamMember(string $dosenId): void
    {
        $dosen = User::whereHas('roles', fn ($q) => $q->where('name', 'dosen'))
            ->where('id', $dosenId)
            ->with('identity')
            ->first();

        if (! $dosen) {
            $this->dispatch('swal', title: 'Gagal', text: 'Dosen tidak ditemukan.', icon: 'error');

            return;
        }

        foreach ($this->team as $member) {
            if ($member['id'] === $dosenId) {
                return;
            }
        }

        $this->team[] = [
            'id' => $dosen->id,
            'name' => $dosen->name,
            'role' => 'Anggota',
            'identifier' => $dosen->identity->identity_id ?? '-',
        ];

        $this->searchQuery = '';
        $this->searchResults = [];
    }

    public function removeTeamMember(int $index): void
    {
        unset($this->team[$index]);
        $this->team = array_values($this->team);
    }

    protected function buildTeamData(): array
    {
        return array_map(fn ($m) => [
            'name' => $m['name'],
            'role' => $m['role'],
            'identifier' => $m['identifier'] ?? '-',
        ], $this->team);
    }
}
