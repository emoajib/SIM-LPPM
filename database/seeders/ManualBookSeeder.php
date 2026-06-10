<?php

namespace Database\Seeders;

use App\Models\ManualBook;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ManualBookSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin lppm'))->first();

        if (! $admin) {
            return;
        }

        $books = [
            [
                'title' => 'Panduan Pengajuan Proposal Penelitian',
                'description' => 'Panduan lengkap bagi dosen dalam mengajukan proposal penelitian.',
                'version_number' => '1.0',
                'assigned_roles' => ['dosen'],
            ],
            [
                'title' => 'Panduan Pengajuan Proposal Pengabdian',
                'description' => 'Panduan lengkap bagi dosen dalam mengajukan proposal pengabdian.',
                'version_number' => '1.0',
                'assigned_roles' => ['dosen'],
            ],
            [
                'title' => 'Panduan Review Proposal',
                'description' => 'Panduan bagi reviewer dalam melakukan review proposal.',
                'version_number' => '1.0',
                'assigned_roles' => ['reviewer'],
            ],
            [
                'title' => 'Panduan Admin LPPM',
                'description' => 'Panduan pengelolaan sistem untuk admin LPPM.',
                'version_number' => '1.0',
                'assigned_roles' => ['admin lppm'],
            ],
            [
                'title' => 'Panduan Kepala LPPM',
                'description' => 'Panduan bagi kepala LPPM dalam melakukan persetujuan.',
                'version_number' => '1.0',
                'assigned_roles' => ['kepala lppm'],
            ],
            [
                'title' => 'Panduan Persetujuan Dekan',
                'description' => 'Panduan bagi dekan dalam memberikan persetujuan.',
                'version_number' => '1.0',
                'assigned_roles' => ['dekan'],
            ],
            [
                'title' => 'Panduan Persetujuan Kaprodi',
                'description' => 'Panduan bagi kaprodi dalam melakukan validasi proposal.',
                'version_number' => '1.0',
                'assigned_roles' => ['kaprodi'],
            ],
            [
                'title' => 'Panduan Rektor',
                'description' => 'Panduan bagi rektor dalam melihat laporan dan IKU.',
                'version_number' => '1.0',
                'assigned_roles' => ['rektor'],
            ],
            [
                'title' => 'Panduan Super Admin',
                'description' => 'Panduan pengelolaan sistem tingkat super admin.',
                'version_number' => '1.0',
                'assigned_roles' => ['superadmin'],
            ],
        ];

        foreach ($books as $book) {
            ManualBook::create([
                'id' => Str::uuid(),
                'title' => $book['title'],
                'description' => $book['description'],
                'version_number' => $book['version_number'],
                'status' => 'active',
                'assigned_roles' => $book['assigned_roles'],
                'created_by' => $admin->id,
            ]);
        }
    }
}
