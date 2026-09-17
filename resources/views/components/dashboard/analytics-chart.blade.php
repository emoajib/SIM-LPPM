{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
@props([
    'type' => 'bar',
    'labels' => [],
    'datasets' => [],
    'title' => '',
    'loading' => false,
    'height' => '320px'
])

<style wire:ignore>
    .hover-scale {
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .hover-scale:hover {
        transform: translateY(-4px) scale(1.008);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06) !important;
    }
    .chart-container-relative {
        border-radius: 12px; 
        position: relative; 
        min-height: {{ $height }};
        overflow: hidden;
    }
</style>

<div class="card border-0 shadow-sm h-100 hover-scale chart-container-relative"
     x-data="{
        isLoading: {{ $loading ? 'true' : 'false' }},
        isEmpty: false,
        a11yData: [],
        
        initChart(labels, datasets, isReinit = false) {
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            
            // 1. Edge Case Handling: Data Kosong
            if (!labels || !datasets || labels.length === 0 || datasets.length === 0) {
                this.isEmpty = true;
                this.isLoading = false;
                return;
            }
            
            // Periksa jika seluruh dataset bernilai 0 (untuk bar/line chart)
            const hasData = datasets.some(ds => ds.data && ds.data.some(val => val > 0));
            if (!hasData) {
                this.isEmpty = true;
                this.isLoading = false;
                return;
            }

            this.isEmpty = false;
            this.isLoading = false;

            // 2. Aksesibilitas Dinamis (WCAG)
            this.a11yData = labels.map((label, i) => {
                return {
                    label: label,
                    values: datasets.map(ds => ({
                        name: ds.label || 'Dataset',
                        value: ds.data[i] || 0
                    }))
                };
            });

            // 3. Render Chart
            if (typeof Chart === 'undefined') {
                setTimeout(() => this.initChart(labels, datasets, isReinit), 100);
                return;
            }
            
            const canvasEl = this.$refs.canvas;
            if (!canvasEl) return;
            const ctx = canvasEl.getContext('2d');
            if (!ctx) return;
            
            // CRITICAL: Matikan animasi pada update untuk hindari stale rAF callbacks
            const animDuration = isReinit ? 0 : 800;

            // DOM-based Chart Reference (Mengatasi proxy Alpine stack overflow)
            if (canvasEl.chartInstance) {
                canvasEl.chartInstance.data.labels = labels;
                canvasEl.chartInstance.data.datasets = datasets.map(ds => ({
                    ...ds,
                    hoverOffset: {{ $type === 'doughnut' ? 15 : 0 }},
                    hoverBorderWidth: {{ $type === 'bar' ? 2 : 0 }},
                    hoverBorderColor: '#ffffff',
                    hoverBackgroundColor: ds.backgroundColor ? (Array.isArray(ds.backgroundColor) ? ds.backgroundColor.map(c => c + 'cc') : ds.backgroundColor + 'cc') : undefined
                }));
                canvasEl.chartInstance.update(isReinit ? 'none' : 'default');
                return;
            }

            canvasEl.chartInstance = new Chart(ctx, {
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
                                // WCAG AAA: kontras teks maksimal #0f172a di atas putih
                                color: '#0f172a',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 13,
                                    weight: '600'
                                },
                                padding: 14,
                                boxWidth: 12,
                                boxHeight: 12,
                                usePointStyle: true
                            }
                        },
                        // Vetted by AI - Manual Review Required by Senior Engineer/Manager
                        // Direct Data Labels: Angka tampil langsung di atas chart tanpa perlu hover
                        datalabels: {
                            display: function(context) {
                                const val = context.dataset.data ? context.dataset.data[context.dataIndex] : 0;
                                return typeof val === 'number' && val > 0;
                            },
                            color: '{{ $type === "doughnut" ? "#ffffff" : "#0f172a" }}',
                            anchor: '{{ $type === "doughnut" ? "center" : "end" }}',
                            align: '{{ $type === "doughnut" ? "center" : "top" }}',
                            offset: {{ $type === "doughnut" ? 0 : 4 }},
                            font: {
                                family: 'Inter, sans-serif',
                                size: {{ $type === "doughnut" ? 12 : 11 }},
                                weight: 'bold'
                            },
                            padding: { top: 2, bottom: 2, left: 5, right: 5 },
                            borderRadius: 4,
                            backgroundColor: '{{ $type === "doughnut" ? "rgba(15, 23, 42, 0.6)" : "rgba(255, 255, 255, 0.92)" }}',
                            borderColor: '{{ $type === "doughnut" ? "rgba(255, 255, 255, 0.3)" : "rgba(226, 232, 240, 0.8)" }}',
                            borderWidth: 1,
                            formatter: function(value) {
                                return typeof value === 'number' ? value.toLocaleString('id-ID') : value;
                            }
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: { family: 'Inter, sans-serif', size: 13, weight: 'bold' },
                            bodyFont: { family: 'Inter, sans-serif', size: 12 },
                            displayColors: true,
                            callbacks: {
                                // Format angka ribuan: 1.234 (locale Indonesia)
                                label: function(item) {
                                    const val = typeof item.raw === 'number'
                                        ? item.raw.toLocaleString('id-ID')
                                        : item.formattedValue;
                                    return (item.dataset.label ? item.dataset.label + ': ' : '') + val;
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
                                // Kontras tinggi Slate-800 (#1e293b) & font jelas
                                color: '#1e293b',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12,
                                    weight: '600'
                                },
                                maxRotation: 45,
                                minRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 12
                            }
                        },
                        y: {
                            display: {{ $type !== 'doughnut' ? 'true' : 'false' }},
                            grace: '15%', // Memberi ruang vertikal 15% agar angka datalabel tidak terpotong canvas
                            grid: {
                                color: 'rgba(0, 0, 0, 0.07)'
                            },
                            ticks: {
                                color: '#1e293b',
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12,
                                    weight: '600'
                                },
                                beginAtZero: true,
                                // Format angka ribuan pada sumbu Y
                                callback: function(value) {
                                    return Number.isInteger(value) ? value.toLocaleString('id-ID') : value;
                                }
                            }
                        }
                    }
                }
            });
        }
     }"
     x-init="$nextTick(() => initChart(@js($labels), @js($datasets)))"
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
     ">
    
    {{-- 1. SKELETON LOADER (Shimmer Effect) --}}
    <div x-show="isLoading" 
         x-transition.opacity.duration.300ms
         class="position-absolute top-0 start-0 w-100 h-100 bg-white p-3 z-3 placeholder-glow flex-column" 
         style="border-radius: 12px; display: flex;">
        <div class="placeholder col-6 mb-4" style="height: 24px; border-radius: 4px;"></div>
        <div class="d-flex align-items-end justify-content-between h-100 gap-3 pb-3 px-2">
            <div class="placeholder w-100" style="height: 40%; border-radius: 4px;"></div>
            <div class="placeholder w-100" style="height: 80%; border-radius: 4px;"></div>
            <div class="placeholder w-100" style="height: 60%; border-radius: 4px;"></div>
            <div class="placeholder w-100" style="height: 90%; border-radius: 4px;"></div>
            <div class="placeholder w-100" style="height: 30%; border-radius: 4px;"></div>
            <div class="placeholder w-100" style="height: 70%; border-radius: 4px;"></div>
        </div>
    </div>

    {{-- 2. EMPTY STATE (Edge Case Handling) --}}
    <div x-show="!isLoading && isEmpty" 
         x-transition.opacity.duration.300ms
         class="position-absolute top-0 start-0 w-100 h-100 bg-white flex-column align-items-center justify-content-center z-2" 
         style="border-radius: 12px; display: flex;">
        <div class="bg-light rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon text-muted" width="32" height="32" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <circle cx="12" cy="12" r="9" />
                <line x1="9" y1="10" x2="9.01" y2="10" />
                <line x1="15" y1="10" x2="15.01" y2="10" />
                <path d="M9 15a3 3 0 0 0 6 0" />
            </svg>
        </div>
        <span class="text-dark fw-bold mb-1">Belum Ada Data</span>
        <span class="text-muted small">Data untuk grafik ini masih kosong.</span>
    </div>

    {{-- 3. CHART UI --}}
    <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center justify-content-between">
        <h3 class="card-title fw-bold text-dark mb-0">{{ $title }}</h3>
        <template x-if="a11yData && a11yData.length > 0">
            <span class="badge bg-primary-lt fw-bold px-2 py-1" 
                  x-text="'Total: ' + a11yData.reduce((acc, curr) => acc + curr.values.reduce((vAcc, v) => vAcc + (Number(v.value) || 0), 0), 0).toLocaleString('id-ID')">
            </span>
        </template>
    </div>
    
    <div class="card-body d-flex flex-column p-3" style="position: relative; flex-grow: 1;">
        <div style="position: relative; height: 220px; width: 100%;" wire:ignore>
            <canvas x-ref="canvas" aria-label="Grafik: {{ $title }}" role="img"></canvas>
        </div>
        
        {{-- 4. SCREEN READER ACCESSIBILITY TABLE (Dynamic WCAG) --}}
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
                    <template x-for="item in a11yData" :key="item.label">
                        <tr>
                            <td x-text="item.label"></td>
                            <td>
                                <ul>
                                    <template x-for="val in item.values" :key="val.name">
                                        <li x-text="val.name + ': ' + val.value"></li>
                                    </template>
                                </ul>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
