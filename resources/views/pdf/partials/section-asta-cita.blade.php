@if(isset($proposal->asta_cita) && $proposal->asta_cita)
    <div class="section-title">{{ $sectionNum }}. Asta Cita</div>
    <div style="margin-left: 20px; text-align: justify;">{{ $proposal->asta_cita }}</div>
@endif
