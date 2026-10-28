@php
    $isLocal = app()->environment('local');
@endphp

<div>
    @if ($isLocal)
        <div class="row">
            <div class="col-md-8">
                <h3 class="card-title mb-3">
                    
                    Sinkron dari Produksi
                </h3>

                <p class="text-secondary mb-3">
                    Tarik data terbaru dari server produksi ke localhost untuk
                    <strong>backup</strong> dan <strong>pengembangan</strong>.
                </p>

                @if ($configComplete)
                    <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                        
                        <div>Konfigurasi server <strong>{{ $sshUser }}@{{ $sshHost }}</strong> siap.</div>
                    </div>
                @else
                    <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
                        
                        <div>
                            <strong>Konfigurasi server belum lengkap.</strong><br>
                            <small>Minta admin IT untuk mengisi konfigurasi SSH di file <code>.env</code>.</small>
                        </div>
                    </div>
                @endif

                <div class="mb-3">
                    <strong>Sinkronisasi terakhir:</strong>
                    <span class="text-secondary">{{ $lastSync }}</span>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button
                        type="button"
                        wire:click="testConnection"
                        class="btn btn-outline-primary"
                        wire:loading.attr="disabled"
                        @disabled($isRunning || !$configComplete)
                    >
                        
                        Uji Koneksi
                    </button>

                    <button
                        type="button"
                        wire:click="runSync"
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        @disabled($isRunning || !$configComplete)
                    >
                        <span wire:loading.remove>
                            
                            Sinkronkan Data
                        </span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Menyinkronkan...
                        </span>
                    </button>
                </div>

                @if (!empty($output))
                    <div class="mt-4">
                        <h3 class="card-title mb-2">Output Proses</h3>
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
                        <h4 class="subheader">Yang Akan Disinkronkan</h4>
                        <ul class="list-unstyled mb-0 mt-2">
                            <li class="py-1 d-flex align-items-center gap-2">
                                
                                <div>
                                    Database
                                    <small class="text-secondary d-block ms-0">MySQL/PostgreSQL dari produksi</small>
                                </div>
                            </li>
                            <li class="py-1 d-flex align-items-center gap-2">
                                
                                <div>
                                    File Upload / Storage
                                    <small class="text-secondary d-block ms-0">File media, dokumen, dan lampiran</small>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="subheader">Informasi Penting</h4>
                        <div class="d-flex flex-column gap-2 mt-2">
                            <div class="alert alert-info mb-0 py-2">
                                <div class="d-flex align-items-center gap-2">
                                    
                                    <small>
                                        <strong>Untuk Admin Non-IT:</strong><br>
                                        Cukup klik tombol <strong>"Sinkronkan Data"</strong>.
                                        Proses berjalan otomatis.
                                    </small>
                                </div>
                            </div>

                            <div class="alert alert-warning mb-0 py-2">
                                <div class="d-flex align-items-center gap-2">
                                    
                                    <small>
                                        <strong>Perhatian:</strong><br>
                                        Data lokal akan ditimpa. Pastikan tidak ada perubahan penting yang belum tersimpan.
                                    </small>
                                </div>
                            </div>

                            <div class="alert alert-secondary mb-0 py-2">
                                <div class="d-flex align-items-center gap-2">
                                    
                                    <small>
                                        <strong>Konfigurasi SSH:</strong><br>
                                        Admin IT dapat mengisi detail koneksi di file <code>.env</code>:
                                    </small>
                                </div>
                                <code class="d-block mt-1 bg-light p-2 rounded small">
SYNC_SSH_HOST=hostname<br>
SYNC_SSH_USER=username<br>
SYNC_SSH_KEY_PATH=~/.ssh/key
                                </code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-5">
            
            <h4 class="text-secondary">Fitur Sinkron Data</h4>
            <p class="text-secondary">Fitur ini hanya tersedia di lingkungan <strong>Lokal</strong>.</p>
        </div>
    @endif
</div>
