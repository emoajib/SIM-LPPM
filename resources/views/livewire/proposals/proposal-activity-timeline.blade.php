<div>
    @if ($activities->isEmpty())
        <div class="empty-state py-5 text-center">
            <div class="empty-img mb-3">
                <x-lucide-history class="text-muted opacity-20" size="48" stroke-width="1" />
            </div>
            <p class="empty-title">Belum ada riwayat perubahan</p>
            <p class="empty-subtitle text-muted">Aktivitas perubahan data proposal akan muncul di sini secara otomatis.</p>
        </div>
    @else
        <div class="timeline">
            @foreach ($activities as $activity)
                @php
                    $badgeColor = match ($activity->activity_type) {
                        'created' => 'bg-success',
                        'updated' => 'bg-primary',
                        'deleted' => 'bg-danger',
                        default => 'bg-info'
                    };

                    $icon = match ($activity->activity_type) {
                        'created' => 'lucide-plus',
                        'updated' => 'lucide-pencil',
                        'deleted' => 'lucide-trash',
                        default => 'lucide-history'
                    };
                @endphp

                <div class="timeline-item">
                    <div class="timeline-badge {{ $badgeColor }}">
                        <x-dynamic-component :component="$icon" class="icon-inline" style="width: 12px; height: 12px;" />
                    </div>

                    <div class="timeline-content">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <span class="fw-bold text-dark">{{ $activity->description }}</span>
                                <span class="text-muted ms-1">oleh
                                    <strong>{{ $activity->user?->name ?? 'Sistem' }}</strong></span>
                            </div>
                            <small class="text-muted">{{ $activity->created_at->format('d M Y H:i') }}</small>
                        </div>

                        @if($activity->changes && count($activity->changes) > 0)
                            <div class="table-responsive border rounded-2 overflow-hidden mt-3">
                                <table class="table table-vcenter table-sm card-table mb-0 bg-white"
                                    style="table-layout: fixed; font-size: 0.825rem;">
                                    <thead>
                                        <tr>
                                            <th class="bg-light py-2" style="width: 25%;">Field</th>
                                            <th class="bg-light py-2 text-center" style="width: 37.5%;">Sebelumnya</th>
                                            <th class="bg-light py-2 text-center" style="width: 37.5%;">Sesudah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $formatVal = function ($val) {
                                                if ($val === null || $val === '' || $val === '-')
                                                    return '<span class="text-muted small"><em>-</em></span>';

                                                if (is_string($val) && (str_starts_with($val, '[') || str_starts_with($val, '{'))) {
                                                    $decoded = json_decode($val, true);
                                                    if (json_last_error() === JSON_ERROR_NONE) {
                                                        $val = $decoded;
                                                    }
                                                }

                                                if (is_array($val)) {
                                                    if (empty($val))
                                                        return '<span class="text-muted small"><em>-</em></span>';

                                                    if (isset($val[0]['name'])) {
                                                        $html = '<div class="members-display-list">';
                                                        foreach ($val as $m) {
                                                            $html .= '<div class="mb-2" style="padding-bottom: 8px; border-bottom: 1px dashed #eee;">';
                                                            $html .= '<div class="fw-bold text-dark">' . e($m['name']) . '</div>';
                                                            $html .= '<div class="text-muted small">' . e($m['role'] ?? 'anggota') . ' • ' . e($m['study_program'] ?? '-') . '</div>';
                                                            if (!empty($m['tasks'])) {
                                                                $html .= '<div class="text-secondary small mt-1">' . e($m['tasks']) . '</div>';
                                                            }
                                                            $html .= '</div>';
                                                        }
                                                        $html = str_replace('border-bottom: 1px dashed #eee;"></div>', '"></div>', $html);
                                                        $html .= '</div>';
                                                        return $html;
                                                    }
                                                    return '<span class="text-muted small">Data terstruktur (' . count($val) . ' item)</span>';
                                                }
                                                return '<div class="text-wrap" style="word-break: break-word;">' . e(Str::limit($val, 500)) . '</div>';
                                            };

                                            $fieldLabels = [
                                                'title' => 'Judul',
                                                'summary' => 'Ringkasan',
                                                'student_members' => 'Anggota Mahasiswa',
                                                'duration_in_years' => 'Durasi (Tahun)',
                                                'start_year' => 'Tahun Mulai',
                                                'sbk_value' => 'Nilai SBK',
                                                'focus_area_id' => 'Bidang Fokus',
                                                'theme_id' => 'Tema',
                                                'topic_id' => 'Topik',
                                                'research_scheme_id' => 'Skema Penelitian',
                                                'community_service_scheme_id' => 'Skema PKM',
                                            ];
                                        @endphp

                                        @foreach($activity->changes as $field => $change)
                                            <tr>
                                                <td class="align-top py-2">
                                                    <span
                                                        class="text-secondary opacity-75 small fw-bold">{{ $fieldLabels[$field] ?? Str::headline($field) }}</span>
                                                </td>
                                                <td class="align-top py-2"
                                                    style="background-color: #fdfdfd; border-right: 1px solid #f1f1f1;">
                                                    <div class="px-1 text-muted">{!! $formatVal($change['old']) !!}</div>
                                                </td>
                                                <td class="align-top py-2" style="background-color: #fcfdfc;">
                                                    <div class="px-1 text-dark fw-medium">{!! $formatVal($change['new']) !!}</div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
