<div>
    <x-slot:title>Daftar Pengguna</x-slot:title>
    <x-slot:pageTitle>Daftar Pengguna</x-slot:pageTitle>
    <x-slot:pageSubtitle>Kelola pengguna dalam sistem.</x-slot:pageSubtitle>
    <x-slot:pageActions>
        <a href="{{ route('users.sync-sinta') }}" class="btn btn-outline-primary me-2" wire:navigate.hover>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
            </svg>
            {{ __('Sync SINTA') }}
        </a>
        <a href="{{ route('users.import') }}" class="btn btn-success me-2" wire:navigate.hover>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                <path d="M7 9l5 -5l5 5"></path>
                <path d="M12 4l0 12"></path>
            </svg>
            {{ __('Import Excel') }}
        </a>
        <a href="{{ route('users.create') }}" class="btn btn-primary me-2" wire:navigate.hover>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                <path d="M12 5v14"></path>
                <path d="M5 12h14"></path>
            </svg>
            {{ __('Tambah Pengguna') }}
        </a>
        @if(auth()->user()->hasAnyRole(['superadmin', 'admin lppm']))
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reset-password-modal">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-key" width="24" height="24"
                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path
                        d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z">
                    </path>
                    <path d="M15 9h.01"></path>
                </svg>
                {{ __('Reset Password Massal') }}
            </button>
        @endif
    </x-slot:pageActions>

    <x-tabler.alert />

    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users"
                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
                                    <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ $userCounts['total'] ?? 0 }} {{ __('Total Pengguna') }}
                            </div>
                            <div class="text-secondary">
                                {{ __('Semua akun terdaftar') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @foreach(['dosen', 'reviewer', 'dekan', 'kepala lppm', 'rektor', 'admin lppm'] as $r)
            @if(isset($userCounts[$r]))
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-azure-lt text-azure avatar">
                                        @if($r === 'dosen')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-school"
                                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M22 9l-10 -4l-10 4l10 4l10 -4v6"></path>
                                                <path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4"></path>
                                            </svg>
                                        @elseif($r === 'reviewer')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-check"
                                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M8 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
                                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4"></path>
                                                <path d="M15 19l2 2l4 -4"></path>
                                            </svg>
                                        @elseif($r === 'dekan')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-award"
                                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M12 9m-6 0a6 6 0 1 0 12 0a6 6 0 1 0 -12 0"></path>
                                                <path d="M12 15l3.4 5.89l1.59 -3.23l3.51 .05l-2.5 -4.71"></path>
                                                <path d="M12 15l-3.4 5.89l-1.59 -3.23l-3.51 .05l2.5 -4.71"></path>
                                            </svg>
                                        @elseif($r === 'kepala lppm')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-briefcase"
                                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path
                                                    d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z">
                                                </path>
                                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                <path d="M12 12l0 .01"></path>
                                                <path d="M3 13a20 20 0 0 0 18 0"></path>
                                            </svg>
                                        @elseif($r === 'rektor')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-crown"
                                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M12 6l4 6l5 -4l-2 10h-14l-2 -10l5 4z"></path>
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-settings"
                                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path
                                                    d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37a1.724 1.724 0 0 0 2.572 -1.065z">
                                                </path>
                                                <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                                            </svg>
                                        @endif
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">
                                        {{ $userCounts[$r] }} {{ str($r)->title() }}
                                    </div>
                                    <div class="text-secondary">
                                        {{ __('Aktif dalam sistem') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="card card-stacked">
        <div class="border-bottom card-body">
            {{-- Row 1: Search + Role + IdentityType + Status + Sort --}}
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="user-search" class="form-label">{{ __('Cari') }}</label>
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M10 17a7 7 0 1 0 0 -14 7 7 0 0 0 0 14z"></path>
                                <path d="M21 21l-6 -6"></path>
                            </svg>
                        </span>
                        <input id="user-search" type="search" class="form-control"
                            wire:model.live.debounce.400ms="search"
                            placeholder="{{ __('Cari nama, email, atau ID...') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <label for="role-filter" class="form-label">{{ __('Peran') }}</label>
                    <select id="role-filter" class="form-select" wire:model.live="role">
                        @foreach ($roleOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="identity-type-filter" class="form-label">{{ __('Tipe') }}</label>
                    <select id="identity-type-filter" class="form-select" wire:model.live="identityType">
                        @foreach ($identityTypeOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="status-filter" class="form-label">{{ __('Status') }}</label>
                    <select id="status-filter" class="form-select" wire:model.live="status">
                        @foreach ($statusOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="sort-filter" class="form-label">{{ __('Urutkan') }}</label>
                    <select id="sort-filter" class="form-select" wire:model.live="sort">
                        <option value="latest">{{ __('Terbaru (Z-A)') }}</option>
                        <option value="oldest">{{ __('Terlama (A-Z)') }}</option>
                        <option value="name_asc">{{ __('Nama (A-Z)') }}</option>
                        <option value="name_desc">{{ __('Nama (Z-A)') }}</option>
                    </select>
                </div>
            </div>

            {{-- Row 2: Institution + Faculty + Prodi (dependent dropdowns) --}}
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="institution-filter" class="form-label">{{ __('Institusi') }}</label>
                    <select id="institution-filter" class="form-select" wire:model.live="institution">
                        @foreach ($institutionOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="faculty-filter" class="form-label">{{ __('Fakultas') }}</label>
                    <select id="faculty-filter" class="form-select" wire:model.live="faculty"
                        @disabled(empty($facultyOptions))>
                        @if(empty($facultyOptions))
                            <option value="all">{{ __('Pilih institusi dulu...') }}</option>
                        @endif
                        @foreach ($facultyOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="prodi-filter" class="form-label">{{ __('Program Studi') }}</label>
                    <select id="prodi-filter" class="form-select" wire:model.live="prodi"
                        @disabled(empty($prodiOptions))>
                        @if(empty($prodiOptions))
                            <option value="all">{{ __('Pilih fakultas dulu...') }}</option>
                        @endif
                        @foreach ($prodiOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Reset Button --}}
            @if($institution !== 'all' || $faculty !== 'all' || $prodi !== 'all' || $identityType !== 'all' || $search !== '' || $role !== 'all' || $status !== 'all' || $sort !== 'latest')
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetAllFilters">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline me-1" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path>
                            <path d="M12 8l0 4"></path>
                            <path d="M12 16l.01 0"></path>
                        </svg>
                        {{ __('Reset Semua Filter') }}
                    </button>
                </div>
            @endif
        </div>

        <div class="table-responsive">
            <table class="card-table table-vcenter table">
                <thead>
                    <tr>
                        <th>{{ __('Nama') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Peran') }}</th>
                        <th class="text-center">{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Terakhir Aktif') }}</th>
                        <th class="text-end">{{ __('Bergabung') }}</th>
                        <th class="text-end text-nowrap" style="width: 1%;">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="fw-semibold">
                                <div>{{ $user->name }}</div>
                                <div class="text-secondary text-truncate">{{ $user->identity?->identity_id }}</div>
                                @if($user->identity?->institution)
                                    <div class="text-muted small mt-1 text-truncate" style="max-width: 250px;">
                                        {{ $user->identity?->institution?->name }}
                                        @if($user->identity?->faculty)
                                            / {{ $user->identity?->faculty?->name }}
                                        @endif
                                        @if($user->identity?->studyProgram)
                                            / {{ $user->identity?->studyProgram?->name }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <div class="btn-list">
                                    @forelse ($user->roles as $role)
                                        <x-tabler.badge color="primary">
                                            {{ str($role->name)->title() }}
                                        </x-tabler.badge>
                                    @empty
                                        <span class="text-secondary">{{ __('Tidak ada peran') }}</span>
                                    @endforelse
                                </div>
                                @if($user->identity?->faculty)
                                    <div class="text-muted small mt-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline me-1" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M3 21l18 0"></path>
                                            <path d="M9 8l1 0"></path>
                                            <path d="M9 12l1 0"></path>
                                            <path d="M9 16l1 0"></path>
                                            <path d="M14 8l1 0"></path>
                                            <path d="M14 12l1 0"></path>
                                            <path d="M14 16l1 0"></path>
                                            <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"></path>
                                        </svg>
                                        {{ $user->identity?->faculty?->name ?? '-' }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($user->hasVerifiedEmail())
                                    <x-tabler.badge color="green">{{ __('Terverifikasi') }}</x-tabler.badge>
                                @else
                                    <x-tabler.badge color="yellow">{{ __('Menunggu') }}</x-tabler.badge>
                                @endif
                            </td>
                            <td class="text-secondary text-end">
                                @if($user->last_active_at)
                                    <span title="{{ $user->last_active_at->translatedFormat('d M Y H:i') }}">
                                        {{ $user->last_active_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-muted small italic">-</span>
                                @endif
                            </td>
                            <td class="text-secondary text-end">
                                {{ optional($user->created_at)->translatedFormat('d M Y') }}
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle align-text-top"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ __('Tindakan') }}
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <a class="dropdown-item" href="{{ route('users.show', $user) }}" wire:navigate>
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="icon icon-tabler icon-tabler-eye me-2 text-info" width="24"
                                                height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
                                                <path
                                                    d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6">
                                                </path>
                                            </svg>
                                            {{ __('Lihat Profil') }}
                                        </a>
                                        <a class="dropdown-item" href="{{ route('users.edit', $user) }}" wire:navigate>
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="icon icon-tabler icon-tabler-edit me-2 text-primary" width="24"
                                                height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M9 7h-3a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-3"></path>
                                                <path d="M9 15h3l8.5 -8.5a1.5 1.5 0 0 0 -3 -3l-8.5 8.5v3"></path>
                                                <line x1="16" y1="5" x2="19" y2="8"></line>
                                            </svg>
                                            {{ __('Ubah Data') }}
                                        </a>

                                        @if(auth()->user()->hasAnyRole(['superadmin', 'admin lppm']))
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item" type="button"
                                                wire:click.prevent="resetUserPassword('{{ $user->id }}')"
                                                wire:confirm="{{ __("Apakah Anda yakin ingin mereset password {$user->name} menjadi 'password'?") }}">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-key me-2 text-warning" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path
                                                        d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z">
                                                    </path>
                                                    <path d="M15 9h.01"></path>
                                                </svg>
                                                {{ __('Reset Password') }}
                                            </button>
                                            <button class="dropdown-item" type="button"
                                                wire:click.prevent="delete('{{ $user->id }}')"
                                                wire:confirm="{{ __('Apakah Anda yakin ingin menghapus pengguna ini?') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-trash me-2 text-danger" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M4 7l16 0"></path>
                                                    <path d="M10 11l0 6"></path>
                                                    <path d="M14 11l0 6"></path>
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                </svg>
                                                <span class="text-danger">{{ __('Hapus Pengguna') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"
                                class="text-secondary py-5 text-center">
                                {{ __('Tidak ada pengguna yang cocok dengan filter Anda.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex align-items-center justify-content-between card-footer">
            <div class="text-secondary">
                {{ trans_choice('{0}Tidak ada data|{1}Menampilkan :count pengguna|[2,*]Menampilkan :count pengguna', $users->count(), ['count' => $users->count()]) }}
            </div>
            <div>
                {{ $users->links() }}
            </div>
        </div>
    </div>

    {{-- Reset Password Modal --}}
    @if(auth()->user()->hasAnyRole(['superadmin', 'admin lppm']))
        @teleport('body')
        <div class="modal modal-blur fade" id="reset-password-modal" tabindex="-1" role="dialog" aria-hidden="true"
            wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reset Password Massal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-3">
                            <x-lucide-alert-triangle class="icon me-2" />
                            Tindakan ini akan mereset password <strong>SEMUA</strong> pengguna dengan role yang dipilih.
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Pilih Role Target</label>
                            <select class="form-select @error('massResetRole') is-invalid @enderror"
                                wire:model="massResetRole">
                                <option value="">-- Pilih Role --</option>
                                @foreach($roleOptions as $r)
                                    @if($r['value'] !== 'all' && $r['value'] !== 'superadmin')
                                        <option value="{{ $r['value'] }}">{{ $r['label'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('massResetRole') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Password Baru Standar</label>
                            <div class="input-group input-group-flat">
                                <input type="text" class="form-control @error('massResetPassword') is-invalid @enderror"
                                    wire:model="massResetPassword" placeholder="Masukkan password baru">
                                <span class="input-group-text">
                                    <button type="button" class="btn btn-link link-secondary p-0"
                                        @click="$wire.set('massResetPassword', Math.random().toString(36).slice(-8))"
                                        title="Generate Random Password">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-wand"
                                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M6 21l15 -15l-3 -3l-15 15l3 3"></path>
                                            <path d="M15 6l3 3"></path>
                                            <path d="M9 3a2 2 0 0 0 2 2a2 2 0 0 0 -2 2a2 2 0 0 0 -2 -2a2 2 0 0 0 2 -2">
                                            </path>
                                            <path d="M19 13a2 2 0 0 0 2 2a2 2 0 0 0 -2 2a2 2 0 0 0 -2 -2a2 2 0 0 0 2 -2">
                                            </path>
                                        </svg>
                                    </button>
                                </span>
                                @error('massResetPassword') <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-hint">Password ini akan diterapkan ke semua user dalam role tersebut.</small>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-ghost-secondary me-2"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-danger" wire:click="resetPasswordsForRole"
                                wire:loading.attr="disabled"
                                wire:confirm="Apakah Anda yakin ingin mereset password untuk SEMUA pengguna dengan role tersebut?">
                                <span wire:loading.remove wire:target="resetPasswordsForRole">Reset Password</span>
                                <span wire:loading wire:target="resetPasswordsForRole">Memproses...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- Delete User Modal --}}
    @if(auth()->user()->hasAnyRole(['superadmin', 'admin lppm']))
        @teleport('body')
        <div class="modal modal-blur fade" id="delete-user-modal" tabindex="-1" role="dialog" aria-hidden="true"
            wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Hapus Pengguna</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <x-lucide-alert-triangle class="icon me-2" />
                            Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak dapat diurungkan.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-danger" wire:click="deleteUser" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="deleteUser">Hapus</span>
                            <span wire:loading wire:target="deleteUser">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>

@push('scripts')
    <script>
            document.addEventListener('livewire:initialized', function () {
                // Livewire 3 handled scripts here if needed
            });
    </script>
@endpush