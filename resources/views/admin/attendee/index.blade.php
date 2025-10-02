@extends('layouts.layout')

@section('title', 'Daftar Attendee')

@section('content')
    <div class="dashboard-main-body">
        <x-breadcrumb>
            <x-slot:title>Daftar Attendee - {{ $event->title }}</x-slot:title>
            <x-slot:icon>solar:users-group-rounded-broken</x-slot:icon>
        </x-breadcrumb>

        @include('layouts.feedback')

        <div class="card basic-data-table">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Attendee ({{ $event->title }})</h5>
                <div class="d-flex gap-2 align-items-center">
                    <a href="{{ route('admin.event.show', $event->id) }}"
                        class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Kembali">
                        <iconify-icon icon="lucide:arrow-left"></iconify-icon>
                        <span>Kembali</span>
                    </a>
                    @if ($attendees->count() > 0)
                        <a href="{{ route('admin.attendee.exportExcel', $event->id) }}"
                            class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
                            <iconify-icon icon="mdi:file-excel"></iconify-icon>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('admin.attendee.exportPdf', $event->id) }}"
                            class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                            <iconify-icon icon="mdi:file-pdf"></iconify-icon>
                            <span>PDF</span>
                        </a>
                    @else
                        <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" disabled>
                            <iconify-icon icon="mdi:file-excel"></iconify-icon>
                            <span>Excel</span>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" disabled>
                            <iconify-icon icon="mdi:file-pdf"></iconify-icon>
                            <span>PDF</span>
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table bordered-table mb-0" id="dataTable" data-page-length='10' style="overflow-x: auto;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event</th>
                                <th>Full Name</th>
                                <th>Phone Number</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attendees as $attendee)
                                <tr>
                                    <td>{{ $attendee->id }}</td>
                                    <td class="text-truncate" style="max-width: 120px;" title="{{ $event->title }}">
                                        {{ Str::limit($event->title, 15) }}
                                    </td>
                                    <td>{{ $attendee->first_name . ' ' . $attendee->last_name }}</td>
                                    <td>{{ $attendee->phone_number ?? '-' }}</td>
                                    <td>{{ $attendee->email ?? '-' }}</td>
                                    <td>
                                        <span
                                            class="@if ($attendee->attendance?->status === 'present') bg-success-focus text-success-600
                                                    @elseif ($attendee->attendance?->status === 'absent') bg-danger-focus text-danger-600
                                                    @elseif ($attendee->attendance?->status === 'late') bg-warning-focus text-warning-600
                                                    @else bg-secondary text-secondary-600 @endif
                                                    px-16 py-6 rounded-pill fw-semibold text-xs d-inline-block text-center"
                                            style="min-width: 80px;">
                                            {{ ucfirst($attendee->attendance->status ?? 'pending') }}
                                        </span>
                                    </td>
                                    <td class="d-flex gap-1">
                                        {{-- tombol SHOW selalu ada --}}
                                        <a href="{{ route('admin.attendee.show', $attendee->id) }}"
                                            class="w-32-px h-32-px bg-primary-100 text-primary-600 rounded-circle d-inline-flex align-items-center justify-content-center"
                                            title="Detail">
                                            <iconify-icon icon="lucide:eye"></iconify-icon>
                                        </a>

                                        {{-- hanya boleh edit kalau boleh update event --}}
                                        @can('update', $event)
                                            @if ($event->status !== 'done')
                                                <a href="{{ route('admin.attendee.edit', $attendee->id) }}"
                                                    class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center"
                                                    title="Edit">
                                                    <iconify-icon icon="lucide:edit"></iconify-icon>
                                                </a>
                                            @endif
                                        @endcan

                                        {{-- hanya boleh delete kalau boleh delete event --}}
                                        @can('delete', $event)
                                            @if ($event->status === 'done' || $event->status === 'cancelled' || auth()->user()->role === 'super_admin')
                                                <form action="{{ route('admin.attendee.destroy', $attendee->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center"
                                                        title="Hapus">
                                                        <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="1">Belum ada attendee.</td>
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
