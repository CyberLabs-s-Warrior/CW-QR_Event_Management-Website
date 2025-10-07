@extends('layouts.layout')

@section('title', 'Log QR')

@section('content')
    <div class="dashboard-main-body">
        <x-breadcrumb>
            <x-slot:title>Trash Event</x-slot:title>
            <x-slot:icon>solar:trash-bin-minimalistic-broken</x-slot:icon>
        </x-breadcrumb>

        @include('layouts.feedback')

        <div class="card basic-data-table">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Log QR Terhapus</h5>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table bordered-table mb-0" id="dataTable" data-page-length='10'>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>QRCode</th>
                                <th>Attendee</th>
                                <th>Dilakukan oleh</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($qrcodelog as $logs)
                                <tr>
                                    <td>{{ $logs->id }}</td>
                                    <td>{{ $logs->attendee->code ?? 'N/A' }}</td>
                                    <td>{{ $logs->attendee->first_name . ' ' . $logs->attendee->last_name ?? 'N/A' }}</td>
                                    <td>{{ $logs->user->name ?? 'N/A' }}</td>

                                    <td class="text-center">
                                        <span class="@if($logs->status === 'Scanned') bg-success-focus text-success-600
                                            @elseif($logs->status === 'Invalid') bg-danger-focus text-danger-600
                                            @else bg-secondary text-secondary-600 @endif
                                            px-16 py-6 rounded-pill fw-semibold text-white text-xs">
                                            {{ ucfirst($logs->status) }}
                                        </span>
                                    </td>
                                </tr>
                                {{-- Modal --}}
                                <x-delete-modal :id="$logs->id" :action="route('admin.trash.forceDelete', ['type' => 'logss', 'id' => $logs->id])" title="Hapus Permanen Event"
                                    :message="'Apakah Anda yakin ingin menghapus permanen logs ' . $logs->title . '?'" />
                                {{-- Modal Restore --}}
                                <x-restore-modal :id="$logs->id" :action="route('admin.trash.restore', ['type' => 'logss', 'id' => $logs->id])" title="Restore Event"
                                    :message="'Apakah Anda yakin ingin merestore logs ' . $logs->title . '?'" />
                            @empty
                                <tr>
                                    <td colspan="1">Tidak ada logs di trash.</td>
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
            order: [
                [0, 'desc']
            ]
        });
    </script>
@endsection
