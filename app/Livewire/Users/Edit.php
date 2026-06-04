<?php

namespace App\Livewire\Users;

use App\Actions\HandleHybridInstitution;
use App\Livewire\Concerns\HasToast;
use App\Models\Faculty;
use App\Models\Institution;
use App\Models\ScienceCluster;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * @property-read User $user
 */
#[Layout('components.layouts.app', ['title' => 'Edit User', 'pageTitle' => 'Edit User', 'pageSubtitle' => 'Update the user profile and role assignments'])]
class Edit extends Component
{
    use HasToast;

    public string $userId;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public array $selectedRoles = [];

    public bool $emailVerified = false;

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

    public int $scopus_h_index = 0;

    public float $sinta_score_v3_overall = 0;

    public string $functional_position = '';

    public int $gs_h_index = 0;

    public int $wos_h_index = 0;

    public ?string $type = null;

    public ?string $institution_id = null;

    public ?string $institution_name = null;

    public ?string $faculty_id = null;

    public ?string $study_program_id = null;

    public ?string $science_cluster_id = null;

    // Password Management
    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Hydrate the component state from the selected user.
     */
    public function mount(User $user): void
    {
        $this->userId = $user->getKey();
        $this->name = $user->name;
        $this->username = $user->username ?? '';
        $this->email = $user->email;
        $this->selectedRoles = $user->getRoleNames()->toArray();
        $this->emailVerified = $user->hasVerifiedEmail();

        // Load identity data
        if ($user->identity) {
            $this->identity_id = $user->identity->identity_id ?? '';
            $this->address = $user->identity->address ?? '';
            $this->birthdate = $user->identity->birthdate?->format('Y-m-d');
            $this->birthplace = $user->identity->birthplace ?? '';
            $this->sinta_id = $user->identity->sinta_id ?? '';
            $this->scopus_id = $user->identity->scopus_id ?? '';
            $this->google_scholar_id = $user->identity->google_scholar_id ?? '';
            $this->wos_id = $user->identity->wos_id ?? '';
            $this->title_prefix = $user->identity->title_prefix ?? '';
            $this->title_suffix = $user->identity->title_suffix ?? '';
            $this->scopus_h_index = $user->identity->scopus_h_index ?? 0;
            $this->sinta_score_v3_overall = $user->identity->sinta_score_v3_overall ?? 0;
            $this->functional_position = $user->identity->functional_position ?? '';
            $this->gs_h_index = $user->identity->gs_h_index ?? 0;
            $this->wos_h_index = $user->identity->wos_h_index ?? 0;
            $this->type = $user->identity->type ?? '';
            $this->institution_id = $user->identity->institution_id ?? '';
            // Load from column first
            $this->institution_name = $user->identity->institution_name ?? '';
            $this->faculty_id = $user->identity->faculty_id ?? '';
            $this->study_program_id = $user->identity->study_program_id ?? '';
            $this->science_cluster_id = $user->identity->science_cluster_id ?? '';
        }
    }

    /**
     * Validation rules for updating the user.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $roles = $this->selectedRoles;
        $isExempt = count(array_intersect(['reviewer', 'superadmin', 'admin lppm'], array_map('strtolower', $roles))) > 0;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50', Rule::unique(User::class)->ignore($this->userId)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->userId)],
            'selectedRoles' => ['nullable', 'array'],
            'selectedRoles.*' => ['string', Rule::exists('roles', 'name')],
            'emailVerified' => ['boolean'],
            'identity_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('identities', 'identity_id')->ignore($this->userId, 'user_id'),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'birthplace' => ['nullable', 'string', 'max:255'],
            'sinta_id' => ['nullable', 'string', 'max:255'],
            'scopus_id' => ['nullable', 'string', 'max:255'],
            'google_scholar_id' => ['nullable', 'string', 'max:255'],
            'wos_id' => ['nullable', 'string', 'max:255'],
            'title_prefix' => ['nullable', 'string', 'max:255'],
            'title_suffix' => ['nullable', 'string', 'max:255'],
            'scopus_h_index' => ['nullable', 'integer', 'min:0'],
            'sinta_score_v3_overall' => ['nullable', 'numeric', 'min:0'],
            'functional_position' => ['nullable', 'string', 'max:255'],
            'gs_h_index' => ['nullable', 'integer', 'min:0'],
            'wos_h_index' => ['nullable', 'integer', 'min:0'],
            'type' => [$isExempt ? 'nullable' : 'required', Rule::in('dosen', 'mahasiswa', 'reviewer')],

            // Internal vs External logic
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
            'science_cluster_id' => ['nullable', 'exists:science_clusters,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Persist the updated user information.
     */
    public function generatePassword(): void
    {
        // Generate a random 8 character string with alphanumeric and symbols
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }

