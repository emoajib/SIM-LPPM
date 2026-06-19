<div class="section-title">{{ $sectionNum }}. ANGGARAN</div>
<p class="mb-0" style="font-size: 8pt;">Rencana Anggaran Biaya {{ $proposal->detailable_type === 'App\Models\Research' ? 'Penelitian' : 'Pengabdian' }} mengacu pada PMK dan buku Panduan Penelitian dan Pengabdian kepada Masyarakat yang berlaku.</p>
@php
    $totalRAB = $proposal->budgetItems->sum('total_price');
    $budgetGroups = $proposal->budgetItems->groupBy(function ($item) {
        return $item->budgetGroup->name ?? ($item->group ?? 'Lainnya');
    });
@endphp
<p class="mt-0"><strong>Total RAB : Rp. {{ number_format($totalRAB, 0, ',', '.') }}</strong></p>

@foreach($budgetGroups as $groupName => $items)
    @php $groupTotal = $items->sum('total_price'); @endphp
    <div class="group-total">
        Total Biaya {{ $groupName }} Rp. {{ number_format($groupTotal, 0, ',', '.') }} 
        ({{ $totalRAB > 0 ? number_format(($groupTotal / $totalRAB) * 100, 2) : 0 }}%)
    </div>
    <table class="table-data">
        <thead>
            <tr>
                <th width="20%">Komponen</th>
                <th width="35%">Item</th>
                <th width="10%">Satuan</th>
                <th width="5%">Vol.</th>
                <th width="15%">Biaya Satuan</th>
                <th width="15%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->budgetComponent->name ?? $item->component }}</td>
                    <td>{{ $item->item_description ?? $item->item_name }}</td>
                    <td class="text-center">{{ $item->budgetComponent->unit ?? ($item->unit ?? '-') }}</td>
                    <td class="text-center">{{ $item->volume }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->total_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach
