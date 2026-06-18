<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL DEFERRED;');
        }

        Institution::truncate();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL IMMEDIATE;');
        }

        $installerConfig = Cache::get('installer_institution_config', []);
        $merged = array_merge(config('institution', []), $installerConfig);

        $payload = [[
            'code' => $merged['code'] ?? '062004',
            'name' => $merged['name'] ?? $merged['full_name'] ?? 'Institut Teknologi dan Sains Nahdlatul Ulama Pekalongan',
            'short_name' => $merged['short_name'] ?? 'ITSNU Pekalongan',
            'type' => $merged['type'] ?? 'university',
            'address' => $merged['address'] ?? '',
            'phone' => $merged['phone'] ?? '',
            'email' => $merged['email'] ?? 'info@itsnu.ac.id',
            'website' => $merged['website'] ?? 'https://itsnu.ac.id',
            'is_verified' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]];

        Institution::insert($payload);

        $syncKeys = [
            'name', 'full_name', 'short_name', 'lppm_name', 'lppm_full_name',
            'lppm_short', 'address', 'address_line1', 'address_line2',
            'phone', 'email', 'email_public', 'website', 'website_main',
            'city', 'postal_code', 'motto',
            'lppm_head_name', 'lppm_head_nidn', 'lppm_head_position',
        ];

        foreach ($syncKeys as $key) {
            $value = $merged[$key] ?? null;
            if ($value !== null && $value !== '') {
                Setting::set("institution_{$key}", $value, 'string');
            }
        }

        $this->command->info('✅ Institution seeded & settings synchronized.');
    }
}
