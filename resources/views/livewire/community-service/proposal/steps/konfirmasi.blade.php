<!-- Section: Konfirmasi -->
<div class="mb-3 card">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <x-lucide-check-circle class="me-3 icon" />
            <h3 class="mb-0 card-title">Konfirmasi Proposal</h3>
        </div>

        <div class="alert alert-info">
            <x-lucide-info class="me-2 icon" />
            Silakan periksa kembali seluruh data proposal Anda sebelum mengirimkan.
        </div>

        <!-- Ringkasan Step 1: Identitas Usulan -->
        <div class="mb-3 card">
            <div class="card-header">
                <h4 class="mb-0 card-title">1. Identitas Usulan</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <tr>
                            <td width="30%"><strong>Judul Proposal</strong></td>
                            <td>{{ $form->title }}</td>
                        </tr>
                        <tr>
                            <td><strong>Periode Pelaksanaan</strong></td>
                            <td>
                                @if ($form->academic_year && $form->duration_in_years)
                                    {{ $form->academic_year }} ({{ $form->duration_in_years }} Tahun)
                                @else
                                    {{ $form->duration_in_years }} Tahun
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Bidang Fokus</strong></td>
                            <td>{{ $this->focusAreas->find($form->focus_area_id)?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tema</strong></td>
                            <td>{{ $this->themes->find($form->theme_id)?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Topik</strong></td>
                            <td>{{ $this->topics->find($form->topic_id)?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Klaster Sains Level 1</strong></td>
                            <td>{{ $this->scienceClusters->find($form->cluster_level1_id)?->name ?? '-' }}</td>
                        </tr>
                        @if($form->cluster_level2_id)
                        <tr>
                            <td><strong>Klaster Sains Level 2</strong></td>
                            <td>{{ $this->scienceClusters->find($form->cluster_level2_id)?->name ?? '-' }}</td>
                        </tr>
                        @endif
                        @if($form->cluster_level3_id)
                        <tr>
                            <td><strong>Klaster Sains Level 3</strong></td>
                            <td>{{ $this->scienceClusters->find($form->cluster_level3_id)?->name ?? '-' }}</td>
                        </tr>
                        @endif
                        @if($form->national_priority_id)
                        <tr>
                            <td><strong>Prioritas Nasional</strong></td>
                            <td>{{ $this->nationalPriorities->find($form->national_priority_id)?->name ?? '-' }}</td>
                        </tr>
                        @endif
                        @if($form->sbk_value > 0)
                        <tr>
                            <td><strong>Nilai SBK</strong></td>
                            <td>Rp {{ number_format($form->sbk_value, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td><strong>Ringkasan</strong></td>
                            <td>{{ Str::limit($form->summary, 500) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Masalah Mitra</strong></td>
                            <td>{{ Str::limit($form->partner_issue_summary, 500) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Solusi</strong></td>
                            <td>{{ Str::limit($form->solution_offered, 500) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Ketua Peneliti</strong></td>
                            <td>{{ $author_name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tugas Ketua</strong></td>
                            <td>{{ $form->author_tasks }}</td>
                        </tr>
                        <tr>
                            <td><strong>Anggota Tim</strong></td>
                            <td>
                                @if(empty($form->members))
                                    <span class="text-muted">Tidak ada anggota</span>
                                @else
                                    <ul class="mb-0 ps-3">
                                        @foreach($form->members as $member)
                                            <li>{{ $member['name'] }} ({{ $member['nidn'] }}) - {{ $member['tugas'] }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ringkasan Step 2: Substansi Usulan -->
        <div class="mb-3 card">
            <div class="card-header">
                <h4 class="mb-0 card-title">2. Substansi Usulan</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <tr>
                            <td width="30%"><strong>Kelompok Makro Riset</strong></td>
                            <td>{{ $this->macroResearchGroups->find($form->macro_research_group_id)?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>File Substansi</strong></td>
                            <td>
                                @if ($form->substance_file && !is_string($form->substance_file))
                                    <x-lucide-file-check class="text-success icon" />
                                    {{ $form->substance_file->getClientOriginalName() }}
                                @elseif ($form->proposal && $form->proposal->detailable && $form->proposal->detailable->hasMedia('substance_file'))
                                    @php
                                        $media = $form->proposal->detailable->getFirstMedia('substance_file');
                                    @endphp
                                    <x-lucide-file-check class="text-success icon" />
                                    {{ $media->name }} <small class="text-muted">(Lama)</small>
                                @else
                                    <span class="text-muted">Tidak ada file</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Jumlah Luaran Target</strong></td>
                            <td>{{ count($form->outputs) }} luaran</td>
                        </tr>
                    </table>
                </div>

                @if (!empty($form->outputs))
                    <h5 class="mt-3">Luaran Target Capaian:</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Tahun</th>
                                    <th>Jenis</th>
                                    <th>Kategori Luaran</th>
                                     <th>Luaran</th>
                                    <th>Status</th>
                                    <th>Keterangan (URL)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($form->outputs as $output)
                                    <tr>
                                        <td>{{ $output['year'] ?? 1 }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $output['category'] ?? '-')) }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $output['group'] ?? '-')) }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $output['type'] ?? '-')) }}</td>
                                        <td>{{ $output['status'] ?? '-' }}</td>
                                        <td>{{ $output['description'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Ringkasan Step 3: RAB -->
        <div class="mb-3 card">
            <div class="card-header">
                <h4 class="mb-0 card-title">3. Rencana Anggaran Biaya (RAB)</h4>
            </div>
            <div class="card-body">
                @if (empty($form->budget_items))
                    <p class="text-muted">Belum ada item anggaran</p>
                @else
                    @php
                        $budgetItems = collect($form->budget_items);
                        $budgetByYear = $budgetItems->groupBy('year');
                        $duration = (int) ($form->duration_in_years ?? 1);
                        $startYear = \App\Models\Proposal::parseAcademicYear($form->academic_year) ?? (int) date('Y');
                    @endphp

                    {{-- Year Summary Cards for Multi-Year Proposals --}}
                    @if ($duration > 1)
                        <div class="row g-2 mb-3">
                            @for ($y = 1; $y <= $duration; $y++)
                                @php
                                    $yearTotal = $budgetByYear->get($y, collect())->sum('total');
                                    $actualYear = $startYear + $y - 1;
                                @endphp
                                <div class="col-auto">
                                    <div class="card card-sm">
                                        <div class="card-body py-2 px-3">
                                            <div class="text-muted small">Tahun ke-{{ $y }} ({{ $actualYear }}/{{ $actualYear + 1 }})</div>
                                            <div class="fw-bold">Rp {{ number_format($yearTotal, 0, ',', '.') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endfor
                            <div class="col-auto">
                                <div class="card card-sm bg-primary-lt">
                                    <div class="card-body py-2 px-3">
                                        <div class="text-muted small">Total Keseluruhan</div>
                                        <div class="fw-bold">Rp {{ number_format($budgetItems->sum('total'), 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Budget Group Summary --}}
                    @php
                        $konfSchemeId = $form->research_scheme_id ?: $form->community_service_scheme_id;
                        $konfBudgetCap = \App\Models\BudgetCap::getCapForPeriod(
                            (int) ($form->start_year ?: date('Y')),
                            $form->semester ?: 'ganjil',
                            $form->research_scheme_id ? 'research' : 'community_service',
                            $konfSchemeId ? (int) $konfSchemeId : null
                        );
                        $konfGroupTotals = $budgetItems->groupBy('budget_group_id')->map(fn($items) => $items->sum('total'));
                        $konfTotalBudget = $budgetItems->sum('total');
                    @endphp
                    @if ($konfBudgetCap > 0 || $konfTotalBudget > 0)
                        <div class="card card-sm mb-3">
                            <div class="card-body">
                                <h6>Ringkasan Alokasi per Kelompok Anggaran</h6>
                                @if (!$konfBudgetCap)
                                    <div class="alert alert-warning py-1 small mb-2">
                                        <x-lucide-alert-triangle class="icon alert-icon" />
                                        Batas anggaran belum ditetapkan Admin LPPM. Persentase ditampilkan terhadap total RAB.
                                    </div>
                                @endif
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Kelompok</th>
                                                <th class="text-end">Anggaran (Rp)</th>
                                                <th class="text-end">{{ $konfBudgetCap ? '% dari Cap' : '% dari Total RAB' }}</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($this->budgetGroups->whereNotNull('percentage') as $group)
                                                @php
                                                    $gTotal = $konfGroupTotals[$group->id] ?? 0;
                                                    $gPct = $konfBudgetCap > 0 ? ($gTotal / $konfBudgetCap) * 100 : ($konfTotalBudget > 0 ? ($gTotal / $konfTotalBudget) * 100 : 0);
                                                    $gAllowed = (float) $group->percentage;
                                                    $gIsMinimum = $group->code === 'TEKNOLOGI';
                                                    $gIsViolation = $gIsMinimum ? $gPct < $gAllowed : $gPct > $gAllowed;
                                                @endphp
                                                <tr>
                                                    <td>{{ $group->name }}</td>
                                                    <td class="text-end">Rp {{ number_format($gTotal, 0, ',', '.') }}</td>
                                                    <td class="text-end">{{ number_format($gPct, 1) }}%</td>
                                                    <td class="text-center">
                                                        @if ($gTotal > 0)
                                                            <x-tabler.badge :color="$gIsViolation ? 'danger' : 'success'">
                                                                {{ $gIsViolation ? 'Tidak Sesuai' : 'Sesuai' }}
                                                            </x-tabler.badge>
                                                        @else
                                                            <x-tabler.badge color="secondary">Belum Diisi</x-tabler.badge>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    @if ($duration > 1)
                                        <th style="width: 80px;">Tahun Ke-</th>
                                    @endif
                                    <th>Kelompok</th>
                                    <th>Komponen</th>
                                    <th>Item</th>
                                    <th>Volume</th>
                                    <th class="text-end">Harga Satuan</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($form->budget_items as $item)
                                    @php
                                        $itemYear = $item['year'] ?? 1;
                                        $displayYear = $startYear + $itemYear - 1;
                                    @endphp
                                    <tr>
                                        @if ($duration > 1)
                                            <td class="text-center">{{ $itemYear }} ({{ $displayYear }})</td>
                                        @endif
                                        <td>
                                            @if (!empty($item['budget_group_id']))
                                                {{ $this->budgetGroups->find($item['budget_group_id'])?->name ?? $item['group'] ?? '-' }}
                                            @else
                                                {{ $item['group'] ?? '-' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (!empty($item['budget_component_id']))
                                                {{ $this->budgetComponents->find($item['budget_component_id'])?->name ?? $item['component'] ?? '-' }}
                                            @else
                                                {{ $item['component'] ?? '-' }}
                                            @endif
                                        </td>
                                        <td>{{ $item['item'] ?? '-' }}</td>
                                        <td>{{ $item['volume'] ?? 0 }} {{ $item['unit'] ?? '' }}</td>
                                        <td class="text-end">Rp {{ number_format($item['unit_price'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($item['total'] ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="{{ $duration > 1 ? 6 : 5 }}" class="text-end">Total Anggaran:</th>
                                    <th class="text-end">Rp {{ number_format($budgetItems->sum('total'), 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Ringkasan Step 4: Dokumen Pendukung -->
        <div class="mb-3 card">
            <div class="card-header">
                <h4 class="mb-0 card-title">4. Dokumen Pendukung (Mitra)</h4>
            </div>
            <div class="card-body">
                @if (empty($form->partner_ids))
                    <p class="text-muted">Belum ada mitra yang ditambahkan</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Nama Mitra</th>
                                    <th>Email</th>
                                    <th>Institusi</th>
                                    <th>Tipe</th>
                                    <th>Negara</th>
                                    <th>Alamat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($form->partner_ids as $partnerId)
                                    @php
                                        $partner = $this->partners->find($partnerId);
                                    @endphp
                                    @if ($partner)
                                        <tr>
                                            <td>{{ $partner->name }}</td>
                                            <td>{{ $partner->email ?? '-' }}</td>
                                            <td>{{ $partner->institution ?? '-' }}</td>
                                            <td>{{ $partner->type ?? '-' }}</td>
                                            <td>{{ $partner->country ?? '-' }}</td>
                                            <td>{{ $partner->address ?? '-' }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="alert alert-warning">
            <x-lucide-alert-triangle class="me-2 icon" />
            <strong>Perhatian:</strong> Setelah Anda mengirimkan proposal, data akan disimpan sebagai draft.
            Anda masih dapat mengedit proposal sebelum melakukan pengiriman final.
        </div>
    </div>
</div>
