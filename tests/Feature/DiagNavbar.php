<?php
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);
beforeEach(fn () => $this->seed(\Database\Seeders\RoleSeeder::class));
it('diag', function () {
    $user = User::factory()->create();
    $user->assignRole('dosen');
    $response = $this->actingAs($user)->withSession(['active_role' => 'dosen', 'sim_lppm_app_theme' => 'modern'])->get(route('research.proposal.index'));
    $content = $response->getContent();
    file_put_contents('/tmp/research.html', $content);
    echo "STATUS=" . $response->getStatusCode() . "\n";
    echo "HAS_NAVBAR=" . (str_contains($content, 'navbar-nav') ? 'yes' : 'no') . "\n";
    echo "COUNT_LI=" . substr_count($content, '<li') . "\n";
    echo "HAS_PENGABDIAN=" . (str_contains($content, 'Pengabdian') ? 'yes' : 'no') . "\n";
    echo "HAS_MODERNROOT=" . (str_contains($content, 'modern-root') ? 'yes' : 'no') . "\n";
});
