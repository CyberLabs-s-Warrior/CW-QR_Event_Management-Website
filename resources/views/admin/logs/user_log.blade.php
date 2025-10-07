@extends('layouts.layout')

@section('title', 'Users Log')

@section('content')
    <div class="dashboard-main-body">
        <x-breadcrumb>
            <x-slot:title>Users Log</x-slot:title>
            <x-slot:icon>solar:trash-bin-minimalistic-broken</x-slot:icon>
        </x-breadcrumb>

        @include('layouts.feedback')

        <div class="card basic-data-table">
            <div class="card-header">
                <h5 class="card-title mb-0">Users Log</h5>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Aksi yang dilakukan</th>
                                <th>IP Address</th>
                                <th>Info Perangkat</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($userlog as $logs)
                                <tr>
                                    <td>{{ $logs->id }}</td>
                                    <td>{{ $logs->user->name }}</td>
                                    <td>{{ $logs->action }}</td>
                                    <td>{{ $logs->ip_address }}</td>
                                    <td>{{ $logs->device_info }}</td>
                                    <td class="text-center">
                                        <span class="@if($logs->status === 'success') bg-success-focus text-success-600
                                            @elseif($logs->status === 'failed') bg-danger-focus text-danger-600
                                            @else bg-secondary text-secondary-600 @endif
                                            px-16 py-6 rounded-pill fw-semibold text-white text-xs">
                                            {{ ucfirst($logs->status) }}
                                        </span>
                                    </td>
                                </tr>
                                {{-- Modal --}}
                                <x-delete-modal 
                                    :id="$logs->id"
                                    :action="route('admin.trash.forceDelete', ['type' => 'categories', 'id' => $logs->id])"
                                    title="Hapus Permanen Kategori"
                                    :message="'Apakah Anda yakin ingin menghapus permanen kategori ' . $logs->name . '?'"
                                />
                                {{-- Modal Restore --}}
                                <x-restore-modal
                                    :id="$logs->id"
                                    :action="route('admin.trash.restore', ['type' => 'categories', 'id' => $logs->id])"
                                    title="Restore Kategori"
                                    :message="'Apakah Anda yakin ingin merestore kategori ' . $logs->name . '?'"
                                />
                            @empty
                                <tr>
                                    <td colspan="1">Tidak ada kategori di trash.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('beforeAppScripts')
    <script>
        let table = new DataTable('#dataTable', {
            order: [[0, 'desc']]
        });
    </script>
@endsection
