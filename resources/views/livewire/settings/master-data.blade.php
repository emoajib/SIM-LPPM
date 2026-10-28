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
                        <h4 class="subheader">Konten Penelitian & Akademik</h4>
                        <div class="list-group list-group-transparent">
                            <button wire:click="setActiveTab('focus-areas')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'focus-areas' ? 'active' : '' }}">
                                
                                Area Fokus
                            </button>
                            <button wire:click="setActiveTab('keywords')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'keywords' ? 'active' : '' }}">
                                
                                Kata Kunci
                            </button>
                            <button wire:click="setActiveTab('themes')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'themes' ? 'active' : '' }}">
                                
                                Tema
                            </button>
                            <button wire:click="setActiveTab('topics')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'topics' ? 'active' : '' }}">
                                
                                Topik
                            </button>
                            <button wire:click="setActiveTab('research-schemes')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'research-schemes' ? 'active' : '' }}">
                                
                                Skema Penelitian
                            </button>
                            <button wire:click="setActiveTab('community-service-schemes')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'community-service-schemes' ? 'active' : '' }}">
                                
                                Skema Pengabdian
                            </button>
                            <button wire:click="setActiveTab('science-clusters')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'science-clusters' ? 'active' : '' }}">
                                
                                Klaster Sains
                            </button>
                            <button wire:click="setActiveTab('tkt')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'tkt' ? 'active' : '' }}">
                                
                                TKT
                            </button>
                            <button wire:click="setActiveTab('review-criteria')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'review-criteria' ? 'active' : '' }}">
                                
                                Kriteria Penilaian
                            </button>
                            <button wire:click="setActiveTab('iku-output-types')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'iku-output-types' ? 'active' : '' }}">
                                
                                Jenis Luaran IKU
                            </button>
                            <button wire:click="setActiveTab('master-iku')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'master-iku' ? 'active' : '' }}">
                                
                                Master IKU
                            </button>
                            <button wire:click="setActiveTab('sdgs')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'sdgs' ? 'active' : '' }}">
                                
                                SDG
                            </button>
                            <button wire:click="setActiveTab('strata')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'strata' ? 'active' : '' }}">
                                
                                Strata
                            </button>
                            <button wire:click="setActiveTab('quota-messages')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'quota-messages' ? 'active' : '' }}">
                                
                                Pesan Kuota
                            </button>
                        </div>
                        <h4 class="subheader mt-4">Manajemen Akses</h4>
                        <div class="list-group list-group-transparent">
                            <button wire:click="setActiveTab('rbac-matrix')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'rbac-matrix' ? 'active' : '' }}">
                                
                                Matriks Hak Akses (RBAC)
                            </button>
                        </div>
                    @endif

                    @if ($group === 'academic-structure')
                        <h4 class="subheader">Struktur Akademik</h4>
                        <div class="list-group list-group-transparent">
                            @if(active_has_any_role(['admin lppm', 'superadmin']))
                                <button wire:click="setActiveTab('study-programs')"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'study-programs' ? 'active' : '' }}">
                                    
                                    Program Studi
                                </button>
                                <button wire:click="setActiveTab('faculties')"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'faculties' ? 'active' : '' }}">
                                    
                                    Fakultas
                                </button>
                                <button wire:click="setActiveTab('institutions')"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'institutions' ? 'active' : '' }}">
                                    <x-lucide-building-2 class="icon me-2" />
                                    Institusi
                                </button>
                            @endif

                            @if(active_has_any_role(['admin lppm', 'superadmin', 'dekan']) && \App\Models\Setting::get('feature_roadmap_active', false))
                                <button wire:click="setActiveTab('faculty-roadmaps')"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'faculty-roadmaps' ? 'active' : '' }}">
                                    
                                    Peta Jalan Fakultas
                                </button>
                            @endif

                            @if(active_has_any_role(['admin lppm', 'superadmin', 'dekan', 'kaprodi']) && \App\Models\Setting::get('feature_roadmap_active', false))
                                <button wire:click="setActiveTab('study-program-roadmaps')"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'study-program-roadmaps' ? 'active' : '' }}">
                                    
                                    Peta Jalan Prodi
                                </button>
                            @endif
                        </div>
                    @endif

                    @if ($group === 'budget')
                        <h4 class="subheader">Anggaran & RAB</h4>
                        <div class="list-group list-group-transparent">
                            <button wire:click="setActiveTab('budget-groups')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'budget-groups' ? 'active' : '' }}">
                                
                                Kelompok Anggaran
                            </button>
                            <button wire:click="setActiveTab('budget-components')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'budget-components' ? 'active' : '' }}">
                                
                                Komponen Anggaran
                            </button>
                            <button wire:click="setActiveTab('budget-caps')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'budget-caps' ? 'active' : '' }}">
                                
                                Batas Anggaran Tahunan
                            </button>
                        </div>
                    @endif

                    @if ($group === 'partnership')
                        <h4 class="subheader">Kemitraan & Prioritas</h4>
                        <div class="list-group list-group-transparent">
                            <button wire:click="setActiveTab('partners')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'partners' ? 'active' : '' }}">
                                
                                Mitra
                            </button>
                            <button wire:click="setActiveTab('national-priorities')"
                                class="list-group-item list-group-item-action d-flex align-items-center {{ $activeTab === 'national-priorities' ? 'active' : '' }}">
                                
                                Prioritas Nasional
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-column col-12 col-md-9">
                <div class="alert alert-info m-3" role="alert">
                    <div class="alert-icon">

                        <!-- Download SVG icon from http://tabler.io/icons/icon/info-circle -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon alert-icon icon-2">
                            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                            <path d="M12 9h.01"></path>
                            <path d="M11 12h1v4h1"></path>
                        </svg>
                    </div>
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