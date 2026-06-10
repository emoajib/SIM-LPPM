{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
@props([
    'type' => 'bar',
    'labels' => [],
    'datasets' => [],
    'title' => '',
    'loading' => false
])

<div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px;"
     x-data="{
        chart: null,
        initChart(labels, datasets) {
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            if (!labels || !datasets) return;
            if (typeof Chart === 'undefined') {
                setTimeout(() => this.initChart(labels, datasets), 100);
                return;
            }
            const ctx = this.$refs.canvas.getContext('2d');
            if (this.chart) {
                this.chart.destroy();
            }
            this.chart = new Chart(ctx, {
                type: '{{ $type }}',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: {{ $type === 'doughnut' ? 'true' : 'false' }},
                            position: 'bottom',
                            labels: {
                                color: 'var(--tblr-body-color, #333)',
                                font: {
                                    family: 'Inter, sans-serif'
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: {{ $type !== 'doughnut' ? 'true' : 'false' }},
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: 'var(--tblr-muted-color, #777)'
                            }
                        },
                        y: {
                            display: {{ $type !== 'doughnut' ? 'true' : 'false' }},
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: 'var(--tblr-muted-color, #777)',
                                beginAtZero: true
                            }
                        }
                    }
                }
            });
        }
     }"
     x-init="initChart(@js($labels), @js($datasets))"
     @chart-updated.window="
        if ($event.detail.focusAreas && '{{ $title }}'.includes('Fokus')) {
            initChart($event.detail.focusAreas.labels, $event.detail.focusAreas.datasets);
        } else if ($event.detail.facultyPerformance && '{{ $title }}'.includes('Fakultas')) {
            initChart($event.detail.facultyPerformance.labels, $event.detail.facultyPerformance.datasets);
        }
     "
     style="position: relative; min-height: 320px;">
    
    @if($loading)
        <div class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex align-items-center justify-content-center" style="z-index: 10;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    @endif

    <div class="card-header bg-transparent border-0 py-3">
        <h3 class="card-title fw-bold text-dark mb-0">{{ $title }}</h3>
    </div>
    
    <div class="card-body d-flex flex-column justify-content-between p-3" style="position: relative; flex-grow: 1;">
        <div style="position: relative; height: 220px; width: 100%;" wire:ignore>
            <canvas x-ref="canvas" aria-label="Grafik: {{ $title }}" role="img"></canvas>
        </div>
        
        {{-- Screen Reader Alternative (WCAG Accessibility) --}}
        <div class="visually-hidden">
            <h4>Data Alternatif untuk Grafik: {{ $title }}</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Kategori</th>
                        <th scope="col">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($labels as $index => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>
                                @foreach($datasets as $dataset)
                                    {{ $dataset['label'] ?? '' }}: {{ $dataset['data'][$index] ?? 0 }} 
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
