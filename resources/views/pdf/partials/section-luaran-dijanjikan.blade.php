@if($proposal->outputs->count() > 0)
    <div class="section-title">{{ $sectionNum }}. LUARAN DIJANJIKAN</div>
    <table>
        <thead>
            <tr>
                <th>Tahun</th>
                <th>Kelompok Luaran</th>
                <th>Jenis Luaran</th>
                <th>Status Target</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($proposal->outputs as $output)
                <tr>
                    <td class="text-center">{{ $output->output_year }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $output->group)) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $output->type)) }}</td>
                    <td class="text-center">{{ $output->target_status }}</td>
                    <td>{{ $output->description ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
