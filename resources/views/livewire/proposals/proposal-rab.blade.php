<div>
    <div class="mb-3 card">
        <div class="card-header">
            <h3 class="card-title">3.1 Rencana Anggaran Biaya (RAB)</h3>
        </div>
        @if ($proposal->budgetItems->isEmpty())
            <div class="card-body">
                <p class="text-muted">Belum ada item anggaran</p>
            </div>
        @else
            @php
                $startYear = (int) ($proposal->start_year ?? date('Y'));
                $duration = (int) ($proposal->duration_in_years ?? 1);
                $budgetByYear = $proposal->budgetItems->groupBy('year');
            @endphp

            {{-- Year Summary Cards for Multi-Year Proposals --}}
            @if ($duration > 1)
                <div class="pb-0 card-body">
                    <div class="mb-3 row g-2">
                        @for ($y = 1; $y <= $duration; $y++)
                            @php
                                $yearTotal = $budgetByYear->get($y, collect())->sum('total_price');
                                $actualYear = $startYear + $y - 1;
                            @endphp
                            <div class="col-auto">
                                <div class="card card-sm">
                                    <div class="px-3 py-2 card-body">
                                        <div class="text-muted small">Tahun {{ $y }}
                                            ({{ $actualYear }})</div>
                                        <div class="fw-bold">Rp {{ number_format($yearTotal, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endfor
                        <div class="col-auto">
                            <div class="bg-primary-lt card card-sm">
                                <div class="px-3 py-2 card-body">
                                    <div class="text-muted small">Total Keseluruhan</div>
                                    <div class="fw-bold">Rp
                                        {{ number_format($proposal->activeBudgetItems->sum('total_price'), 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="table-responsive">
                <table class="card-table table table-bordered">
                    <thead>
                        <tr>
                            @if ($duration > 1)
                                <th style="width: 80px;">Tahun Ke-</th>
                            @endif
                            <th>Kelompok</th>
                            <th>Komponen</th>
                            <th>Item</th>
                            <th>Satuan</th>
                            <th>Volume</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proposal->budgetItems as $item)
                            @php
                                $itemYear = $item->year ?? 1;
                                $displayYear = $startYear + $itemYear - 1;
                            @endphp
                            <tr>
                                @if ($duration > 1)
                                    <td class="text-center">{{ $itemYear }} ({{ $displayYear }})</td>
                                @endif
                                <td>{{ $item->budgetGroup?->name ?? ($item->group ?? '-') }}</td>
                                <td>{{ $item->budgetComponent?->name ?? ($item->component ?? '-') }}</td>
                                <td>{{ $item->item_description ?? '-' }}</td>
                                <td><x-tabler.badge>{{ $item->budgetComponent?->unit ?? '-' }}</x-tabler.badge>
                                </td>
                                <td>{{ $item->volume }}</td>
                                <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="text-end">Rp {{ number_format($item->total_price, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="{{ $duration > 1 ? 7 : 6 }}" class="text-end">Total Anggaran:</th>
                            <th class="text-end">Rp
                                {{ number_format($proposal->activeBudgetItems->sum('total_price'), 0, ',', '.') }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
