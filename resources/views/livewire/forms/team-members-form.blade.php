<div>
    <!-- Members List -->
    @if (!empty($members))
        <div class="mb-4">
            <div class="table-responsive">
                <table class="table-hover table-sm table">
                    <thead>
                        <tr>
                            <th>NAMA / NIDN</th>
                            <th>Tugas</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $index => $member)
                            <tr wire:key="member-{{ $index }}">
                                <td class="align-middle">
                                    {{ $member['name'] }}<br />
                                    <small class="text-muted"><code>{{ $member['nidn'] }}</code></small>
                                    @if (!empty($member['sinta_id']))
                                        <br /><small class="text-muted">Sinta ID:
                                            <code>{{ $member['sinta_id'] }}</code></small>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($editingIndex === $index)
                                        <textarea wire:model="editingTugas"
                                            class="form-control form-control-sm @error('editingTugas') is-invalid @enderror"
                                            rows="2"></textarea>
                                        @error('editingTugas') <small class="text-danger">{{ $message }}</small> @enderror
                                    @else
                                        {{ $member['tugas'] }}
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if (($member['status'] ?? 'pending') === 'accepted')
                                        <x-tabler.badge color="success">Diterima</x-tabler.badge>
                                    @elseif (($member['status'] ?? 'pending') === 'rejected')
                                        <x-tabler.badge color="danger">Ditolak</x-tabler.badge>
                                    @else
                                        <x-tabler.badge color="warning">Menunggu</x-tabler.badge>
                                    @endif
                                </td>
                                <td class="text-end align-middle">
                                    @if($editingIndex === $index)
                                        <button type="button" wire:click="saveEditingMember" class="btn btn-sm btn-success"
                                            title="Simpan">
                                            <x-lucide-check class="icon" />
                                        </button>
                                        <button type="button" wire:click="cancelEditingMember"
                                            class="btn btn-sm btn-outline-secondary" title="Batal">
                                            <x-lucide-x class="icon" />
                                        </button>
                                    @else
                                        <button type="button" wire:click="startEditingMember({{ $index }})"
                                            class="btn-outline-primary btn btn-sm me-1" title="Edit Tugas">
                                            <x-lucide-edit class="icon me-1" /> Edit
                                        </button>
                                        <button type="button" wire:click="openDeleteModal({{ $index }})"
                                            class="btn-outline-danger btn btn-sm" title="Hapus">
                                            <x-lucide-trash-2 class="icon" />
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Add Button -->
    <button type="button" class="btn btn-primary" wire:click="openAddModal" data-bs-toggle="modal"
        data-bs-target="#modal-add-member">
        <x-lucide-plus class="icon" />
        Tambah Anggota
    </button>

    @error('members')
        <div class="d-block text-danger mt-2">{{ $message }}</div>
    @enderror

    <!-- Add Member Modal -->
    @teleport('body')
    <x-tabler.modal id="modal-add-member" :title="$modalTitle" on-show="resetMemberForm"
        component-id="{{ $this->getId() }}">
        <x-slot:body>
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <label class="form-label mb-0" for="member_nidn">NIDN / NIP / NIM <span
                            class="text-danger">*</span></label>
                    <button type="button" class="btn btn-ghost-primary btn-sm" wire:click="toggleManual">
                        <x-lucide-user-plus class="icon me-1" />
                        {{ $isManual ? 'Kembali ke Cari' : 'Input Manual (Dosen/Eksternal/Mahasiswa)' }}
                    </button>
                </div>

                @if(!$isManual)
                    <div class="input-group">
                        <input id="member_nidn" type="text" class="form-control @error('member_nidn') is-invalid @enderror"
                            wire:model.live="member_nidn" placeholder="Masukkan NIDN, NIP, atau NIM">
                        <button class="btn-outline-primary btn" type="button" wire:click="checkMember" id="button-addon2">
                            <x-lucide-search class="icon" />
                            Cek
                        </button>
                    </div>
                    @error('member_nidn')
                        <div class="d-block invalid-feedback">{{ $message }}</div>
                    @enderror
                @else
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">NIDN / NIP / NIM <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('member_nidn') is-invalid @enderror"
                                wire:model="member_nidn" placeholder="NIM untuk mahasiswa, NIDN/NIP untuk dosen">
                            <small class="text-muted">Gunakan NIM untuk mahasiswa, NIDN/NIP untuk dosen/eksternal.</small>
                            @error('member_nidn') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Tipe Peneliti <span class="text-danger">*</span></label>
                            <select class="form-select @error('member_type') is-invalid @enderror" wire:model="member_type">
                                <option value="dosen">Anggota Eksternal</option>
                                <option value="mahasiswa">Mahasiswa</option>
                            </select>
                            @error('member_type') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-12 mt-2">
                            <label class="form-label small">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('member_name') is-invalid @enderror"
                                wire:model="member_name" placeholder="Nama lengkap beserta gelar jika ada">
                            @error('member_name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-12 mt-2">
                            <label class="form-label small">Email (opsional)</label>
                            <input type="email" class="form-control @error('member_email') is-invalid @enderror"
                                wire:model="member_email" placeholder="Alamat email aktif (boleh dikosongkan)">
                            <small class="text-muted">Jika kosong, sistem membuat email internal agar anggota tetap tersimpan.</small>
                            @error('member_email') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label small">Institusi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('member_institution') is-invalid @enderror"
                                wire:model="member_institution" placeholder="Nama Institusi">
                            @error('member_institution') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label small">Program Studi <span class="text-danger">*</span></label>
                            <select class="form-select @error('member_study_program') is-invalid @enderror"
                                wire:model="member_study_program">
                                <option value="">-- Pilih Prodi --</option>
                                @foreach($this->studyPrograms as $prodi)
                                    <option value="{{ $prodi->name }}">{{ $prodi->name }}</option>
                                @endforeach
                                <option value="Lainnya">Lainnya...</option>
                            </select>
                            @error('member_study_program') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                @endif
            </div>

            @if (!$isManual && $memberFound && $foundMember)
                <div class="alert alert-success mb-3">
                    <div class="alert-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon alert-icon icon-2">
                            <path d="M5 12l5 5l10 -10"></path>
                        </svg>
                    </div>

                    <dl class="row g-2 small mb-0">
                        <div class="col-12 mb-2"><strong>Anggota Ditemukan:</strong></div> <br>
                        <dt class="text-bold col-12 col-sm-4">Nama</dt>
                        <dd class="col-12 col-sm-8">{{ $foundMember['name'] }}</dd>

                        <dt class="text-bold col-12 col-sm-4">NUPTK/NIDN</dt>
                        <dd class="col-12 col-sm-8">
                            {{ $foundMember['nidn'] }}
                        </dd>

                        @if (!empty($foundMember['institution']))
                            <dt class="text-bold col-12 col-sm-4">Institusi</dt>
                            <dd class="col-12 col-sm-8">{{ $foundMember['institution'] }}</dd>
                        @endif

                        @if (!empty($foundMember['study_program']))
                            <dt class="text-bold col-12 col-sm-4">Program Studi</dt>
                            <dd class="col-12 col-sm-8">{{ $foundMember['study_program'] }}</dd>
                        @endif

                        <dt class="text-bold col-12 col-sm-4">Tipe Identitas</dt>
                        <dd class="col-12 col-sm-8">{{ $foundMember['identity_type'] }}</dd>
                    </dl>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label" for="member_tugas">Tugas <span class="text-danger">*</span></label>
                <textarea id="member_tugas" @class([
                    'form-control',
                    'is-invalid' => $errors->has('member_tugas'),
                    'disabled' => !$memberFound,
                ]) wire:model="member_tugas" rows="3"
                    placeholder="Jelaskan tugas anggota dalam penelitian ini" {{ !$memberFound ? 'disabled' : '' }}
                    required></textarea>
                @error('member_tugas')
                    <div class="d-block invalid-feedback">{{ $message }}</div>
                @enderror
                @if (!$memberFound && !$isManual)
                    <small class="text-muted">Cek NIDN/NIP/NIM terlebih dahulu untuk mengisi tugas</small>
                @endif
            </div>
        </x-slot:body>

        <x-slot:footer>
            <button type="button" class="btn-outline-secondary btn" data-bs-dismiss="modal">
                Batal
            </button>
            <button type="button" wire:click="addMember" class="btn btn-primary" {{ !$memberFound ? 'disabled' : '' }}>
                <x-lucide-plus class="icon" />
                Tambah
            </button>
        </x-slot:footer>
    </x-tabler.modal>
    @endteleport

    <!-- Delete Confirmation Modals -->
    @if (!empty($members))
        @foreach ($members as $index => $member)
            @teleport('body')
            <x-tabler.modal id="modal-confirm-delete-{{ $index }}" title="Konfirmasi Hapus" component-id="{{ $this->getId() }}">
                <x-slot:body>
                    <div class="text-center">
                        <div class="mb-3">
                            <x-lucide-alert-triangle class="text-danger" style="width: 64px; height: 64px;" />
                        </div>
                        <h3 class="mb-2">Hapus Anggota?</h3>
                        <p class="text-muted">
                            Apakah Anda yakin ingin menghapus <strong>{{ $member['name'] }}</strong> dari daftar
                            {{ strtolower($memberLabel) }}?
                        </p>
                        <p class="text-danger small mb-0">
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </x-slot:body>

                <x-slot:footer>
                    <button type="button" class="btn-outline-secondary btn" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="button" wire:click="removeMember({{ $index }})" class="btn btn-danger"
                        data-bs-dismiss="modal">
                        <x-lucide-trash-2 class="icon" />
                        Ya, Hapus
                    </button>
                </x-slot:footer>
            </x-tabler.modal>
            @endteleport
        @endforeach
    @endif
</div>