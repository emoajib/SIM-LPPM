@if(!empty($pdfConfig['intro_text'] ?? null))
    <div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; text-align: justify; font-size: 9pt;">
        {!! nl2br(e($pdfConfig['intro_text'])) !!}
    </div>
@endif

@if(!empty($pdfConfig['approval_custom_text'] ?? null))
    <div style="margin-top: 20px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; text-align: center; font-size: 9pt;">
        {!! nl2br(e($pdfConfig['approval_custom_text'])) !!}
    </div>
@endif

@if(!empty($pdfConfig['outro_text'] ?? null))
    <div style="margin-top: 15px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; text-align: justify; font-size: 9pt;">
        {!! nl2br(e($pdfConfig['outro_text'])) !!}
    </div>
@endif
