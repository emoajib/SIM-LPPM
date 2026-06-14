<?php

namespace App\Livewire\Users;

use App\Imports\UsersImport;
use App\Livewire\Concerns\HasToast;
use App\Models\Identity;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

#[Layout('components.layouts.app', ['title' => 'Import Users', 'pageTitle' => 'Import Pengguna', 'pageSubtitle' => 'Import data pengguna dari file Excel'])]
class Import extends Component
{
    use HasToast, WithFileUploads;

    #[Validate('required|file|mimes:xlsx,xls')]
    public $file;

    public array $parsedData = [];

    public array $validationErrors = [];

    public bool $isPreviewing = false;

    public function updatedFile()
    {
        $this->validate();
        $this->parseFile();
    }

    public function parseFile()
    {
        $this->reset(['parsedData', 'validationErrors', 'isPreviewing']);

        try {
            $import = new UsersImport;
            // Parse to array for preview
            $rows = Excel::toArray($import, $this->file)[0];

            $this->parsedData = $rows;

            // Validate each row for preview
            // Note: Maatwebsite's validation happens during import, so we manually validate for preview
            // or we could try a dry run. For simplicity, we'll reuse the rules from the import class.

            $rules = $import->rules();
            $messages = $import->customValidationMessages();

            foreach ($this->parsedData as $index => $data) {
                // Map keys to match rules if necessary, or ensure Excel header matches rule keys
                // Assuming Excel headers match rule keys (name, email, etc.)

                $validator = Validator::make($data, $rules, $messages);

                if ($validator->fails()) {
                    $this->validationErrors[$index + 2] = $validator->errors()->all();
                }
            }

            $this->isPreviewing = true;
        } catch (\Exception $e) {
            $this->addError('file', 'Gagal memproses file: '.$e->getMessage());
        }
    }

    protected function validateRow($data, $rowIndex)
    {
        // This method is no longer used as validation is handled by UserImporter
        // Keeping it for now, but it could be removed if not referenced elsewhere.
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Nama wajib diisi.';
        }
        if (empty($data['email'])) {
            $errors[] = 'Email wajib diisi.';
        } elseif (User::where('email', $data['email'])->exists()) {
            $errors[] = 'Email sudah terdaftar.';
        }

        if (empty($data['password'])) {
            $errors[] = 'Password wajib diisi.';
        }

        if (empty($data['identity_id'])) {
            $errors[] = 'NIDN/NUPTK/NIM wajib diisi.';
        } elseif (Identity::where('identity_id', $data['identity_id'])->exists()) {
            $errors[] = 'NIDN/NUPTK/NIM sudah terdaftar.';
        }

        if (! in_array($data['type'], ['dosen', 'mahasiswa'])) {
            $errors[] = "Tipe harus 'dosen' atau 'mahasiswa'.";
        }

        if (! empty($errors)) {
            $this->validationErrors[$rowIndex] = $errors;
        }
    }

    public function import()
    {
        if (! empty($this->validationErrors)) {
            $this->addError('error', 'Harap perbaiki kesalahan validasi sebelum mengimpor.');

            return;
        }

        try {
            Excel::import(new UsersImport, $this->file);

            $message = 'Data pengguna berhasil diimpor.';
            session()->flash('success', $message);
            $this->toastSuccess($message);

            $this->redirect(route('users.index'), navigate: true);
        } catch (ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                $this->validationErrors[$failure->row()] = $failure->errors();
            }
            $message = 'Terdapat kesalahan validasi.';
            $this->addError('error', $message);
            $this->toastError($message);
        } catch (\Exception $e) {
            $message = 'Terjadi kesalahan saat menyimpan: '.$e->getMessage();
            $this->addError('error', $message);
            $this->toastError($message);
        }
    }

    public function downloadTemplate(): void
    {
        $this->dispatch('download-file', url: route('users.import-template'));
    }

    public function render()
    {
        return view('livewire.users.import');
    }
}
