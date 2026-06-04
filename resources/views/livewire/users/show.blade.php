<div x-data="{}" @scroll-to-top.window="window.scrollTo({ top: 0, behavior: 'smooth' })">
    <x-slot:title>Detail Pengguna</x-slot:title>
    <x-slot:pageTitle>Detail Pengguna</x-slot:pageTitle>
    <x-slot:pageSubtitle>Lihat profil lengkap pengguna dan metadata.</x-slot:pageSubtitle>
    <x-slot:pageActions>
        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary" wire:navigate.hover>
            Ubah pengguna
        </a>
    </x-slot:pageActions>

    <div class="row row-cards">
        <div class="col-md-5">
            <div class="card">
                <div class="text-center card-body">
                    <span class="mb-3 avatar avatar-xl" style="background-image: url('{{ $user->profile_picture }}')">
                        @if (!$user->getFirstMedia('avatar') && !$user->identity?->profile_picture)
                            {{ $user->initials() }}
                        @endif
                    </span>
                    <h2 class="mb-1">{{ $user->name }}</h2>
                    <p class="text-secondary">{{ $user->email }}</p>
                    <div class="my-3">
                        @if ($user->roles->isNotEmpty())
                            @foreach ($user->roles as $role)
                                <x-tabler.badge color="primary" size="lg">{{ str($role->name)->title() }}</x-tabler.badge>
                            @endforeach
                        @else
                            <span class="text-secondary">Tidak ada peran</span>
                        @endif
                    </div>
                    <div class="mt-3">
                        @if ($user->hasVerifiedEmail())
                            <x-tabler.badge color="green">Email terverifikasi</x-tabler.badge>
                        @else
                            <x-tabler.badge color="yellow">Verifikasi menunggu</x-tabler.badge>
                        @endif
                    </div>
                </div>
            </div>
            <div class="mt-3 card">
                <div class="card-body">
                    <h3 class="card-title">Detail profil</h3>
                    <dl class="row row-cards">
                        <div class="col-sm-6">
                            <dt class="text-secondary">Nama</dt>
                            <dd class="fw-medium">{{ $user->name ?? '—' }}</dd>
                        </div>
                        <div class="col-sm-6">
                            <dt class="text-secondary">Alamat email</dt>
                            <dd class="fw-medium">{{ $user->email ?? '—' }}</dd>
                        </div>
                        <div class="col-sm-6">
                            <dt class="text-secondary">Bergabung platform</dt>
                            <dd class="fw-medium">{{ optional($user->created_at)->translatedFormat('d F Y') ?? '—' }}
                            </dd>
                        </div>
                        <div class="col-12">
                            <dt class="text-secondary">Peran</dt>
                            <dd class="fw-medium">
                                @if ($user->roles->isNotEmpty())
                                    @foreach ($user->roles as $role)
                                        <x-tabler.badge color="primary"
                                            size="lg">{{ str($role->name)->title() }}</x-tabler.badge>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Identitas</h3>
                </div>
                <div class="card-body">
                    <dl class="row row-cards">
                        <div class="col-sm-6">
                            <dt class="text-secondary">ID Identitas (NIDN/NIK/NIM/NIP)</dt>
                            <dd class="fw-medium">{{ $user->identity?->identity_id ?? '—' }}</dd>
                        </div>
                        <div class="col-sm-6">
                            <dt class="text-secondary">Tipe Identitas</dt>
                            <dd class="fw-medium">
                                @if ($user->identity?->type)
                                    <span class="text-capitalize">{{ str($user->identity?->type)->title() }}</span>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="col-sm-6">
                            <dt class="text-secondary">Tempat Lahir</dt>
                            <dd class="fw-medium">{{ $user->identity?->birthplace ?? '—' }}</dd>
                        </div>
                        <div class="col-sm-6">
                            <dt class="text-secondary">Tanggal Lahir</dt>
                            <dd class="fw-medium">
                                {{ $user->identity?->birthdate ? \Illuminate\Support\Carbon::parse($user->identity->birthdate)->translatedFormat('d F Y') : '—' }}
                            </dd>
                        </div>
                        <div class="col-12">
                            <dt class="text-secondary">Institusi</dt>
                            <dd class="fw-medium">{{ $user->identity?->institution?->name ?? '—' }}</dd>
                        </div>
                        <div class="col-12">
                            <dt class="text-secondary">Fakultas</dt>
                            <dd class="fw-medium">{{ $user->identity?->faculty?->name ?? '—' }} /
                                {{ $user->identity?->faculty?->code ?? '—' }}
                            </dd>
                        </div>
                        <div class="col-12">
                            <dt class="text-secondary">Program Studi</dt>
                            <dd class="fw-medium">{{ $user->identity?->studyProgram?->name ?? '—' }}</dd>
                        </div>
                        <div class="col-12">
                            <dt class="text-secondary">Alamat</dt>
                            <dd class="fw-medium">{{ $user->identity?->address ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="mt-3 card">
                <div class="card-header">
                    <h3 class="card-title">Profil Akademik & SINTA</h3>
                </div>
                <div class="card-body">
                    <div class="row row-cards">
                        <div class="col-md-6">
                            <h4 class="mb-3">Informasi Akademik</h4>
                            <dl class="row row-cards">
                                <div class="col-12">
                                    <dt class="text-secondary">Pendidikan Terakhir</dt>
                                    <dd class="fw-medium">{{ $user->identity?->last_education ?? '—' }}</dd>
                                </div>
                                <div class="col-12">
                                    <dt class="text-secondary">Jabatan Fungsional</dt>
                                    <dd class="fw-medium">{{ $user->identity?->functional_position ?? '—' }}</dd>
                                </div>
                                <div class="col-12">
                                    <dt class="text-secondary">Gelar Akademik</dt>
                                    <dd class="fw-medium">
                                        {{ $user->identity?->title_prefix }} {{ $user->name }}
                                        {{ $user->identity?->title_suffix }}
                                    </dd>
                                </div>
                                <div class="col-12">
                                    <dt class="text-secondary">ID SINTA</dt>
                                    <dd class="fw-medium">
                                        @if ($user->identity?->sinta_id)
                                            <a href="https://sinta.kemdiktisaintek.go.id/authors/profile/{{ $user->identity?->sinta_id }}"
                                                target="_blank" rel="noopener noreferrer" class="d-flex align-items-center">
                                                <img src="https://sinta.kemdikbud.go.id/assets/img/logo_sinta.png"
                                                    alt="SINTA" width="20" class="me-2">
                                                {{ $user->identity?->sinta_id }}
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="ms-1 icon icon-tabler icon-tabler-external-link" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path
                                                        d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" />
                                                    <path d="M11 13l9 -9" />
                                                    <path d="M15 4h5v5" />
                                                </svg>
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h4 class="mb-3">SINTA Score</h4>
                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">SINTA Score Overall</div>
                                    <div class="datagrid-content">
                                        {{ number_format($user->identity?->sinta_score_v3_overall ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">SINTA Score 3Yr</div>
                                    <div class="datagrid-content">
                                        {{ number_format($user->identity?->sinta_score_v3_3yr ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Affiliation Score Overall</div>
                                    <div class="datagrid-content">
                                        {{ number_format($user->identity?->affil_score_v3_overall ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Affiliation Score 3Yr</div>
                                    <div class="datagrid-content">
                                        {{ number_format($user->identity?->affil_score_v3_3yr ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hr-text">Indeksasi</div>

                    <div class="row row-cards">
                        <div class="col-md-4">
                            <div class="card card-sm border-secondary">
                                <div class="card-header bg-secondary-lt">
                                    <h4 class="card-title m-0">Scopus</h4>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <div class="col-6 mb-2">
                                            <dt class="text-secondary small">Dokumen</dt>
                                            <dd class="fw-bold">{{ $user->identity?->scopus_documents ?? 0 }}</dd>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <dt class="text-secondary small">Sitasi</dt>
                                            <dd class="fw-bold">{{ $user->identity?->scopus_citations ?? 0 }}</dd>
                                        </div>
                                        <div class="col-6">
                                            <dt class="text-secondary small">H-Index</dt>
                                            <dd class="fw-bold">{{ $user->identity?->scopus_h_index ?? '-' }}</dd>
                                        </div>
                                        <div class="col-6">
                                            <dt class="text-secondary small">i10-Index</dt>
                                            <dd class="fw-bold">{{ $user->identity?->scopus_i10_index ?? 0 }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-sm border-google">
                                <div class="card-header bg-google-lt">
                                    <h4 class="card-title m-0">Google Scholar</h4>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <div class="col-6 mb-2">
                                            <dt class="text-secondary small">Dokumen</dt>
                                            <dd class="fw-bold">{{ $user->identity?->gs_documents ?? 0 }}</dd>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <dt class="text-secondary small">Sitasi</dt>
                                            <dd class="fw-bold">{{ $user->identity?->gs_citations ?? 0 }}</dd>
                                        </div>
                                        <div class="col-6">
                                            <dt class="text-secondary small">H-Index</dt>
                                            <dd class="fw-bold">{{ $user->identity?->gs_h_index ?? '-' }}</dd>
                                        </div>
                                        <div class="col-6">
                                            <dt class="text-secondary small">i10-Index</dt>
                                            <dd class="fw-bold">{{ $user->identity?->gs_i10_index ?? 0 }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-sm border-info">
                                <div class="card-header bg-info-lt">
                                    <h4 class="card-title m-0">Web of Science</h4>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <div class="col-6 mb-2">
                                            <dt class="text-secondary small">Dokumen</dt>
                                            <dd class="fw-bold">{{ $user->identity?->wos_documents ?? 0 }}</dd>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <dt class="text-secondary small">Sitasi</dt>
                                            <dd class="fw-bold">{{ $user->identity?->wos_citations ?? 0 }}</dd>
                                        </div>
                                        <div class="col-6">
                                            <dt class="text-secondary small">H-Index</dt>
                                            <dd class="fw-bold">{{ $user->identity?->wos_h_index ?? '-' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 card">
                <div class="card-body">
                    <h3 class="mb-3 card-title">Ringkasan keamanan</h3>
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-secondary">Autentikasi dua faktor</span>
                            @if ($user->two_factor_secret)
                                <x-tabler.badge color="green">Diaktifkan</x-tabler.badge>
                            @else
                                <x-tabler.badge color="secondary">Dinonaktifkan</x-tabler.badge>
                            @endif
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-secondary">Terakhir diperbarui</span>
                            <span class="fw-medium">{{ optional($user->updated_at)->diffForHumans() ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">


            <div class="mt-3 card">
                <div class="card-header">
                    <h3 class="card-title">Riwayat Penelitian</h3>
                </div>
                <div class="table-responsive">
                    <table class="card-table table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Tahun</th>
                                <th>Peran</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->researches as $research)
                                <tr>
                                    <td class="text-wrap">
                                        <div class="fw-medium">
                                            <a href="{{ route('research.proposal.show', $research) }}" class="text-reset"
                                                wire:navigate.hover>
                                                {{ $research->title }}
                                            </a>
                                        </div>
                                        <div class="text-secondary small">{{ $research->researchScheme?->name }}</div>
                                    </td>
                                    <td>{{ $research->start_year }}</td>
                                    <td>
                                        @php
                                            $roleName = str($research->user_role)->title();
                                            $badgeColor = $roleName == 'Ketua' ? 'bg-blue-lt' : 'bg-orange-lt';
                                        @endphp
                                        <span class="badge {{ $badgeColor }}">
                                            {{ $roleName }}
                                        </span>
                                    </td>
                                    <td>
                                        <x-tabler.badge :color="$research->status->color()">
                                            {{ $research->status->label() }}
                                        </x-tabler.badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted text-center">Tidak ada riwayat penelitian</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($this->researches->hasPages())
                    <div class="d-flex align-items-center card-footer">
                        {{ $this->researches->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif
            </div>

            <div class="mt-3 card">
                <div class="card-header">
                    <h3 class="card-title">Riwayat Pengabdian Masyarakat</h3>
                </div>
                <div class="table-responsive">
                    <table class="card-table table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Tahun</th>
                                <th>Peran</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->communityServices as $pkm)
                                <tr>
                                    <td class="text-wrap">
                                        <div class="fw-medium">
                                            <a href="{{ route('community-service.proposal.show', $pkm) }}"
                                                class="text-reset" wire:navigate.hover>
                                                {{ $pkm->title }}
                                            </a>
                                        </div>
                                        <div class="text-secondary small">{{ $pkm->researchScheme?->name }}</div>
                                    </td>
                                    <td>{{ $pkm->start_year }}</td>
                                    <td>
                                        @php
                                            $roleName = str($pkm->user_role)->title();
                                            $badgeColor = $roleName == 'Ketua' ? 'bg-blue-lt' : 'bg-orange-lt';
                                        @endphp
                                        <span class="badge {{ $badgeColor }}">
                                            {{ $roleName }}
                                        </span>
                                    </td>
                                    <td>
                                        <x-tabler.badge :color="$pkm->status->color()">
                                            {{ $pkm->status->label() }}
                                        </x-tabler.badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted text-center">Tidak ada riwayat pengabdian
                                        masyarakat</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($this->communityServices->hasPages())
                    <div class="d-flex align-items-center card-footer">
                        {{ $this->communityServices->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif
            </div>

            <div class="mt-3">
                <livewire:users.activity-log-list :user="$user" />
            </div>
        </div>
    </div>
</div>