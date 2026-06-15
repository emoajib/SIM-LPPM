<x-slot:title>Pengaturan</x-slot:title>
<x-slot:pageTitle>Pengaturan Akun</x-slot:pageTitle>
<x-slot:pageSubtitle>Kelola pengaturan akun dan preferensi Anda.</x-slot:pageSubtitle>

<div>
    <x-tabler.alert />
    <div class="card">
        <div class="row g-0">
            <div class="col-12 col-md-3 settings-sidebar">
                <div class="card-body">
                    <h4 class="subheader">Pengaturan Akun</h4>
                    <div class="list-group list-group-transparent">
                        <button wire:click="setActiveTab('profile')"
                            class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'profile' ? 'active' : '' }}">
                            <x-lucide-user class="icon" />
                            <span>Profil Saya</span>
                        </button>
                        <button wire:click="setActiveTab('password')"
                            class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'password' ? 'active' : '' }}">
                            <x-lucide-lock class="icon" />
                            <span>Ubah Kata Sandi</span>
                        </button>
                        <button wire:click="setActiveTab('security')"
                            class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'security' ? 'active' : '' }}">
                            <x-lucide-activity class="icon" />
                            <span>Log Keamanan</span>
                        </button>
                    </div>
                    @role('admin lppm')
                        <h4 class="mt-4 subheader">Pengaturan Sistem</h4>
                        <div class="list-group list-group-transparent">
                            <button wire:click="setActiveTab('appearance')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'appearance' ? 'active' : '' }}">
                                <x-lucide-palette class="icon" />
                                <span>Tampilan</span>
                            </button>
                            {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                            <button wire:click="setActiveTab('pdf-export')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'pdf-export' ? 'active' : '' }}">
                                <x-lucide-printer class="icon" />
                                <span>PDF & Ekspor</span>
                            </button>
                            <button wire:click="setActiveTab('audit')"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'audit' ? 'active' : '' }}">
                                <x-lucide-eye class="icon" />
                                <span>Audit Log</span>
                            </button>
                             <button wire:click="setActiveTab('feature-flags')"
                                 class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'feature-flags' ? 'active' : '' }}">
                                 <x-lucide-toggle-left class="icon" />
                                 <span>Feature Flags</span>
                             </button>
                             <button wire:click="setActiveTab('sync')"
                                  class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'sync' ? 'active' : '' }}">
                                  <x-lucide-refresh-ccw class="icon" />
                                  <span>Sinkronisasi Data</span>
                              </button>
                              <button wire:click="setActiveTab('backup')"
                                  class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'backup' ? 'active' : '' }}">
                                  <x-lucide-database class="icon" />
                                  <span>Backup Data</span>
                              </button>
                              <button wire:click="setActiveTab('restore')"
                                  class="list-group-item list-group-item-action d-flex align-items-center gap-2 {{ $activeTab === 'restore' ? 'active' : '' }}">
                                  <x-lucide-cloud-upload class="icon" />
                                  <span>Pulihkan Data</span>
                              </button>
                         </div>
                    @endrole
                </div>
            </div>

            <div class="col-12 col-md-9 ps-md-0">
                <div class="card-body">
                    @if ($activeTab === 'profile')
                        <div>
                            <h2 class="mb-4">Profil Saya</h2>
                            <livewire:settings.profile-form />
                        </div>
                    @elseif ($activeTab === 'password')
                        <div>
                            <h2 class="mb-4">Ubah Kata Sandi</h2>
                            <p class="mb-4 card-subtitle">Pastikan akun Anda menggunakan kata sandi yang panjang dan
                                acak untuk tetap aman.</p>
                            <livewire:settings.password />
                        </div>
                    @elseif ($activeTab === 'appearance')
                        <div>
                            <livewire:settings.appearance />
                        </div>
                    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
                    @elseif ($activeTab === 'pdf-export')
                        <div>
                            <h2 class="mb-4">Pengaturan PDF & Ekspor</h2>
                            <p class="mb-4 card-subtitle">Kelola preferensi tipografi, tata letak, dan logo untuk semua dokumen PDF.</p>
                            <livewire:settings.pdf-export-settings />
                        </div>
                    @elseif ($activeTab === 'audit')
                        <div>
                            <h2 class="mb-4">Audit Log</h2>
                            <p class="mb-4 card-subtitle">Pantau aktivitas sistem untuk semua pengguna.</p>
                            <livewire:settings.audit-log />
                        </div>
                    @elseif ($activeTab === 'security')
                        <div>
                            <h2 class="mb-4">Log Keamanan</h2>
                            <p class="mb-4 card-subtitle">Pantau riwayat aktivitas login dan akses akun Anda untuk menjaga keamanan.</p>
                            <livewire:users.activity-log-list :user="auth()->user()" />
                        </div>
                     @elseif ($activeTab === 'feature-flags')
                         <div>
                             <h2 class="mb-4">Feature Flags (Eksperimental)</h2>
                             <p class="mb-4 card-subtitle">Aktifkan atau nonaktifkan fitur sistem yang belum diwajibkan secara institusional.</p>
                             <livewire:settings.feature-flags />
                         </div>
                      @elseif ($activeTab === 'sync')
                          <div>
                              <h2 class="mb-4">Sinkronisasi Data</h2>
                              <p class="mb-4 card-subtitle">Sinkronkan data dari server produksi ke localhost untuk backup dan development.</p>
                              <livewire:settings.data-sync />
                          </div>
                      @elseif ($activeTab === 'backup')
                          <div>
                              <h2 class="mb-4">Backup Data</h2>
                              <p class="mb-4 card-subtitle">Unduh cadangan database dan file storage untuk backup dan pengembangan lokal.</p>
                              <livewire:settings.backup-data />
                          </div>
                      @elseif ($activeTab === 'restore')
                          <div>
                              <h2 class="mb-4">Pulihkan Data</h2>
                              <p class="mb-4 card-subtitle">Upload file backup untuk memulihkan database dan file storage setelah disaster recovery.</p>
                              <livewire:settings.restore-data />
                          </div>
                      @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .settings-sidebar {
        border-right: 1px solid var(--tblr-border-color);
    }
    @media (max-width: 767.98px) {
        .settings-sidebar {
            border-right: none;
            border-bottom: 1px solid var(--tblr-border-color);
        }
    }
</style>
