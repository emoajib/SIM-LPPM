<div>
    <x-slot:title>Import Pengguna</x-slot:title>
    <x-slot:pageTitle>Import Pengguna</x-slot:pageTitle>
    <x-slot:pageSubtitle>Import data pengguna dari file Excel.</x-slot:pageSubtitle>
    <x-slot:pageActions>
        <a href="{{ route('users.index') }}" class="btn btn-secondary" wire:navigate.hover>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                <path d="M9 11l-4 4l4 4m-4 -4h11a4 4 0 0 0 0 -8h-1"></path>
            </svg>
            {{ __('Kembali') }}
        </a>
    </x-slot:pageActions>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">{{ __('File Excel') }}</label>
                        <input type="file" wire:model="file" class="form-control" accept=".xlsx, .xls">
                        <div wire:loading wire:target="file" class="text-muted mt-2">
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                            {{ __('Memproses file...') }}
                        </div>
                        <div class="form-text">
                            {{ __('Format yang didukung: .xlsx, .xls. Pastikan format kolom sesuai template.') }}
                        </div>
                        @error('file')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <a href="{{ route('users.import-template') }}" class="btn btn-outline-primary"
                        data-navigate-ignore="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon">
                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                            <path d="M7 11l5 5l5 -5"></path>
                            <path d="M12 4l0 12"></path>
                        </svg>
                        {{ __('Download Template') }}
                    </a>
                </div>
            </div>

            @if ($isPreviewing)
                <div class="mt-4">
                    <h3 class="card-title">{{ __('Preview Data') }}</h3>
                    <div class="table-responsive">
                        <table class="table-vcenter card-table table">
                            <thead>
                                <tr>
                                    <th>{{ __('No') }}</th>
                                    <th>{{ __('Nama') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('NIDN/NUPTK/NIM') }}</th>
                                    <th>{{ __('Tipe') }}</th>
                                    <th>{{ __('Fakutas') }}</th>
                                    <th>{{ __('Prodi') }}</th>
                                    <th>{{ __('Status') }}</th>

                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($parsedData as $index => $row)
                                    <tr class="{{ isset($validationErrors[$index + 2]) ? 'bg-red-lt' : '' }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row['name'] }}</td>
                                        <td>{{ $row['email'] }}</td>
                                        <td>{{ $row['nidn'] }}</td>
                                        <td>
                                            <span class="badge bg-{{ $row['type'] === 'dosen' ? 'blue' : 'green' }}">
                                                {{ ucfirst($row['type']) }}
                                            </span>
                                        </td>
                                        <td>{{ $row['inst'] ?? '-' }}</td>
                                        <td>{{ $row['prodi'] ?? '-' }}</td>
                                        <td>
                                            @if (isset($validationErrors[$index + 2]))
                                                <ul class="list-unstyled text-danger mb-0">
                                                    @foreach ($validationErrors[$index + 2] as $error)
                                                        <li><small>• {{ $error }}</small></li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-success">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" class="icon">
                                                        <path d="M5 12l5 5l10 -10"></path>
                                                    </svg>
                                                    {{ __('Valid') }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted py-3 text-center">
                                            {{ __('Tidak ada data yang ditemukan dalam file.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button wire:click="import" class="btn btn-primary" @if (!empty($validationErrors) || empty($parsedData)) disabled @endif wire:loading.attr="disabled" wire:target="import">
                            <span wire:loading wire:target="import" class="spinner-border spinner-border-sm me-2"
                                role="status"></span>
                            <svg wire:loading.remove wire:target="import" xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="icon">
                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                                <path d="M7 9l5 -5l5 5"></path>
                                <path d="M12 4l0 12"></path>
                            </svg>
                            {{ __('Import Data') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <x-tabler.alert />
</div>