<?php

namespace App\Imports;

use App\Enums\ProposalStatus;
use App\Enums\ProposalUserStatus;
use App\Models\CommunityService;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ResearchScheme;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HistoricalProposalImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    /** @var array Baris gagal beserta alasannya */
    public array $failures = [];

    /** @var int Total baris berhasil diimport */
    public int $imported = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            // ── Validasi field wajib ──────────────────────────────────────────
            if (empty(trim((string) ($row['judul'] ?? '')))) {
                $this->failures[] = "Baris {$rowNum}: Kolom 'judul' kosong, dilewati.";

                continue;
            }
            if (empty(trim((string) ($row['skema'] ?? '')))) {
                $this->failures[] = "Baris {$rowNum}: Kolom 'skema' kosong, dilewati.";

                continue;
            }
            if (empty($row['tahun'])) {
                $this->failures[] = "Baris {$rowNum}: Kolom 'tahun' kosong, dilewati.";

                continue;
            }

            // ── Cari Dosen Ketua ─────────────────────────────────────────────
            $ketua = $this->findUser(
                trim((string) ($row['nidn'] ?? '')),
                trim((string) ($row['nama_dosen'] ?? ''))
            );

            if (! $ketua) {
                $info = trim((string) ($row['nidn'] ?? '')) ?: trim((string) ($row['nama_dosen'] ?? '')) ?: 'tidak ada info';
                $this->failures[] = "Baris {$rowNum}: Dosen ketua '{$info}' tidak ditemukan, dilewati.";

                continue;
            }

            // ── Proses Anggota Dosen ─────────────────────────────────────────
            // Format kolom: "nidn_anggota1,nidn_anggota2" atau satu NIDN
            // Support multi-kolom: nidn_anggota_1, nidn_anggota_2, dst.
            $dosenAnggota = $this->parseDosenAnggota($row);

            // ── Proses Anggota Mahasiswa ─────────────────────────────────────
            // Format kolom: "nama_mahasiswa1|nim1,nama_mahasiswa2|nim2"
            // Contoh: "Ahmad Rizki|220102001,Siti Nur|220102002"
            $studentMembers = $this->parseStudentMembers($row);

            // ── Research Scheme lookup ────────────────────────────────────────
            $researchSchemeId = null;
            if (! empty($row['nama_skema'])) {
                $scheme = ResearchScheme::where('name', 'like', '%'.trim((string) $row['nama_skema']).'%')->first();
                $researchSchemeId = $scheme?->id;
            }

            // ── Nilai dana, bersihkan non-angka ──────────────────────────────
            $dana = (float) preg_replace('/[^0-9]/', '', (string) ($row['dana'] ?? '0'));
            $durasi = max(1, (int) ($row['lama_kegiatan'] ?? 1));

            // ── Wrap semua dalam satu transaction ────────────────────────────
            try {
                DB::transaction(function () use ($row, $ketua, $dosenAnggota, $studentMembers, $researchSchemeId, $dana, $durasi) {
                    // 1. Buat detail record (Research / CommunityService)
                    $type = strtolower(trim((string) $row['skema']));
                    $isAbmas = str_contains($type, 'abmas')
                        || str_contains($type, 'pengabdian')
                        || str_contains($type, 'pkm');

                    if ($isAbmas) {
                        $detail = CommunityService::create([
                            'partner_issue_summary' => trim((string) ($row['ringkasan'] ?? 'Data historis import')),
                        ]);
                    } else {
                        $detail = Research::create([
                            'background' => trim((string) ($row['ringkasan'] ?? 'Data historis import')),
                        ]);
                    }

                    // 2. Buat Proposal (HasUuids auto-generates UUID)
                    $proposal = Proposal::create([
                        'title' => trim((string) $row['judul']),
                        'submitter_id' => $ketua->id,
                        'detailable_type' => get_class($detail),
                        'detailable_id' => $detail->id,
                        'research_scheme_id' => $researchSchemeId,
                        'start_year' => (int) $row['tahun'],
                        'duration_in_years' => $durasi,
                        'status' => ProposalStatus::COMPLETED,
                        'sbk_value' => $dana,
                        'summary' => trim((string) ($row['ringkasan'] ?? 'Imported from Excel')),
                        // Anggota mahasiswa disimpan sebagai JSON
                        'student_members' => ! empty($studentMembers) ? $studentMembers : null,
                    ]);

                    // 3. Tambah Ketua ke proposal_user (role: ketua, status: accepted)
                    $proposal->teamMembers()->attach($ketua->id, [
                        'role' => 'ketua',
                        'status' => ProposalUserStatus::ACCEPTED->value,
                        'tasks' => 'Ketua Peneliti',
                    ]);

                    // 4. Tambah Anggota Dosen ke proposal_user (role: anggota, status: accepted)
                    foreach ($dosenAnggota as $anggota) {
                        // Hindari duplikasi jika NIDN anggota = NIDN ketua
                        if ($anggota->id === $ketua->id) {
                            continue;
                        }

                        $proposal->teamMembers()->syncWithoutDetaching([
                            $anggota->id => [
                                'role' => 'anggota',
                                'status' => ProposalUserStatus::ACCEPTED->value,
                                'tasks' => null,
                            ],
                        ]);
                    }
                });

                $this->imported++;
            } catch (\Exception $e) {
                $judul = trim((string) ($row['judul'] ?? ''));
                $this->failures[] = "Baris {$rowNum} [{$judul}]: Gagal simpan — ".$e->getMessage();
            }
        }
    }

    /**
     * Cari User berdasarkan NIDN (via Identity) atau Nama.
     */
    private function findUser(string $nidn, string $nama): ?User
    {
        if (! empty($nidn)) {
            $user = User::whereHas('identity', fn ($q) => $q->where('nidn', $nidn))->first();
            if ($user) {
                return $user;
            }
        }

        if (! empty($nama)) {
            return User::where('name', 'like', '%'.$nama.'%')->first();
        }

        return null;
    }

    /**
     * Parsing anggota dosen dari kolom nidn_anggota (koma-separated NIDN).
     * Support multi-value: "0101010101,0202020202"
     *
     * @return User[]
     */
    private function parseDosenAnggota(Collection $row): array
    {
        $found = [];
        $raw = trim((string) ($row['nidn_anggota'] ?? ''));

        if (empty($raw)) {
            return $found;
        }

        $nidnList = array_filter(array_map('trim', explode(',', $raw)));

        foreach ($nidnList as $nidn) {
            $user = User::whereHas('identity', fn ($q) => $q->where('nidn', $nidn))->first();
            if ($user) {
                $found[] = $user;
            }
        }

        return $found;
    }

    /**
     * Parsing anggota mahasiswa dari kolom anggota_mahasiswa.
     * Format: "Nama Mahasiswa|NIM,Nama Mahasiswa2|NIM2"
     * Contoh: "Ahmad Rizki|220102001,Siti Nur|220102002"
     *
     * @return array JSON-compatible array untuk kolom student_members
     */
    private function parseStudentMembers(Collection $row): array
    {
        $result = [];
        $raw = trim((string) ($row['anggota_mahasiswa'] ?? ''));

        if (empty($raw)) {
            return $result;
        }

        $entries = array_filter(array_map('trim', explode(',', $raw)));

        foreach ($entries as $entry) {
            // Format: "Nama Mahasiswa|NIM" atau hanya "Nama Mahasiswa"
            $parts = array_map('trim', explode('|', $entry));
            $result[] = [
                'name' => $parts[0], // Offset 0 always exists after explode
                'nim' => $parts[1] ?? null,
                'tasks' => $parts[2] ?? null, // opsional: tugas mahasiswa
            ];
        }

        return $result;
    }
}
