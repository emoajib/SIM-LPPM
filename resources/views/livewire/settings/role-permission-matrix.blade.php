<div class="row row-cards">
    <div class="col-12">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title text-primary d-flex align-items-center mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shield-lock me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                       <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"></path>
                       <path d="M12 11m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                       <path d="M12 12l0 2.5"></path>
                    </svg>
                    Matriks Akses Modul (RBAC)
                </h3>
                <div class="card-actions d-flex gap-2">
                    <button wire:click="resetMatrix" class="btn btn-outline-secondary btn-sm" wire:loading.attr="disabled">
                        
                        Reset
                    </button>
                    <button wire:click="save" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                        
                        Simpan Perubahan
                        <span wire:loading wire:target="save" class="spinner-border spinner-border-sm ms-2" role="status"></span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><circle cx="12" cy="12" r="9"></circle><line x1="12" y1="8" x2="12.01" y2="8"></line><polyline points="11 12 12 12 12 16 13 16"></polyline></svg>
                        </div>
                        <div>
                            <h4 class="alert-title">Sistem Konfigurasi Keamanan</h4>
                            <div class="text-muted">
                                Sesuaikan hak akses setiap peran terhadap modul sistem. 
                                <strong>PENTING:</strong> Klik tombol <strong>Simpan Perubahan</strong> untuk menerapkan setelan ke database. 
                                Pengaturan tidak akan berubah sampai Anda menyimpannya.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mt-4">
                    <table class="table table-vcenter table-hover card-table table-bordered">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="w-1">No</th>
                                <th>Nama Modul / Izin</th>
                                @foreach($roleList as $role)
                                    <th class="text-center text-uppercase" style="min-width: 100px; font-size: 0.75rem;">
                                        {{ str_replace(' lppm', '', $role->name) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permissionList as $index => $permission)
                                @php
                                    $modName = ucwords(str_replace(['module_', '_'], ['', ' '], $permission->name));
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $index + 1 }}</td>
                                    <td class="font-weight-medium">
                                        <div class="d-flex align-items-center">
                                            <span class="status-dot status-dot-animated bg-primary me-2"></span>
                                            {{ $modName }}
                                        </div>
                                    </td>
                                    @foreach($roleList as $role)
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center">
                                                <label class="form-check form-switch m-0">
                                                    <input class="form-check-input" type="checkbox" 
                                                        wire:click="togglePermission('{{ $role->id }}', '{{ $permission->id }}')"
                                                        @if($matrix[$role->id][$permission->id] ?? false) checked @endif
                                                        @if(in_array($role->name, ['superadmin']) || 
                                                            (in_array($role->name, ['admin lppm']) && in_array($permission->name, ['module_pengaturan', 'module_kelola_pengguna']))) 
                                                            disabled 
                                                        @endif
                                                    >
                                                </label>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-transparent text-muted mt-3 d-flex justify-content-between">
                <div wire:loading wire:target="save" class="text-primary small fw-bold">
                    Sedang menyimpan ke server...
                </div>
            </div>
        </div>
    </div>
</div>
