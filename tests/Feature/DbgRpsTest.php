<?php
use App\Enums\ProposalStatus;
use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
uses(RefreshDatabase::class);
test('debug rps save', function () {
    $dosen = User::factory()->create();
    $this->actingAs($dosen);
    $detail = CommunityService::factory()->create();
    $proposal = Proposal::factory()->create(['submitter_id' => $dosen->id,'detailable_type' => CommunityService::class,'detailable_id' => $detail->id,'status' => ProposalStatus::COMPLETED]);
    \Livewire\Livewire::test(\App\Livewire\CommunityService\FinalReport\Show::class, ['proposal' => $proposal])
        ->set('form.summaryUpdate', 'Ringkasan akhir PKM dengan RPS')
        ->set('form.keywordsInput', 'pkm; rps; test')
        ->set('substanceFile', UploadedFile::fake()->create('laporan.pdf', 100))
        ->set('rpsFile', UploadedFile::fake()->create('rps.pdf', 100))
        ->call('save')->assertHasNoErrors();
});
