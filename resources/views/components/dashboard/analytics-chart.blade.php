{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
@props([
    'type' => 'bar',
    'labels' => [],
    'datasets' => [],
    'title' => '',
    'loading' => false
])

<style wire:ignore>
    .hover-scale {
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .hover-scale:hover {
        transform: translateY(-4px) scale(1.008);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06) !important;
    }
</style>

<div class="card border-0 shadow-sm h-100 hover-scale"
     x-data="{
        chart: null,
        initChart(labels, datasets, isReinit = false) {
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            if (!labels || !datasets) return;
            if (typeof Chart === 'undefined') {
                setTimeout(() => this.initChart(labels, datasets, isReinit), 100);
                return;
            }
            const ctx = this.$refs.canvas?.getContext('2d');
            if (!ctx) return;
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
            
            // CRITICAL: On re-init (chart-updated), disable animation to prevent stale rAF callbacks
            // that could fire after chart.ctx is nulled by destroy(). Animation on initial render only.
            const animDuration = isReinit ? 0 : 800;

            this.chart = new Chart(ctx, {
                type: '{{ $type }}',
                data: {
                    labels: labels,
                    datasets: datasets.map(ds => ({
                        ...ds,
                        hoverOffset: {{ $type === 'doughnut' ? 15 : 0 }},
                        hoverBorderWidth: {{ $type === 'bar' ? 2 : 0 }},
                        hoverBorderColor: '#ffffff',
                        hoverBackgroundColor: ds.backgroundColor ? (Array.isArray(ds.backgroundColor) ? ds.backgroundColor.map(c => c + 'cc') : ds.backgroundColor + 'cc') : undefined
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: animDuration,
                        easing: 'easeOutQuart'
                    },
                    transitions: {
                        active: {
                            animation: {
                                duration: 300
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                color: 'var(--tblr-body-color, #333)',
                                font: {
                                    family: 'Inter, sans-serif'
                                }
                            }
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(30, 41, 59, 0.9)',
                            titleFont: { family: 'Inter, sans-serif', size: 13, weight: 'bold' },
                            bodyFont: { family: 'Inter, sans-serif', size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: true
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
                },
                plugins: [
                    {
                                id: 'datalabels',
                        afterDraw(chart) {
                            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
                            const ctx = chart.ctx;
                            // CRITICAL: ctx CAN be null if chart was destroyed during a pending draw cycle
                            // or if the canvas context was released. Guard EVERY usage.
                            if (!ctx) return;
                            try {
                                ctx.save();
                                const visibleDatasetsCount = chart.data.datasets.filter((_, idx) => chart.isDatasetVisible(idx)).length;
                                chart.data.datasets.forEach((dataset, i) => {
                                    if (!chart.isDatasetVisible(i)) return;
                                    const meta = chart.getDatasetMeta(i);
                                    meta.data.forEach((element, index) => {
                                        const dataVal = dataset.data[index];
                                        if (dataVal === undefined || dataVal === null || dataVal === 0) return;
                                        
                                        let x, y;
                                        if (chart.config.type === 'doughnut') {
                                            if (typeof element.getCenterPoint === 'function') {
                                                const center = element.getCenterPoint();
                                                x = center.x;
                                                y = center.y;
                                            } else {
                                                return;
                                            }
                                            ctx.fillStyle = '#ffffff';
                                            ctx.shadowColor = 'rgba(0, 0, 0, 0.4)';
                                            ctx.shadowBlur = 4;
                                            ctx.font = 'bold 12px Inter, sans-serif';
                                        } else {
                                            x = element.x;
                                            y = element.y - 8;
                                            ctx.fillStyle = 'var(--tblr-body-color, #333)';
                                            ctx.shadowBlur = 0;
                                            // Vetted by AI - Adjust font size when multiple datasets are shown to avoid overlap
                                            ctx.font = visibleDatasetsCount > 1 ? 'bold 9px Inter, sans-serif' : 'bold 11px Inter, sans-serif';
                                        }
                                        
                                        ctx.textAlign = 'center';
                                        ctx.textBaseline = 'middle';
                                        ctx.fillText(dataVal, x, y);
                                    });
                                });
                                ctx.restore();
                            } catch (e) {
                                // Silently fail if canvas context is lost mid-draw
                                console.warn('Chart datalabels draw error:', e);
                            }
                        }
                    }
                ]
            });
        },
        destroy() {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
        }
     }"
     x-init="initChart(@js($labels), @js($datasets))"
     @chart-updated.window="
        requestAnimationFrame(() => {
            if ($event.detail.focusAreas && '{{ $title }}'.includes('Fokus')) {
                initChart($event.detail.focusAreas.labels, $event.detail.focusAreas.datasets, true);
            } else if ($event.detail.facultyPerformance && '{{ $title }}'.includes('Fakultas')) {
                initChart($event.detail.facultyPerformance.labels, $event.detail.facultyPerformance.datasets, true);
            } else if ($event.detail.scienceClusters && '{{ $title }}'.includes('Rumpun')) {
                initChart($event.detail.scienceClusters.labels, $event.detail.scienceClusters.datasets, true);
            } else if ($event.detail.tkt && '{{ $title }}'.includes('TKT')) {
                initChart($event.detail.tkt.labels, $event.detail.tkt.datasets, true);
            } else if ($event.detail.themes && '{{ $title }}'.includes('Tema')) {
                initChart($event.detail.themes.labels, $event.detail.themes.datasets, true);
            } else if ($event.detail.topics && '{{ $title }}'.includes('Topik')) {
                initChart($event.detail.topics.labels, $event.detail.topics.datasets, true);
            }
        });
     "
     style="border-radius: 12px; position: relative; min-height: 320px;">
    
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
    
    <div class="card-body d-flex flex-column p-3" style="position: relative; flex-grow: 1;">
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
