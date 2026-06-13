<div>
    <h3 class="card-title mb-3">
        <x-lucide-toggle-left class="icon me-1" />
        Hierarki Riset & Validasi Kaprodi
    </h3>
    <p class="text-secondary mb-3">
        Pengaturan ini bersifat eksperimental dan dapat mengubah alur (workflow) pengajuan proposal secara
        real-time. Pastikan SK/Kebijakan Rektorat telah terbit sebelum mengaktifkan fitur ini.
    </p>

    <div class="list-group list-group-flush">
        <div class="list-group-item px-0">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar bg-blue-lt">
                        <x-lucide-git-branch class="icon" />
                    </span>
                </div>
                <div class="col text-truncate">
                    <div class="text-body d-block font-weight-medium">Pohon Penelitian & Peta Jalan (Roadmap)</div>
                    <div class="text-muted text-truncate mt-n1">
                        Mewajibkan Dosen memilih cabang keilmuan Prodi saat pengajuan proposal, serta mengaktifkan Dasbor Roadmap di level Prodi dan Fakultas.
                    </div>
                </div>
                <div class="col-auto">
                    <label class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" wire:model.live="featureRoadmapActive">
                    </label>
                </div>
            </div>
        </div>

        <div class="list-group-item px-0">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar bg-green-lt">
                        <x-lucide-check-square class="icon" />
                    </span>
                </div>
                <div class="col text-truncate">
                    <div class="text-body d-block font-weight-medium">Validasi Berjenjang: Kaprodi</div>
                    <div class="text-muted text-truncate mt-n1">
                        Dekan tidak dapat melakukan "Setujui" sebelum Kaprodi memberikan stempel validasi pada proposal yang diajukan. (Sistem Check-Point).
                    </div>
                </div>
                <div class="col-auto">
                    <label class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" wire:model.live="featureKaprodiValidation">
                    </label>
                </div>
            </div>
        </div>

        <div class="list-group-item px-0">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar bg-purple-lt">
                        <x-lucide-handshake class="icon" />
                    </span>
                </div>
                <div class="col text-truncate">
                    <div class="text-body d-block font-weight-medium">Mitra Wajib (Pengabdian Masyarakat)</div>
                    <div class="text-muted text-truncate mt-n1">
                        Mewajibkan dosen menambahkan minimal 1 mitra pada proposal Pengabdian Masyarakat.
                    </div>
                </div>
                <div class="col-auto">
                    <label class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" wire:model.live="featureCommunityPartnerRequired">
                    </label>
                </div>
            </div>
        </div>

        <div class="list-group-item px-0">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar bg-orange-lt">
                        <x-lucide-users class="icon" />
                    </span>
                </div>
                <div class="col text-truncate">
                    <div class="text-body d-block font-weight-medium">Jumlah Reviewer per Proposal</div>
                    <div class="text-muted text-truncate mt-n1">
                        Menentukan jumlah minimal reviewer yang wajib ditugaskan ke setiap usulan proposal.
                    </div>
                </div>
                <div class="col-auto">
                    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                    <select class="form-select form-select-sm" style="width: auto;" wire:model.live="reviewerCountRequired">
                        <option value="1">1 Reviewer</option>
                        <option value="2">2 Reviewer</option>
                        <option value="3">3 Reviewer</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <h3 class="card-title mb-3 mt-4">
        <x-lucide-mail class="icon me-1" />
        Modul Persuratan Terintegrasi
    </h3>
    <p class="text-secondary mb-3">
        Kelola aktivasi modul surat tugas dan surat izin serta metode penandatanganan resmi LPPM.
    </p>

    <div class="list-group list-group-flush">
        <div class="list-group-item px-0">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar bg-azure-lt">
                        <x-lucide-toggle-right class="icon" />
                    </span>
                </div>
                <div class="col text-truncate">
                    <div class="text-body d-block font-weight-medium">Status Modul Persuratan</div>
                    <div class="text-muted text-truncate mt-n1">
                        Aktifkan untuk memunculkan menu persuratan bagi Dosen dan Kepala LPPM.
                    </div>
                </div>
                <div class="col-auto">
                    <label class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" wire:model.live="modulePersuratanActive">
                    </label>
                </div>
            </div>
        </div>

        <div class="list-group-item px-0">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar bg-indigo-lt">
                        <x-lucide-pen-tool class="icon" />
                    </span>
                </div>
                <div class="col text-truncate">
                    <div class="text-body d-block font-weight-medium">Metode Tanda Tangan Utama</div>
                    <div class="text-muted text-truncate mt-n1">
                        Pilih antara tanda tangan digital dengan QR Code atau ruang kosong untuk tanda tangan basah.
                    </div>
                </div>
                <div class="col-auto">
                    <select class="form-select form-select-sm" style="width: auto;" wire:model.live="suratSignatureMode">
                        <option value="tte">TTE (QR Code / Barcode)</option>
                        <option value="manual">Tanda Tangan Basah (Cetak)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