        $this->password = $password;
        $this->password_confirmation = $password;

        $this->dispatch('toast-success', message: 'Password acak telah dibuat: '.$password);
        session()->flash('success', 'Password default: '.$password);
    }

    public function save(): void
    {
        $validated = $this->validate();

        DB::transaction(function () use ($validated): void {
            $user = $this->user;

            // Update user details
            $user->fill([
                'name' => $validated['name'],
                'username' => ! empty($validated['username']) ? $validated['username'] : null,
                'email' => $validated['email'],
            ]);

            // Only update password if provided
            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
                $user->original_password = $validated['password'];
            }

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            if ($validated['emailVerified']) {
                $user->email_verified_at = $user->email_verified_at ?? now();
            } else {
                $user->email_verified_at = null;
            }

            $user->save();

            // Handle hybrid institution
            $finalInstitutionId = app(HandleHybridInstitution::class)->execute($validated['institution_id']);
            $finalInstitutionName = is_numeric($validated['institution_id']) ? null : $validated['institution_id'];

            // Update or create identity
            $user->identity()->updateOrCreate(
                ['user_id' => $user->id],
                [
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
                    'sinta_score_v3_overall' => $validated['sinta_score_v3_overall'],
                    'functional_position' => $validated['functional_position'],
                    'gs_h_index' => $validated['gs_h_index'],
                    'wos_h_index' => $validated['wos_h_index'],
                    'type' => $validated['type'],
                    'institution_id' => $finalInstitutionId,
                    'institution_name' => $finalInstitutionName,
                    'faculty_id' => ! empty($validated['faculty_id']) ? $validated['faculty_id'] : null,
                    'study_program_id' => ! empty($validated['study_program_id']) ? $validated['study_program_id'] : null,
                    'science_cluster_id' => ! empty($validated['science_cluster_id']) ? (int) $validated['science_cluster_id'] : null,
                ]
            );

            // Sync multiple roles
            $user->syncRoles($validated['selectedRoles']);
        });

        $this->selectedRoles = $this->user->getRoleNames()->toArray();
        $this->emailVerified = $this->user->hasVerifiedEmail();

        $message = 'Pengguna telah diperbarui.';
        session()->flash('success', $message);
        $this->toastSuccess($message);

        $this->dispatch('user-updated');
    }

    /**
     * Render the component view.
     * All public properties and computed properties are automatically available in the view.
     */
    #[Computed]
    public function isExempt(): bool
    {
        $roles = $this->selectedRoles;

        return count(array_intersect(['reviewer', 'superadmin', 'admin lppm'], array_map('strtolower', $roles))) > 0;
    }

    public function render(): View
    {
        return view('livewire.users.edit');
    }

    /**
     * Resolve the current user model.
     */
    public function getUserProperty(): User
    {
        if (empty($this->userId)) {
            return new User;
        }

        return User::query()
            ->with(['roles', 'identity'])
            ->findOrFail($this->userId);
    }

    /**
     * Retrieve role options for the selection control.
     * Cached computed property that returns available roles.
     *
     * @return array<int, array<string, string>>
     */
    #[Computed]
    public function roleOptions(): array
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
     * Cached computed property that returns all available institutions.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function institutionOptions(): array
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
     * Reactive computed property that automatically updates when institution_id changes.
     * Returns empty array when no institution is selected.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function facultyOptions(): array
    {
        // Return empty array if no institution is selected
        if (! $this->institution_id) {
            return [];
        }

        // Fetch faculties belonging to the selected institution
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
     * Reactive computed property that automatically updates when faculty_id changes.
     * Returns empty array when no faculty is selected.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function studyProgramOptions(): array
    {
        // Return empty array if no faculty is selected
        if (! $this->faculty_id) {
            return [];
        }

        // Fetch study programs belonging to the selected faculty
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
     * Retrieve science cluster options for the selection control.
     */
    #[Computed]
    public function scienceClusterOptions(): array
    {
        return ScienceCluster::where('level', 1)
            ->orderBy('name')
            ->get()
            ->map(fn (ScienceCluster $cluster) => [
                'value' => $cluster->id,
                'label' => $cluster->name,
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
