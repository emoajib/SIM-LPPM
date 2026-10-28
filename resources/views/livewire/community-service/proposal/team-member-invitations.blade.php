<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            Status Anggota Tim
            @if ($this->allAccepted)
                <x-tabler.badge color="success" class="ms-2">Semua Diterima</x-tabler.badge>
            @else
                <x-tabler.badge color="warning" class="ms-2">{{ $this->pendingInvitations->count() }}
                    Menunggu</x-tabler.badge>
            @endif
        </h3>
    </div>

    @if ($this->acceptedMembers->count() > 0)
        <div class="card-header">
            <h4 class="text-success card-title">Anggota yang Diterima</h4>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <tbody>
                    @foreach ($this->acceptedMembers as $member)
                        <tr>
                            <td class="w-1">
                                <x-tabler.badge variant="solid" color="success"></x-tabler.badge>
                            </td>
                            <td>
                                <strong>{{ $member->name }}</strong><br>
                                <small class="text-muted">{{ $member->email }}</small>
                            </td>
                            <td class="text-end">
                                <x-tabler.badge color="success">✓ Diterima</x-tabler.badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($this->pendingInvitations->count() > 0)
        <div class="card-header">
            <h4 class="text-warning card-title">Anggota yang Menunggu Persetujuan</h4>
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <tbody>
                    @foreach ($this->pendingInvitations as $member)
                        <tr>
                            <td class="w-1">
                                <x-tabler.badge variant="solid" color="warning"></x-tabler.badge>
                            </td>
                            <td>
                                <strong>{{ $member->name }}</strong><br>
                                <small class="text-muted">{{ $member->email }}</small>
                            </td>
                            <td class="text-end">
                                <x-tabler.badge color="warning">⏳ Menunggu</x-tabler.badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Show action buttons only for current user who is invited --}}
        @php
            $currentUserInvited = $this->pendingInvitations->where('id', auth()->id())->first();
        @endphp

        @if ($currentUserInvited)
            <div class="card-footer">
                <div class="btn-list">
                    <button wire:click="acceptInvitation" class="btn btn-success">
                        
                        Terima Undangan
                    </button>
                    <button wire:click="rejectInvitation" class="btn btn-danger">
                        
                        Tolak Undangan
                    </button>
                </div>
            </div>
        @endif
    @endif

    @if ($this->rejectedMembers->count() > 0)
        <div class="card-header">
            <h4 class="text-danger card-title">Anggota yang Menolak</h4>
        </div>
        <div class="alert alert-danger" role="alert">
            <strong>Perhatian:</strong> Beberapa anggota telah menolak undangan. Silahkan hapus dan tambah anggota baru.
        </div>
        <div class="table-responsive">
            <table class="card-table table table-vcenter">
                <tbody>
                    @foreach ($this->rejectedMembers as $member)
                        <tr>
                            <td class="w-1">
                                <x-tabler.badge variant="solid" color="danger"></x-tabler.badge>
                            </td>
                            <td>
                                <strong>{{ $member->name }}</strong><br>
                                <small class="text-muted">{{ $member->email }}</small>
                            </td>
                            <td class="text-end">
                                <x-tabler.badge color="danger">✗ Ditolak</x-tabler.badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
