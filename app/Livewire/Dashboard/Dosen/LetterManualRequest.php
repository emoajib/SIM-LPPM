<?php

namespace App\Livewire\Dashboard\Dosen;

use App\Livewire\Concerns\HasLetterForm;
use App\Livewire\Concerns\HasTeamSearch;
use App\Services\LetterService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Buat Surat', 'pageTitle' => 'Buat Surat Baru', 'pageSubtitle' => 'Ajukan surat tanpa proposal'])]
class LetterManualRequest extends Component
{
    use HasLetterForm, HasTeamSearch;

    public function submit(LetterService $service): void
    {
        $this->validate([
            'letterTypeId' => 'required|exists:letter_types,id',
            'title' => 'required|string|min:3',
            'activityType' => 'required|in:Penelitian,PKM',
            'date' => 'required|date',
            'timeStart' => 'required',
            'timeEnd' => 'required',
            'location' => 'required|string',
            'destinationName' => 'required_if:selectedLetterType.code,SP|nullable|string',
            'tembusan' => 'nullable|string',
        ]);

        try {
            $userName = auth()->user()->name;
            $userIdentifier = auth()->user()->identity->identity_id ?? '-';

            $teamData = array_values(array_filter($this->team, fn ($m) => ($m['name'] ?? '') !== $userName));

            array_unshift($teamData, [
                'name' => $userName,
                'role' => 'Ketua',
                'identifier' => $userIdentifier,
            ]);

            // Format date and time for PDF
            $dateObj = Carbon::parse($this->date);
            $dateString = $dateObj->translatedFormat('l, d F Y');
            $timeString = $this->timeStart.' - '.$this->timeEnd.' WIB';

            $service->requestManualLetter(auth()->user(), [
                'letterTypeId' => $this->letterTypeId,
                'title' => $this->title,
                'activityType' => $this->activityType,
                'dateString' => $dateString,
                'timeString' => $timeString,
                'location' => $this->location,
                'destinationName' => $this->destinationName,
                'tembusan' => $this->tembusan,
                'team' => $teamData,
                'reference_type' => $this->referenceType,
                'reference_id' => $this->referenceId,
            ]);

            session()->flash('success', 'Surat berhasil diajukan ke Kepala LPPM.');

            $this->redirect(route('dashboard.dosen.surat.dashboard'), navigate: true);
        } catch (\DomainException $e) {
            $this->dispatch('swal', title: 'Gagal', text: $e->getMessage(), icon: 'error');
        } catch (\Exception $e) {
            Log::error('Manual letter request failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('swal', title: 'Gagal', text: 'Terjadi kesalahan. Silakan coba lagi.', icon: 'error');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.dosen.letter-manual-request');
    }
}
