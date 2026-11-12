<x-slot:title>Pengaturan</x-slot:title>
<x-slot:pageTitle>{{ $pageTitle }}</x-slot:pageTitle>
<x-slot:pageSubtitle>Kelola data master sistem kelompok {{ strtolower($pageTitle) }}</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />
    <div class="card">
        <div class="row g-0">
            <div class="border-end col-12 col-md-3">
                <div class="card-body">
                    @if ($group === 'academic-content')
                        <h4 class="subheader d-flex align-items-center gap-2"><i class="ti ti-folder" aria-hidden="true"></i>Konten Penelitian &amp; Akademik</h4>
                        <div class="list-group list-group-transparent">
                            <button wire:click="setActiveTab('focus-areas')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'focus-areas' ? 'active' : '' }}">
                                <i class="ti ti-target-arrow" aria-hidden="true"></i>
                                Area Fokus
                            </button>
                            <button wire:click="setActiveTab('keywords')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'keywords' ? 'active' : '' }}">
                                <i class="ti ti-tag" aria-hidden="true"></i>
                                Kata Kunci
                            </button>
                            <button wire:click="setActiveTab('themes')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'themes' ? 'active' : '' }}">
                                <i class="ti ti-palette" aria-hidden="true"></i>
                                Tema
                            </button>
                            <button wire:click="setActiveTab('topics')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'topics' ? 'active' : '' }}">
                                <i class="ti ti-list-details" aria-hidden="true"></i>
                                Topik
                            </button>
                            <button wire:click="setActiveTab('research-schemes')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'research-schemes' ? 'active' : '' }}">
                                <i class="ti ti-file-description" aria-hidden="true"></i>
                                Skema Penelitian
                            </button>
                            <button wire:click="setActiveTab('community-service-schemes')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'community-service-schemes' ? 'active' : '' }}">
                                <i class="ti ti-heart-handshake" aria-hidden="true"></i>
                                Skema Pengabdian
                            </button>
                            <button wire:click="setActiveTab('science-clusters')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'science-clusters' ? 'active' : '' }}">
                                <i class="ti ti-sitemap" aria-hidden="true"></i>
                                Klaster Sains
                            </button>
                            <button wire:click="setActiveTab('tkt')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'tkt' ? 'active' : '' }}">
                                <i class="ti ti-versions" aria-hidden="true"></i>
                                TKT
                            </button>
                            <button wire:click="setActiveTab('review-criteria')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'review-criteria' ? 'active' : '' }}">
                                <i class="ti ti-clipboard-check" aria-hidden="true"></i>
                                Kriteria Penilaian
                            </button>
                            <button wire:click="setActiveTab('iku-output-types')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'iku-output-types' ? 'active' : '' }}">
                                <i class="ti ti-award" aria-hidden="true"></i>
                                Jenis Luaran IKU
                            </button>
                            <button wire:click="setActiveTab('master-iku')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'master-iku' ? 'active' : '' }}">
                                <i class="ti ti-chart-bar" aria-hidden="true"></i>
                                Master IKU
                            </button>
                            <button wire:click="setActiveTab('sdgs')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'sdgs' ? 'active' : '' }}">
                                <i class="ti ti-world" aria-hidden="true"></i>
                                SDG
                            </button>
                            <button wire:click="setActiveTab('strata')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'strata' ? 'active' : '' }}">
                                <i class="ti ti-stack-2" aria-hidden="true"></i>
                                Strata
                            </button>
                            <button wire:click="setActiveTab('quota-messages')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'quota-messages' ? 'active' : '' }}">
                                <i class="ti ti-message-2" aria-hidden="true"></i>
                                Pesan Kuota
                            </button>
                        </div>
                        <h4 class="subheader d-flex align-items-center gap-2 mt-4"><i class="ti ti-lock-access" aria-hidden="true"></i>Manajemen Akses</h4>
                        <div class="list-group list-group-transparent">
                            <button wire:click="setActiveTab('rbac-matrix')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'rbac-matrix' ? 'active' : '' }}">
                                <i class="ti ti-shield-lock" aria-hidden="true"></i>
                                Matriks Hak Akses (RBAC)
                            </button>
                        </div>
                    @endif

                    @if ($group === 'academic-structure')
                        <h4 class="subheader d-flex align-items-center gap-2"><i class="ti ti-building" aria-hidden="true"></i>Struktur Akademik</h4>
                        <div class="list-group list-group-transparent">
                            @if(active_has_any_role(['admin lppm', 'superadmin']))
                                <button wire:click="setActiveTab('study-programs')"
                                    class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'study-programs' ? 'active' : '' }}">
                                <i class="ti ti-school" aria-hidden="true"></i>
                                    Program Studi
                                </button>
                                <button wire:click="setActiveTab('faculties')"
                                    class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'faculties' ? 'active' : '' }}">
                                <i class="ti ti-building-community" aria-hidden="true"></i>
                                    Fakultas
                                </button>
                                <button wire:click="setActiveTab('institutions')"
                                    class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'institutions' ? 'active' : '' }}">
                                    <x-lucide-building-2 class="icon me-2" />
                                    Institusi
                                </button>
                            @endif

                            @if(active_has_any_role(['admin lppm', 'superadmin', 'dekan']) && \App\Models\Setting::get('feature_roadmap_active', false))
                                <button wire:click="setActiveTab('faculty-roadmaps')"
                                    class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'faculty-roadmaps' ? 'active' : '' }}">
                                <i class="ti ti-map" aria-hidden="true"></i>
                                    Peta Jalan Fakultas
                                </button>
                            @endif

                            @if(active_has_any_role(['admin lppm', 'superadmin', 'dekan', 'kaprodi']) && \App\Models\Setting::get('feature_roadmap_active', false))
                                <button wire:click="setActiveTab('study-program-roadmaps')"
                                    class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'study-program-roadmaps' ? 'active' : '' }}">
                                <i class="ti ti-route" aria-hidden="true"></i>
                                    Peta Jalan Prodi
                                </button>
                            @endif
                        </div>
                    @endif

                    @if ($group === 'budget')
                        <h4 class="subheader d-flex align-items-center gap-2"><i class="ti ti-cash" aria-hidden="true"></i>Anggaran &amp; RAB</h4>
                        <div class="list-group list-group-transparent">
                            <button wire:click="setActiveTab('budget-groups')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'budget-groups' ? 'active' : '' }}">
                                <i class="ti ti-category" aria-hidden="true"></i>
                                Kelompok Anggaran
                            </button>
                            <button wire:click="setActiveTab('budget-components')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'budget-components' ? 'active' : '' }}">
                                <i class="ti ti-puzzle" aria-hidden="true"></i>
                                Komponen Anggaran
                            </button>
                            <button wire:click="setActiveTab('budget-caps')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'budget-caps' ? 'active' : '' }}">
                                <i class="ti ti-coin" aria-hidden="true"></i>
                                Batas Anggaran Tahunan
                            </button>
                        </div>
                    @endif

                    @if ($group === 'partnership')
                        <h4 class="subheader d-flex align-items-center gap-2"><i class="ti ti-affiliate" aria-hidden="true"></i>Kemitraan &amp; Prioritas</h4>
                        <div class="list-group list-group-transparent">
                            <button wire:click="setActiveTab('partners')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'partners' ? 'active' : '' }}">
                                <i class="ti ti-handshake" aria-hidden="true"></i>
                                Mitra
                            </button>
                            <button wire:click="setActiveTab('national-priorities')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'national-priorities' ? 'active' : '' }}">
                                <i class="ti ti-flag" aria-hidden="true"></i>
                                Prioritas Nasional
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-column col-12 col-md-9">
                <div class="alert alert-info mx-3 mt-3 mb-0 d-flex align-items-start gap-2" role="alert">
                    <i class="ti ti-info-circle alert-icon" style="font-size: 1.25rem;" aria-hidden="true"></i>
                    Tidak Disarankan menghapus data master yang sudah digunakan pada entitas lain untuk menghindari
                    inkonsistensi data.
                </div>
                <div class="card-body">
                    @if ($activeTab === 'focus-areas')
                        <div>
                            <livewire:settings.tabs.focus-area-manager />
                        </div>
                    @elseif ($activeTab === 'keywords')
                        <div>
                            <livewire:settings.tabs.keyword-manager />
                        </div>
                    @elseif ($activeTab === 'themes')
                        <div>
                            <livewire:settings.tabs.theme-manager />
                        </div>
                    @elseif ($activeTab === 'topics')
                        <div>
                            <livewire:settings.tabs.topic-manager />
                        </div>
                    @elseif ($activeTab === 'research-schemes')
                        <div>
                            <livewire:settings.tabs.research-scheme-manager />
                        </div>
                    @elseif ($activeTab === 'community-service-schemes')
                        <div>
                            <livewire:settings.tabs.community-service-scheme-manager />
                        </div>
                    @elseif ($activeTab === 'science-clusters')
                        <div>
                            <livewire:settings.tabs.science-cluster-manager />
                        </div>
                    @elseif ($activeTab === 'quota-messages')
                        <div>
                            <livewire:settings.tabs.quota-message-manager />
                        </div>
                    @elseif ($activeTab === 'study-programs')
                        <div>
                            <livewire:settings.tabs.study-program-manager />
                        </div>
                    @elseif ($activeTab === 'faculties')
                        <div>
                            <livewire:settings.tabs.faculty-manager />
                        </div>
                    @elseif ($activeTab === 'institutions')
                        <div>
                            <livewire:settings.tabs.institution-manager />
                        </div>
                    @elseif ($activeTab === 'faculty-roadmaps')
                        <div>
                            <livewire:settings.tabs.faculty-roadmap-manager />
                        </div>
                    @elseif ($activeTab === 'study-program-roadmaps')
                        <div>
                            <livewire:settings.tabs.study-program-roadmap-manager />
                        </div>
                    @elseif ($activeTab === 'partners')
                        <div>
                            <livewire:settings.tabs.partner-manager />
                        </div>
                    @elseif ($activeTab === 'national-priorities')
                        <div>
                            <livewire:settings.tabs.national-priority-manager />
                        </div>
                    @elseif ($activeTab === 'budget-groups')
                        <div>
                            <livewire:settings.tabs.budget-group-manager />
                        </div>
                    @elseif ($activeTab === 'budget-components')
                        <div>
                            <livewire:settings.tabs.budget-component-manager />
                        </div>
                    @elseif ($activeTab === 'budget-caps')
                        <div>
                            <livewire:settings.tabs.budget-cap-manager />
                        </div>
                    @elseif ($activeTab === 'tkt')
                        <div>
                            <livewire:settings.tabs.tkt-manager />
                        </div>
                    @elseif ($activeTab === 'review-criteria')
                        <div>
                            <livewire:settings.review-criteria-manager />
                        </div>
                    @elseif ($activeTab === 'iku-output-types')
                        <div>
                            <livewire:settings.tabs.iku-output-type-manager />
                        </div>
                    @elseif ($activeTab === 'master-iku')
                        <div>
                            <livewire:settings.tabs.master-iku-manager />
                        </div>
                    @elseif ($activeTab === 'sdgs')
                        <div>
                            <livewire:settings.tabs.sdg-manager />
                        </div>
                    @elseif ($activeTab === 'rbac-matrix')
                        <div>
                            <livewire:settings.role-permission-matrix />
                        </div>
                    @elseif ($activeTab === 'strata')
                        <div>
                            <livewire:settings.tabs.strata-manager />
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>