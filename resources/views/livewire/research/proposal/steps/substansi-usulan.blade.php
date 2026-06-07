<!-- Section: Substansi Usulan -->
<div class="mb-3 card">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <x-lucide-book-open class="me-3 icon" />
            <h3 class="mb-0 card-title">2.1 Substansi Usulan</h3>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label d-flex align-items-center gap-2" for="macro_research_group">
                        Kelompok Makro Riset
                        <span class="text-muted" data-bs-toggle="tooltip"
                            title="Pilihlah kelompok riset yang paling sesuai dengan fokus penelitian Anda untuk memudahkan pemetaan data SINTA.">
                            <i class="ti ti-info-circle"></i>
                        </span>
                    </label>
                    <div wire:ignore>
                        <select id="macro_research_group"
                            class="form-select @error('form.macro_research_group_id') is-invalid @enderror"
                            wire:model="form.macro_research_group_id" x-data="tomSelect"
                            placeholder="Pilih kelompok makro riset">
                            <option value="">-- Pilih Kelompok Makro Riset --</option>
                            @foreach ($this->macroResearchGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('form.macro_research_group_id')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label class="d-flex align-items-center justify-content-between form-label" for="substance_file">
                        <span>Unggah Substansi Usulan (PDF)</span>
                        @if ($this->templateUrl)
                            <a href="{{ $this->templateUrl }}" target="_blank" class="text-primary text-decoration-none"
                                style="font-size: 0.875rem;">
                                <x-lucide-download class="me-1 icon" style="width: 1rem; height: 1rem;" />
                                Unduh Template
                            </a>
                        @endif
                    </label>
                    <input id="substance_file" type="file" wire:key="substance-file-{{ $fileInputIteration }}"
                        class="form-control @error('form.substance_file') is-invalid @enderror"
                        wire:model="form.substance_file" accept=".pdf">
                    <div wire:loading wire:target="form.substance_file" class="mt-2 text-primary small">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Sedang mengunggah file... Mohon tunggu.
                    </div>
                    @error('form.substance_file')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Maksimal 10MB, format PDF, DOC, DOCX</small>

                    @if ($form->substance_file)
                        <div class="mt-2">
                            <x-lucide-file-check class="text-success icon" />
                            File terpilih: {{ $form->substance_file->getClientOriginalName() }}
                        </div>
                    @elseif ($form->proposal && $form->proposal->detailable && $form->proposal->detailable->hasMedia('substance_file'))
                        <div class="mt-2">
                            <x-lucide-file-check class="text-success icon" />
                            @php $media = $form->proposal->detailable->getFirstMedia('substance_file'); @endphp
                            <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('media.download', now()->addMinutes(config('media-library.temporary_url_default_lifetime', 5)), ['media' => $media]) }}" target="_blank"
                                class="text-decoration-none" data-navigate-ignore="true">
                                {{ $media->file_name }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section: Luaran Target Capaian -->
<div class="mb-3 card">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center">
                <x-lucide-target class="me-3 icon" />
                <h3 class="mb-0 card-title">2.2 Luaran Target Capaian</h3>
            </div>
            <button type="button" wire:click="addOutput" class="btn btn-primary btn-sm">
                <x-lucide-plus class="icon" />
                Tambah Luaran
            </button>
        </div>

        @error('form.outputs')
            <div class="mb-3 alert alert-danger">
                <div class="d-flex">
                    <x-lucide-alert-circle class="me-2 icon" />
                    <div>{{ $message }}</div>
                </div>
            </div>
        @enderror

        @if (empty($form->outputs))
            <div class="alert alert-info">
                <x-lucide-info class="me-2 icon" />
                Belum ada luaran target. Klik tombol "Tambah Luaran" untuk menambahkan.
            </div>
        @else
            @php
                $outputTypes = \App\Constants\ProposalConstants::RESEARCH_OUTPUT_TYPES;
            @endphp
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="10%">Tahun Ke- <span class="text-danger">*</span></th>
                            <th width="12%">Jenis <span class="text-danger">*</span></th>
                            <th width="18%">Kategori Luaran <span class="text-danger">*</span></th>
                            <th width="20%">Luaran <span class="text-danger">*</span></th>
                            <th width="15%">Status <span class="text-danger">*</span></th>
                            <th width="20%">Keterangan (URL) <span class="text-danger">*</span></th>
                            <th width="5%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($form->outputs as $index => $output)
                            @php
                                $startYear = (int) ($form->start_year ?: date('Y'));
                                $duration = (int) ($form->duration_in_years ?: 1);
                                $currentGroup = $form->outputs[$index]['group'] ?? '';
                            @endphp
                            <tr wire:key="output-{{ $index }}">
                                <td>
                                    <select wire:model="form.outputs.{{ $index }}.year"
                                        class="form-select-sm form-select @error('form.outputs.' . $index . '.year') is-invalid @enderror">
                                        @for ($y = 1; $y <= $duration; $y++)
                                            <option value="{{ $y }}">{{ $y }}
                                                ({{ $startYear + $y - 1 }})</option>
                                        @endfor
                                    </select>
                                </td>
                                <td>
                                    <select wire:model="form.outputs.{{ $index }}.category"
                                        class="form-select-sm form-select @error('form.outputs.' . $index . '.category') is-invalid @enderror">
                                        <option value="Wajib">Wajib</option>
                                        <option value="Tambahan">Tambahan</option>
                                    </select>
                                </td>

                                <td>
                                    <select wire:model.live="form.outputs.{{ $index }}.group"
                                        class="form-select-sm form-select @error('form.outputs.' . $index . '.group') is-invalid @enderror">
                                        <option value="">-- Pilih --</option>
                                        <option value="jurnal">Jurnal</option>
                                        <option value="prosiding">Prosiding</option>
                                        <option value="buku">Buku</option>
                                        <option value="hki">HKI</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </td>
                                <td>
                                    <select wire:model="form.outputs.{{ $index }}.type"
                                        class="form-select-sm form-select @error('form.outputs.' . $index . '.type') is-invalid @enderror">
                                        <option value="">-- Pilih --</option>
                                        @if (!empty($currentGroup) && isset($outputTypes[$currentGroup]))
                                            @foreach ($outputTypes[$currentGroup] as $typeOption)
                                                <option value="{{ $typeOption }}">{{ $typeOption }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </td>
                                <td>
                                    <select wire:model="form.outputs.{{ $index }}.status"
                                        class="form-select form-select-sm @error('form.outputs.' . $index . '.status') is-invalid @enderror">
                                        <option value="">-- Pilih --</option>
                                        @foreach(\App\Constants\ProposalConstants::OUTPUT_STATUSES as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" wire:model="form.outputs.{{ $index }}.description"
                                        class="form-control form-control-sm @error('form.outputs.' . $index . '.description') is-invalid @enderror"
                                        placeholder="Keterangan (URL)">
                                </td>
                                <td>
                                    <button type="button" wire:click="removeOutput({{ $index }})" class="btn btn-sm btn-danger">
                                        <x-lucide-trash-2 class="icon" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>