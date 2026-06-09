<?php

namespace App\Imports;

use App\Models\Identity;
use App\Models\Institution;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function collection(Collection $collection)
    {
        DB::transaction(function () use ($collection) {
            foreach ($collection as $row) {
                $user = User::create([
                    'name' => $row['name'],
                    'username' => ! empty($row['username']) ? $row['username'] : null,
                    'email' => $row['email'],
                    'password' => Hash::make($row['password']),
                    'email_verified_at' => now(),
                ]);

                $user->assignRole(strtolower($row['type']));

                // Resolve Institution and Study Program
                $institutionId = null;
                if (! empty($row['inst'])) {
                    $institution = Institution::where('name', $row['inst'])
                        ->first();
                    $institutionId = $institution?->id;
                }

                $studyProgramId = null;
                if (! empty($row['prodi'])) {
                    $studyProgram = StudyProgram::where('name', $row['prodi'])
                        ->first();
                    $studyProgramId = $studyProgram?->id;
                }

                $identity = Identity::updateOrCreate(
                    ['identity_id' => $row['nidn']],
                    [
                        'user_id' => $user->id,
                        'type' => strtolower($row['type']),
                        'sinta_id' => $row['sinta'] ?? null,
                        'address' => $row['address'] ?? null,
                        'birthdate' => $row['birthdate'] ?? null,
                        'birthplace' => $row['birthplace'] ?? null,
                        'institution_id' => $institutionId,
                        'study_program_id' => $studyProgramId,
                        'sinta_score_v3_overall' => $row['sinta_score'] ?? 0,
                        'functional_position' => $row['jabfung'] ?? 'Tenaga Pengajar',
                    ]
                );
            }
        });
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:50|unique:users,username',
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => 'required|string|min:6',
            'nidn' => ['required', Rule::unique('identities', 'identity_id')],
            'type' => ['required', Rule::in(['dosen', 'mahasiswa'])],
            'sinta' => 'nullable',
            'address' => 'nullable',
            'birthdate' => 'nullable|date',
            'birthplace' => 'nullable',
            // ins and prodi must be exist in database
            'inst' => 'nullable|exists:institutions,name',
            'prodi' => 'nullable|exists:study_programs,name',
            'sinta_score' => 'nullable|numeric',
            'jabfung' => 'nullable|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'email.unique' => 'Email sudah terdaftar.',
            'nidn.unique' => 'NIDN/NIM sudah terdaftar.',
            'type.in' => "Tipe harus 'dosen' atau 'mahasiswa'.",
        ];
    }
}
