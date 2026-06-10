<div>
    <div class="row">
        <div class="col-md-8">
            <h3 class="card-title mb-3">
                <x-lucide-cloud-upload class="icon me-1" />
                Pulihkan Data
            </h3>

            <p class="text-secondary mb-3">
                Upload file backup (.sql / .zip) untuk memulihkan data setelah <strong>disaster recovery</strong> atau
                <strong>deploy fresh</strong>.
            </p>

            <div class="mb-4">
                <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
                    <x-lucide-alert-triangle class="icon" />
                    <div>
                        <strong>Perhatian:</strong>
                        Mode <strong>Sinkron</strong> akan menghapus data lama dan mengganti dengan data backup.
                        Mode <strong>Tambah</strong> hanya menambahkan data baru (risiko duplikasi).
                        Sistem akan membuat <strong>backup otomatis</strong> sebelum memulihkan.
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                <x-lucide-database class="icon me-1" />
                                Database
                            </h4>
                            <p class="text-secondary small">
                                File .sql hasil backup database.<br>
                                <span class="badge bg-light text-secondary">Limit PHP: {{ $this->phpLimits['upload_max'] }}</span>
                            </p>
                            <input
                                type="file"
                                wire:model.live="sqlFile"
                                accept=".sql,.txt"
                                class="form-control"
                                @disabled($isRunning)
                            >
                            @error('sqlFile') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                <x-lucide-archive class="icon me-1" />
                                Storage
                            </h4>
                            <p class="text-secondary small">
                                File .zip hasil backup storage.<br>
                                <span class="badge bg-light text-secondary">Limit PHP: {{ $this->phpLimits['upload_max'] }}</span>
                            </p>
                            <input
                                type="file"
                                wire:model.live="zipFile"
                                accept=".zip"
                                class="form-control"
                                @disabled($isRunning)
                            >
                            @error('zipFile') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            @if ($uploadErrorMessage)
                <div class="alert alert-danger mb-4">
                    <x-lucide-alert-circle class="icon me-2" />
                    {{ $uploadErrorMessage }}
                </div>
            @endif

            @if ($uploadedSqlPath)
                <div class="mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-2">
                                <x-lucide-settings-2 class="icon me-1" />
                                Mode Restore Database
                            </h4>
                            <p class="text-secondary small mb-3">Pilih cara data database dipulihkan.</p>
                            <div class="d-flex gap-4">
                                <label class="form-check">
                                    <input
                                        type="radio"
                                        wire:model.live="replaceMode"
                                        value="1"
                                        class="form-check-input"
                                    >
                                    <span class="form-check-label">
                                        <strong>Sinkron</strong>
                                        <small class="d-block text-secondary">Hapus data lama, ganti dengan data backup.</small>
                                    </span>
                                </label>
                                <label class="form-check">
                                    <input
                                        type="radio"
                                        wire:model.live="replaceMode"
                                        value="0"
                                        class="form-check-input"
                                    >
                                    <span class="form-check-label">
                                        <strong>Tambah</strong>
                                        <small class="d-block text-secondary">INSERT data backup tanpa hapus data lama.</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($uploadedZipPath)
                <div class="mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-2">
                                <x-lucide-settings-2 class="icon me-1" />
                                Mode Restore Storage
                            </h4>
                            <p class="text-secondary small mb-3">Pilih cara file storage dipulihkan.</p>
                            <div class="d-flex gap-4 mb-4">
                                <label class="form-check">
                                    <input
                                        type="radio"
                                        wire:model.live="zipReplaceMode"
                                        value="1"
                                        class="form-check-input"
                                    >
                                    <span class="form-check-label">
                                        <strong>Sinkron</strong>
                                        <small class="d-block text-secondary">Bersihkan folder lokal sebelum mengekstrak backup.</small>
                                    </span>
                                </label>
                                <label class="form-check">
                                    <input
                                        type="radio"
                                        wire:model.live="zipReplaceMode"
                                        value="0"
                                        class="form-check-input"
                                    >
                                    <span class="form-check-label">
                                        <strong>Tambah</strong>
                                        <small class="d-block text-secondary">Gabungkan file backup dengan file lokal.</small>
                                    </span>
                                </label>
                            </div>

                            @if (!empty($availableZipFolders))
                                <h4 class="subheader mb-3">Pilih Folder untuk Dipulihkan</h4>
                                <div class="row g-2">
                                    @foreach($availableZipFolders as $folder)
                                        <div class="col-6 col-sm-4">
                                            <label class="form-check form-check-inline m-0 p-2 border rounded w-100 cursor-pointer hover-bg-light transition-all">
                                                <input type="checkbox" class="form-check-input" wire:model="selectedZipFolders" value="{{ $folder }}">
                                                <span class="form-check-label text-truncate small" title="{{ $folder === '.' ? 'File di root' : $folder }}">
                                                    {{ $folder === '.' ? '(File Root)' : $folder }}
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($hasPreview && !empty($preview))
                @if (isset($preview['valid']))
                    <div class="mb-3">
                        <h4 class="subheader">Preview File ZIP</h4>
                        @if ($preview['valid'])
                            <div class="alert alert-success d-flex align-items-center gap-2">
                                <x-lucide-check-circle class="icon" />
                                <div>File ZIP aman untuk dipulihkan.</div>
                            </div>
                        @else
                            <div class="alert alert-danger d-flex align-items-center gap-2">
                                <x-lucide-alert-octagon class="icon" />
                                <div>
                                    <strong>File ZIP mengandung masalah:</strong>
                                    <ul class="mb-0 mt-1">
                                        @foreach ($preview['issues'] as $issue)
                                            <li>{{ $issue }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="mb-3">
                        <h4 class="subheader">Preview Restore Database</h4>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tabel</th>
                                        <th class="text-end">Baris</th>
                                        <th class="text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $service = app(\App\Services\DatabaseRestoreService::class);
                                        $preservedTables = $service->getPreservedTableInfo($preview['tables']);
                                    @endphp
                                    @foreach ($preview['tables'] as $table => $count)
                                        <tr>
                                            <td>{{ $table }}</td>
                                            <td class="text-end">{{ number_format($count) }}</td>
                                            <td class="text-end">
                                                @if (isset($preservedTables[$table]))
                                                    <span class="badge bg-secondary">Dipertahankan</span>
                                                @elseif ($replaceMode)
                                                    <span class="badge bg-info">Diganti</span>
                                                @else
                                                    <span class="badge bg-success">Ditambah</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($preview['blocked_count'] > 0)
                            <div class="alert alert-info d-flex align-items-center gap-2">
                                <x-lucide-info class="icon" />
                                <div>
                                    <strong>{{ $preview['blocked_count'] }} statement</strong> diblokir (DROP, ALTER, dll.)
                                    akan dilewati.
                                </div>
                            </div>
                        @endif

                        @if ($replaceMode && !empty($preservedTables))
                            <div class="alert alert-info d-flex align-items-center gap-2">
                                <x-lucide-shield class="icon" />
                                <div>
                                    <strong>Tabel sistem dipertahankan:</strong>
                                    {{ implode(', ', array_keys($preservedTables)) }}
                                    — tidak akan dihapus atau diisi ulang.
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if (!isset($preview['valid']) || $preview['valid'])
                    <button
                        type="button"
                        wire:click="executeRestore"
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        @disabled($isRunning)
                    >
                        <span wire:loading.remove>
                            <x-lucide-play class="icon me-1" />
                            Pulihkan Data
                        </span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Memulihkan...
                        </span>
                    </button>
                @endif

                <button
                    type="button"
                    wire:click="resetUpload"
                    class="btn btn-outline-secondary"
                    @disabled($isRunning)
                >
                    <x-lucide-x class="icon me-1" />
                    Batal
                </button>
            @endif

            @if (!empty($output))
                <div class="mt-4">
                    <h3 class="card-title mb-2">Log Proses</h3>
                    <pre
                        class="bg-dark text-light p-3 rounded mb-0"
                        style="font-size: 0.8rem; max-height: 500px; overflow-y: auto; font-family: 'JetBrains Mono', 'Cascadia Code', 'Fira Code', monospace;"
                    >{{ $output }}</pre>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="d-flex flex-column gap-3">
                <div>
                    <h4 class="subheader">Yang Akan Dipulihkan</h4>
                    <ul class="list-unstyled mb-0 mt-2">
                        <li class="py-1 d-flex align-items-center gap-2">
                            <x-lucide-database class="icon" />
                            <div>
                                Database
                                <small class="text-secondary d-block ms-0">File .sql → INSERT into tabel</small>
                            </div>
                        </li>
                        <li class="py-1 d-flex align-items-center gap-2">
                            <x-lucide-archive class="icon" />
                            <div>
                                File Storage
                                <small class="text-secondary d-block ms-0">File .zip → extract ke storage</small>
                            </div>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="subheader">Informasi Penting</h4>
                    <div class="d-flex flex-column gap-2 mt-2">
                        <div class="alert alert-info mb-0 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <x-lucide-shield class="icon" />
                                <small>
                                    <strong>Keamanan:</strong><br>
                                    Statement berbahaya (DROP, ALTER, DELETE) otomatis diblokir.
                                </small>
                            </div>
                        </div>

                        <div class="alert alert-info mb-0 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <x-lucide-database class="icon" />
                                <small>
                                    <strong>Backup Otomatis:</strong><br>
                                    Database saat ini akan di-backup sebelum restore.
                                </small>
                            </div>
                        </div>

                        <div class="alert alert-secondary mb-0 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <x-lucide-terminal class="icon" />
                                <small>
                                    <strong>Alternatif CLI:</strong><br>
                                    <code class="d-block mt-1">
php artisan app:restore-backup --sql=file.sql --storage=file.zip --replace
                                    </code>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .cursor-pointer { cursor: pointer; }
    .hover-bg-light:hover { background-color: var(--tblr-bg-surface-secondary) !important; }
    .transition-all { transition: all 0.2s ease-in-out; }
    .icon-sm { width: 1rem; height: 1rem; }
</style>
