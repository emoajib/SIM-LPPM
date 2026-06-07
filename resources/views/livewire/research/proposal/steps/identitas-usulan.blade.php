<!-- Section: Informasi Dasar -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <x-lucide-file-text class="icon me-3" />
            <h3 class="card-title mb-0">1.1 Informasi Dasar Proposal</h3>
        </div>

        <div class="row g-4">
            <div class="col-md-12">
                <div class="mb-3">
                    <label class="form-label d-flex align-items-center gap-2" for="title">
                        Judul Proposal <span class="text-danger">*</span>
                        <span class="text-muted" data-bs-toggle="tooltip"
                            title="Gunakan judul yang ringkas, spesifik, dan merepresentasikan substansi penelitian Anda (Maksimal 20 kata).">
                            <i class="ti ti-info-circle"></i>
                        </span>
                    </label>
                    <input id="title" type="text" class="form-control @error('form.title') is-invalid @enderror"
                        wire:model="form.title" placeholder="Masukkan judul proposal penelitian" required>
                    @error('form.title')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="">
                    <label class="form-label d-flex align-items-center gap-2" for="research_scheme">
                        Skema Penelitian <span class="text-danger">*</span>
                        <span class="text-muted" data-bs-toggle="tooltip"
                            title="Pilihan skema menentukan pagu anggaran dan luaran wajib yang harus dipenuhi.">
                            <i class="ti ti-info-circle"></i>
                        </span>
                    </label>
                    <div wire:ignore>
                        <select id="research_scheme"
                            class="form-select @error('form.research_scheme_id') is-invalid @enderror"
                            wire:model="form.research_scheme_id" x-data="tomSelect" placeholder="Pilih skema penelitian"
                            required>
                            <option value="">-- Pilih Skema Penelitian --</option>
                            @foreach ($this->schemes as $scheme)
                                <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('form.research_scheme_id')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-3">
                <div class="">
                    <label class="form-label" for="start_year">Tahun Mulai <span class="text-danger">*</span></label>
                    <input id="start_year" type="number"
                        class="form-control @error('form.start_year') is-invalid @enderror"
                        wire:model.live="form.start_year" min="2020" max="2050" placeholder="{{ date('Y') }}" required>
                    @error('form.start_year')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-3">
                <div class="">
                    <label class="form-label" for="semester">Semester <span class="text-danger">*</span></label>
                    <select id="semester" class="form-select @error('form.semester') is-invalid @enderror"
                        wire:model.live="form.semester" required>
                        <option value="ganjil">Ganjil</option>
                        <option value="genap">Genap</option>
                    </select>
                    <small class="text-muted d-block mt-1">
                        Ganjil: 1 Sep - 28/29 Feb | Genap: 1 Mar - 31 Agt
                    </small>
                    @error('form.semester')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-3">
                <div class="">
                    <label class="form-label" for="duration_in_years">Durasi (Tahun) <span
                            class="text-danger">*</span></label>
                    <input id="duration_in_years" type="number"
                        class="form-control @error('form.duration_in_years') is-invalid @enderror"
                        wire:model.live="form.duration_in_years" min="1" max="10" value="1" required>
                    @error('form.duration_in_years')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if ($form->start_year && $form->duration_in_years)
                        <small class="text-muted">
                            Periode: {{ $form->start_year }} -
                            {{ (int) $form->start_year + (int) $form->duration_in_years - 1 }}
                        </small>
                    @endif
                </div>
            </div>

            <div class="col-md-4">
                <div class="">
                    <label class="form-label d-flex align-items-center gap-2" for="focus_area">
                        Bidang Fokus <span class="text-danger">*</span>
                        <span class="text-muted" data-bs-toggle="tooltip"
                            title="Bidang fokus disesuaikan dengan Rencana Induk Penelitian (RIP) Institusi.">
                            <i class="ti ti-info-circle"></i>
                        </span>
                    </label>
                    <div wire:ignore>
                        <select id="focus_area" class="form-select @error('form.focus_area_id') is-invalid @enderror"
                            wire:model.live="form.focus_area_id" x-data="tomSelect" placeholder="Pilih bidang fokus"
                            required>
                            <option value="">-- Pilih Bidang Fokus --</option>
                            @foreach ($this->focusAreas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('form.focus_area_id')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="">
                    <label class="form-label" for="theme">Tema <span class="text-danger">*</span></label>
                    <div wire:key="theme-select-{{ $form->focus_area_id }}">
                        <div wire:ignore>
                            <select id="theme" class="form-select @error('form.theme_id') is-invalid @enderror"
                                wire:model.live="form.theme_id" x-data="tomSelect" placeholder="Pilih tema" required>
                                <option value="">-- Pilih Tema --</option>
                                @foreach ($this->themes as $theme)
                                    <option value="{{ $theme->id }}">{{ $theme->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @error('form.theme_id')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="">
                    <label class="form-label" for="topic">Topik <span class="text-danger">*</span></label>
                    <div wire:key="topic-select-{{ $form->theme_id }}">
                        <div wire:ignore>
                            <select id="topic" class="form-select @error('form.topic_id') is-invalid @enderror"
                                wire:model="form.topic_id" x-data="tomSelect" placeholder="Pilih topik" required>
                                <option value="">-- Pilih Topik --</option>
                                @foreach ($this->topics as $topic)
                                    <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @error('form.topic_id')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            @if(\App\Models\Setting::get('feature_roadmap_active', false))
                <div class="col-md-12">
                    <div class="">
                        <label class="form-label d-flex align-items-center gap-2" for="study_program_roadmap">
                            Pohon Penelitian Prodi <span class="text-danger">*</span>
                            <span class="text-muted" data-bs-toggle="tooltip"
                                title="Pilih topik/pohon penelitian prodi yang relevan dengan usulan ini.">
                                <i class="ti ti-info-circle"></i>
                            </span>
                        </label>
                        <div wire:ignore>
                            <select id="study_program_roadmap" class="form-select @error('form.study_program_roadmap_id') is-invalid @enderror"
                                wire:model="form.study_program_roadmap_id" x-data="tomSelect" placeholder="Pilih Pohon Penelitian Prodi" required>
                                <option value="">-- Pilih Pohon Penelitian Prodi --</option>
                                @foreach ($this->studyProgramRoadmaps as $roadmap)
                                    <option value="{{ $roadmap->id }}">{{ $roadmap->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('form.study_program_roadmap_id')
                            <div class="d-block invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

<!-- Section: Rumpun Ilmu -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <x-lucide-dna class="icon me-3" />
            <h3 class="card-title mb-0">1.2 Rumpun Ilmu</h3>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label" for="cluster_level1">Level 1 <span class="text-danger">*</span></label>
                    <div wire:ignore>
                        <select id="cluster_level1"
                            class="form-select @error('form.cluster_level1_id') is-invalid @enderror"
                            wire:model.live="form.cluster_level1_id" x-data="tomSelect" placeholder="Pilih level 1"
                            required>
                            <option value="">-- Pilih Level 1 --</option>
                            @foreach ($this->clusterLevel1Options as $cluster)
                                <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('form.cluster_level1_id')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label" for="cluster_level2">Level 2</label>
                    <div wire:key="cluster2-select-{{ $form->cluster_level1_id }}">
                        <div wire:ignore>
                            <select id="cluster_level2"
                                class="form-select @error('form.cluster_level2_id') is-invalid @enderror"
                                wire:model.live="form.cluster_level2_id" x-data="tomSelect"
                                placeholder="Pilih level 2 (opsional)">
                                <option value="">-- Pilih Level 2 (Opsional) --</option>
                                @foreach ($this->clusterLevel2Options as $cluster)
                                    <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @error('form.cluster_level2_id')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label" for="cluster_level3">Level 3</label>
                    <div wire:key="cluster3-select-{{ $form->cluster_level2_id }}">
                        <div wire:ignore>
                            <select id="cluster_level3"
                                class="form-select @error('form.cluster_level3_id') is-invalid @enderror"
                                wire:model="form.cluster_level3_id" x-data="tomSelect"
                                placeholder="Pilih level 3 (opsional)">
                                <option value="">-- Pilih Level 3 (Opsional) --</option>
                                @foreach ($this->clusterLevel3Options as $cluster)
                                    <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @error('form.cluster_level3_id')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section: Ringkasan -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <x-lucide-file-text class="icon me-3" />
            <h3 class="card-title mb-0">1.3 Ringkasan Proposal</h3>
        </div>

        <div class="mb-3">
            <label class="form-label" for="summary">Ringkasan <span class="text-danger">*</span></label>
            <textarea id="summary" class="form-control @error('form.summary') is-invalid @enderror"
                wire:model="form.summary" rows="4" placeholder="Masukkan ringkasan proposal (minimal 100 karakter)"
                required></textarea>
            @error('form.summary')
                <div class="d-block invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted d-block mb-3">Minimum 100 karakter</small>
        </div>

        <div class="mb-3">
            <label class="form-label" for="keywords">Kata Kunci (Keywords) <span class="text-danger">*</span></label>
            <div wire:ignore>
                <select id="keywords" class="form-select @error('form.keywords') is-invalid @enderror"
                    wire:model.live="form.keywords" x-data="tomSelect({create: true, maxItems: 5})" multiple
                    placeholder="Ketik lalu Enter untuk menambahkan kata kunci (Maks 5)" required>
                    @foreach ($form->keywords as $keyword)
                        <option value="{{ $keyword }}" selected>{{ $keyword }}</option>
                    @endforeach
                </select>
            </div>
            @error('form.keywords')
                <div class="d-block invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Tekan Enter setelah mengetik kata kunci. Maksimal 5 kata kunci.</small>
        </div>

        <div class="mb-3">
            <label class="form-label d-flex align-items-center gap-2" for="asta_cita">
                Asta Cita Terkait (Opsional)
                <span class="text-muted" data-bs-toggle="tooltip"
                    title="Jelaskan kaitan usulan ini dengan poin-poin Asta Cita terkait jika ada.">
                    <i class="ti ti-info-circle"></i>
                </span>
            </label>
            <textarea id="asta_cita" class="form-control @error('form.asta_cita') is-invalid @enderror"
                wire:model="form.asta_cita" rows="3" placeholder="Jelaskan Asta Cita terkait proposal ini"></textarea>
            @error('form.asta_cita')
                <div class="d-block invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<!-- Section: Sustainable Development Goals (SDGs) -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <x-lucide-globe class="icon me-3" />
            <h3 class="card-title mb-0">1.4 Sustainable Development Goals (SDGs)</h3>
        </div>

        <div class="mb-3">
            <label class="form-label d-flex align-items-center gap-2" for="sdgs">
                Pilih SDGs yang Relevan <span class="text-danger">*</span>
                <span class="text-muted" data-bs-toggle="tooltip"
                    title="Pilih satu atau lebih SDGs yang berkaitan dengan usulan penelitian Anda. Data ini digunakan untuk perhitungan IKU-07.">
                    <i class="ti ti-info-circle"></i>
                </span>
            </label>
            <div wire:ignore>
                <select id="sdgs" class="form-select @error('form.sdg_ids') is-invalid @enderror"
                    wire:model.live="form.sdg_ids" x-data="tomSelect" multiple
                    placeholder="Pilih kategori SDGs yang relevan" required>
                    @foreach ($this->sdgs as $sdg)
                        <option value="{{ $sdg->id }}">{{ $sdg->name }} - {{ $sdg->description }}</option>
                    @endforeach
                </select>
            </div>
            @error('form.sdg_ids')
                <div class="d-block invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Dapat memilih lebih dari satu kategori.</small>
        </div>
    </div>
</div>

<!-- Section: Target IKU -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <x-lucide-target class="icon me-3" />
            <h3 class="card-title mb-0">1.5 Pemetaan Target IKU</h3>
        </div>

        <div class="mb-0">
            <label class="form-label d-flex align-items-center gap-2 mb-3">
                Pilih Target IKU Kepmen 358 <span class="text-danger">*</span>
                <span class="text-muted" data-bs-toggle="tooltip"
                    title="Pilih IKU yang menjadi target luaran dari usulan ini. Gunakan CTRL/CMD + Klik untuk memilih lebih dari satu.">
                    <i class="ti ti-info-circle"></i>
                </span>
            </label>

            <div class="row g-3">
                @foreach($this->masterIkus as $iku)
                    <div class="col-md-12">
                        <label
                            class="form-check form-check-inline mb-0 p-3 border rounded w-100 cursor-pointer hover-shadow transition-all {{ in_array($iku->id, $form->targeted_iku_ids) ? 'border-primary bg-primary-lt' : '' }}">
                            <input class="form-check-input" type="checkbox" value="{{ $iku->id }}"
                                wire:model.live="form.targeted_iku_ids">
                            <span class="form-check-label">
                                <strong>{{ $iku->code }}</strong>: {{ $iku->name }}
                                <div class="text-muted small mt-1">{{ $iku->description }}</div>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>

            @error('form.targeted_iku_ids')
                <div class="d-block invalid-feedback mt-2">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<!-- Section: Detail Penelitian -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <x-lucide-microscope class="icon me-3" />
            <h3 class="card-title mb-0">Detail Penelitian</h3>
        </div>

        <div class="mb-3">
            <label class="form-label" for="tkt_type">Kategori TKT <span class="text-danger">*</span></label>
            <select id="tkt_type" class="form-select @error('form.tkt_type') is-invalid @enderror"
                wire:model.live="form.tkt_type" required>
                <option value="">-- Pilih Kategori TKT --</option>
                @foreach ($this->tktTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
            @error('form.tkt_type')
                <div class="d-block invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Pengukuran TKT</label>
            @php
                // Get strata from selected research scheme
                $selectedScheme = $form->research_scheme_id
                    ? \App\Models\ResearchScheme::find($form->research_scheme_id)
                    : null;
                $schemeStrata = $selectedScheme?->strata;
                $targetRange = \App\Livewire\Research\Proposal\Components\TktMeasurement::getTktRangeForStrata(
                    $schemeStrata,
                );
            @endphp

            @if ($schemeStrata && $targetRange)
                <div class="alert alert-info mb-2 py-2">
                    <small>Skema <strong>{{ $schemeStrata }}</strong> membutuhkan TKT Level
                        <strong>{{ $targetRange[0] }} - {{ $targetRange[1] }}</strong></small>
                </div>
            @endif

            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                    data-bs-target="#tkt-measurement-modal"
                    wire:click="$dispatch('tkt-type-selected', { tktType: '{{ $form->tkt_type }}', existingScores: @js($form->tkt_indicator_scores), strata: '{{ $schemeStrata }}' })"
                    @if (!$form->tkt_type) disabled @endif>
                    <x-lucide-ruler class="icon me-2" />
                    Ukur TKT Saat Ini
                </button>

                @if (!empty($form->tkt_results))
                        @php
                            $currentTkt = 0;
                            // Get level numbers from TktLevel model using the level_id keys
                            // since 'level' is no longer stored in tkt_results
                            foreach ($form->tkt_results as $levelId => $data) {
                                if (($data['percentage'] ?? 0) >= 80) {
                                    $tktLevel = \App\Models\TktLevel::find($levelId);
                                    if ($tktLevel) {
                                        $currentTkt = max($currentTkt, $tktLevel->level);
                                    }
                                }
                            }
                            $isWithinTarget = $targetRange
                                ? $currentTkt >= $targetRange[0] && $currentTkt <= $targetRange[1]
                                : null;
                        @endphp
                        <x-tabler.badge class="fs-3" :color="$isWithinTarget === true
                    ? 'success'
                    : ($isWithinTarget === false
                        ? 'warning'
                        : 'secondary')">
                            TKT Saat Ini: Level {{ $currentTkt }}
                            @if ($isWithinTarget === true)
                                <i class="ti ti-check ms-1"></i>
                            @elseif ($isWithinTarget === false)
                                <i class="ti ti-alert-triangle ms-1"></i>
                            @endif
                        </x-tabler.badge>
                @endif
            </div>
            @error('form.tkt_results')
                <div class="d-block invalid-feedback mt-2">{{ $message }}</div>
            @enderror
            <small class="text-muted d-block mt-1">Pilih kategori terlebih dahulu, lalu klik tombol untuk mengukur
                TKT.</small>
        </div>

        {{-- <div class="mb-3">
            <label class="form-label" for="background">Latar Belakang</label>
            <textarea id="background" class="form-control @error('form.background') is-invalid @enderror"
                wire:model="form.background" rows="5"
                placeholder="Jelaskan latar belakang penelitian (minimal 200 karakter)"></textarea>
            @error('form.background')
            <div class="d-block invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Minimum 200 karakter</small>
        </div>

        <div class="mb-3">
            <label class="form-label" for="methodology">Metodologi</label>
            <textarea id="methodology" class="form-control @error('form.methodology') is-invalid @enderror"
                wire:model="form.methodology" rows="5"
                placeholder="Jelaskan metodologi penelitian (minimal 200 karakter)"></textarea>
            @error('form.methodology')
            <div class="d-block invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Minimum 200 karakter</small>
        </div> --}}
    </div>
</div>

<!-- Section: Ketua Tasks -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <x-lucide-user-check class="icon me-3" />
            <h3 class="card-title mb-0">Tugas Ketua Peneliti</h3>
        </div>

        <div class="mb-3">
            <label class="form-label">Ketua Peneliti</label>
            <input type="text" class="form-control @error('author_name') is-invalid @enderror" wire:model="author_name"
                placeholder="Nama Ketua Peneliti" required disabled />
            @error('author_name')
                <div class="d-block invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="ketua_tasks">Jelaskan tugas ketua peneliti dalam penelitian ini
                <span class="text-danger">*</span></label>
            <textarea id="ketua_tasks" class="form-control @error('form.author_tasks') is-invalid @enderror"
                wire:model="form.author_tasks" rows="3" placeholder="Jelaskan tugas ketua peneliti dalam penelitian ini"
                required></textarea>
            @error('form.author_tasks')
                <div class="d-block invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<!-- Section: Anggota -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <x-lucide-users class="icon me-3" />
            <h3 class="card-title mb-0">Anggota Peneliti</h3>
        </div>

        <livewire:forms.team-members-form :members="$form->members" modal-title="Tambah Anggota Peneliti"
            member-label="Anggota Peneliti" :key="'team-members-form'" />
    </div>
</div>

<livewire:research.proposal.components.tkt-measurement />