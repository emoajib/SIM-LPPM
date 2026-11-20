<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Appearance;
use App\Models\Identity;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Avatar default berbasis gender yang diatur admin LPPM di Pengaturan > Tampilan.
 * Prioritas render: foto unggahan user -> foto identitas -> avatar default gender -> inisial.
 */
class GenderAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
        Setting::clearCache();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin lppm');

        return $user;
    }

    private function userWithGender(string $gender): User
    {
        $user = User::factory()->create();
        Identity::factory()->create([
            'user_id' => $user->id,
            'gender' => $gender,
            'profile_picture' => null,
        ]);

        return $user->fresh();
    }

    private function uploadBothAvatars(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        session(['active_role' => 'admin lppm']);

        // Memilih berkas sudah cukup: hook updatedAvatarMale/Female menyimpannya.
        Livewire::test(Appearance::class)
            ->set('avatarMale', UploadedFile::fake()->image('pria.png', 200, 200))
            ->assertHasNoErrors();

        Livewire::test(Appearance::class)
            ->set('avatarFemale', UploadedFile::fake()->image('wanita.png', 200, 200))
            ->assertHasNoErrors();
    }

    public function test_admin_can_upload_avatar_pria_dan_wanita(): void
    {
        $this->uploadBothAvatars();

        $male = Setting::where('key', 'default_avatar_male')->first();
        $female = Setting::where('key', 'default_avatar_female')->first();

        $this->assertNotNull($male?->getFirstMedia('avatar'));
        $this->assertNotNull($female?->getFirstMedia('avatar'));
        $this->assertNotSame(
            $male->getFirstMediaUrl('avatar'),
            $female->getFirstMediaUrl('avatar'),
            'Avatar pria dan wanita harus tersimpan sebagai berkas terpisah.'
        );
    }

    public function test_pengguna_tanpa_foto_mendapat_avatar_sesuai_gender(): void
    {
        $this->uploadBothAvatars();

        $maleUrl = Setting::mediaUrl('default_avatar_male', 'avatar');
        $femaleUrl = Setting::mediaUrl('default_avatar_female', 'avatar');
        $this->assertNotNull($maleUrl);
        $this->assertNotNull($femaleUrl);

        $male = $this->userWithGender('L');
        $female = $this->userWithGender('P');

        $this->assertSame($maleUrl, $male->profile_picture);
        $this->assertSame($femaleUrl, $female->profile_picture);
    }

    public function test_foto_unggahan_pengguna_tetap_menang_atas_avatar_default(): void
    {
        $this->uploadBothAvatars();

        $user = $this->userWithGender('P');
        $file = UploadedFile::fake()->image('foto-sendiri.png', 300, 300);
        $user->addMedia($file->getRealPath())
            ->usingName('foto-sendiri')
            ->toMediaCollection('avatar');

        $this->assertSame(
            $user->fresh()->getFirstMediaUrl('avatar'),
            $user->fresh()->profile_picture,
            'Foto profil unggahan sendiri harus dipakai sebelum avatar default gender.'
        );
    }

    public function test_tanpa_avatar_default_pengguna_melihat_inisial(): void
    {
        $user = $this->userWithGender('L');

        $this->assertNull($user->profile_picture);

        $html = $this->blade('<x-user-avatar :user="$user" />', ['user' => $user])->__toString();

        $this->assertStringContainsString($user->initials(), $html);
        $this->assertStringNotContainsString('background-image', $html);
    }

    public function test_admin_dapat_menghapus_avatar_default(): void
    {
        $this->uploadBothAvatars();

        $admin = $this->admin();
        $this->actingAs($admin);
        session(['active_role' => 'admin lppm']);

        Livewire::test(Appearance::class)->call('removeAvatarMale');

        Setting::clearCache();

        $this->assertNull(Setting::mediaUrl('default_avatar_male', 'avatar'));
        $this->assertNotNull(Setting::mediaUrl('default_avatar_female', 'avatar'));
        $this->assertNull($this->userWithGender('L')->profile_picture);
    }

    public function test_hanya_admin_lppm_dan_superadmin_yang_bisa_membuka_pengaturan(): void
    {
        $dosen = User::factory()->create();
        $dosen->assignRole('dosen');
        $this->actingAs($dosen);

        $this->get(route('settings.appearance'))->assertForbidden();

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $this->actingAs($superadmin);
        session(['active_role' => 'superadmin']);

        $this->get(route('settings.appearance'))->assertOk();
    }

    public function test_berkas_bukan_gambar_ditolak(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        session(['active_role' => 'admin lppm']);

        Livewire::test(Appearance::class)
            ->set('avatarMale', UploadedFile::fake()->create('jahat.svg', 10, 'image/svg+xml'))
            ->assertHasErrors(['avatarMale']);
    }

    public function test_komponen_avatar_merender_avatar_default(): void
    {
        $this->uploadBothAvatars();

        $male = $this->userWithGender('L');
        $expected = Setting::mediaUrl('default_avatar_male', 'avatar');

        $html = $this->blade('<x-user-avatar :user="$user" />', ['user' => $male])->__toString();

        $this->assertStringContainsString($expected, $html);
    }
}
