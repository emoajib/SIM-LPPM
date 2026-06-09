<?php

namespace App\Livewire\Users;

use App\Actions\HandleHybridInstitution;
use App\Livewire\Concerns\HasToast;
use App\Models\Faculty;
use App\Models\Institution;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.app', ['title' => 'Create User', 'pageTitle' => 'Create User', 'pageSubtitle' => 'Add a new user to the system'])]
class Create extends Component
{
    use HasToast;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public array $selectedRoles = [];

    // Identity fields
    public string $identity_id = '';

    public string $address = '';

    public ?string $birthdate = null;

    public string $birthplace = '';

    public ?string $sinta_id = null;

    public ?string $scopus_id = null;

    public ?string $google_scholar_id = null;

    public ?string $wos_id = null;

    public string $title_prefix = '';

    public string $title_suffix = '';

    public ?int $scopus_h_index = null;

    public ?int $gs_h_index = null;

    public ?int $wos_h_index = null;

    public ?string $type = null;

    public ?string $institution_id = '1';

    public ?string $institution_name = '';

    public ?string $faculty_id = null;

    public ?string $study_program_id = null;

    /**
     * Persist the newly created user.
     */
    public function save(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'username' => ! empty($validated['username']) ? $validated['username'] : null,
                'email' => $validated['email'],
                'password' => $validated['password'],
                'email_verified_at' => now(),
            ]);

            // Handle hybrid institution
            $finalInstitutionId = app(HandleHybridInstitution::class)->execute($validated['institution_id']);
            $finalInstitutionName = is_numeric($validated['institution_id']) ? null : $validated['institution_id'];

            // Create identity
            $user->identity()->create([
                'identity_id' => $validated['identity_id'],
                'address' => $validated['address'],
                'birthdate' => $validated['birthdate'],
                'birthplace' => $validated['birthplace'],
                'sinta_id' => $validated['sinta_id'],
                'scopus_id' => $validated['scopus_id'],
                'google_scholar_id' => $validated['google_scholar_id'],
                'wos_id' => $validated['wos_id'],
                'title_prefix' => $validated['title_prefix'],
                'title_suffix' => $validated['title_suffix'],
                'scopus_h_index' => $validated['scopus_h_index'],
                'gs_h_index' => $validated['gs_h_index'],
                'wos_h_index' => $validated['wos_h_index'],
                'type' => $validated['type'],
                'institution_id' => $finalInstitutionId,
                'institution_name' => $finalInstitutionName,
                'faculty_id' => ! empty($validated['faculty_id']) ? $validated['faculty_id'] : null,
                'study_program_id' => ! empty($validated['study_program_id']) ? $validated['study_program_id'] : null,
            ]);

            // Sync multiple roles
            if (! empty($validated['selectedRoles'])) {
                $user->syncRoles($validated['selectedRoles']);
            }
        });

        $message = 'Pengguna baru telah dibuat.';
        session()->flash('success', $message);
        $this->toastSuccess($message);

        $this->redirect(route('users.index'), navigate: true);
    }

    /**
     * Render the component view.
     */
    #[Computed]
    public function isExempt(): bool
    {
        $roles = $this->selectedRoles;

        return count(array_intersect(['reviewer', 'superadmin', 'admin lppm'], array_map('strtolower', $roles))) > 0;
    }

    public function render(): View
    {
        return view('livewire.users.create', [
            'roleOptions' => $this->roleOptions(),
            'institutionOptions' => $this->institutionOptions(),
            'facultyOptions' => $this->facultyOptions(),
            'studyProgramOptions' => $this->studyProgramOptions(),
        ]);
    }

    /**
     * Validation rules for the create form.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $roles = $this->selectedRoles;
        $isExempt = count(array_intersect(['reviewer', 'superadmin', 'admin lppm'], array_map('strtolower', $roles))) > 0;
        $isInternal = $this->institution_id == '1'; // Assuming 1 is ITSNU

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50', Rule::unique(User::class)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::defaults()],
            'password_confirmation' => ['required', 'same:password'],
            'selectedRoles' => ['nullable', 'array'],
            'selectedRoles.*' => ['string', Rule::exists('roles', 'name')],
            'identity_id' => ['required', 'string', 'max:255', 'unique:identities,identity_id'],
            'address' => ['nullable', 'string', 'max:500'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'birthplace' => ['nullable', 'string', 'max:255'],
            'sinta_id' => ['nullable', 'string', 'max:255'],
            'scopus_id' => ['nullable', 'string', 'max:255'],
            'google_scholar_id' => ['nullable', 'string', 'max:255'],
            'wos_id' => ['nullable', 'string', 'max:255'],
            'title_prefix' => ['nullable', 'string', 'max:255'],
            'title_suffix' => ['nullable', 'string', 'max:255'],
            'scopus_h_index' => ['nullable', 'integer', 'min:0'],
            'gs_h_index' => ['nullable', 'integer', 'min:0'],
            'wos_h_index' => ['nullable', 'integer', 'min:0'],
            'type' => [$isExempt ? 'nullable' : 'required', Rule::in('dosen', 'mahasiswa', 'reviewer')],

            // Internal/Standard logic vs External Reviewer logic
            'institution_id' => ['nullable'], // Flexibel karena bisa ID (numeric) atau Nama (string)
            'institution_name' => ['nullable', 'string', 'max:255'],

            'faculty_id' => [
                $isExempt ? 'nullable' : 'required',
                'exists:faculties,id',
                function ($attribute, $value, $fail) {
                    if (is_numeric($this->institution_id) && $value) {
                        // Only validate existance within institution if institution is selected
                        $exists = Faculty::where('institution_id', $this->institution_id)->where('id', $value)->exists();
                        if (! $exists) {
                            $fail('Fakultas tidak valid untuk institusi yang dipilih.');
                        }
                    }
                },
            ],
            'study_program_id' => [
                $isExempt ? 'nullable' : 'required',
                'exists:study_programs,id',
                function ($attribute, $value, $fail) {
                    if ($this->faculty_id && $value) {
                        $exists = StudyProgram::where('faculty_id', $this->faculty_id)->where('id', $value)->exists();
                        if (! $exists) {
                            $fail('Program Studi tidak valid untuk fakultas yang dipilih.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Retrieve role options shown in the create modal.
     *
     * @return array<int, array<string, string>>
     */
    protected function roleOptions(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'value' => $role->name,
                'label' => str($role->name)->title()->toString(),
            ])
            ->values()
            ->all();
    }

    /**
     * Retrieve institution options for the selection control.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function institutionOptions(): array
    {
        return Institution::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Institution $institution) => [
                'value' => $institution->id,
                'label' => $institution->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Retrieve faculty options for the current institution.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function facultyOptions(): array
    {
        if (! $this->institution_id) {
            return [];
        }

        return Faculty::query()
            ->where('institution_id', $this->institution_id)
            ->orderBy('name')
            ->get()
            ->map(fn (Faculty $faculty) => [
                'value' => $faculty->id,
                'label' => $faculty->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Retrieve study program options for the current faculty.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function studyProgramOptions(): array
    {
        if (! $this->faculty_id) {
            return [];
        }

        return StudyProgram::query()
            ->where('faculty_id', $this->faculty_id)
            ->orderBy('name')
            ->get()
            ->map(fn (StudyProgram $program) => [
                'value' => $program->id,
                'label' => $program->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Updated institution.
     */
    public function updatedInstitutionId(): void
    {
        $this->faculty_id = null;
        $this->study_program_id = null;
    }

    /**
     * Updated faculty.
     */
    public function updatedFacultyId(): void
    {
        $this->study_program_id = null;
    }
}
