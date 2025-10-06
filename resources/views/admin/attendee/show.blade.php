@extends('layouts.layout')

@section('title', 'Detail Attendee')

@section('content')
<div class="dashboard-main-body">
    <x-breadcrumb>
        <x-slot:title>Detail Attendee</x-slot:title>
        <x-slot:icon>solar:user-broken</x-slot:icon>
    </x-breadcrumb>

    

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Detail Attendee: {{ $attendee->first_name }} {{ $attendee->last_name }}</h5>
            <div class="d-flex gap-2">
                {{-- hanya bisa edit attendee kalau bisa update event & status event masih active --}}
                @can('update', $event)
                    @if ($event->status === 'active')
                        <a href="{{ route('admin.attendee.edit', $attendee->id) }}"
                            class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center"
                            title="Edit">
                            <iconify-icon icon="lucide:edit"></iconify-icon>
                        </a>
                    @endif
                @endcan

                {{-- tombol kembali selalu ada --}}
                <a href="{{ route('admin.attendee.index', $attendee->event_id) }}"
                    class="w-32-px h-32-px bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                    title="Kembali">
                    <iconify-icon icon="lucide:arrow-left"></iconify-icon>
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="fw-semibold ">Full Name</label>
                    <p class="mb-0">{{ $attendee->first_name }} {{ $attendee->last_name }}</p>
                </div>

                <div class="col-md-6">
                    <label class="fw-semibold ">Event</label>
                    <p class="mb-0">{{ $attendee->event->title ?? '-' }}</p>
                </div>

                <div class="col-md-6">
                    <label class="fw-semibold ">Phone Number</label>
                    <p class="mb-0">{{ $attendee->phone_number ?? '-' }}</p>
                </div>

                <div class="col-md-6">
                    <label class="fw-semibold ">Email</label>
                    <p class="mb-0">{{ $attendee->email ?? '-' }}</p>
                </div>

                <div class="col-md-6">
                    <label class="fw-semibold ">Dokumen</label>
                    @if($attendee->document)
                        <a href="{{ asset('storage/'.$attendee->document) }}" target="_blank" class="text-primary text-decoration-underline">Lihat Dokumen</a>
                    @else
                        <p class="mb-0 ">Tidak ada dokumen</p>
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="fw-semibold ">Attendance</label>
                    @if($attendance)
                        <p class="mb-0">
                            Status:
                            <span class="@if($attendance->status === 'Scanned') bg-success-focus text-success-600
                                @elseif($attendance->status === 'Invalid') bg-danger-focus text-danger-600
                                @else bg-secondary text-secondary-600 @endif
                                px-16 py-6 rounded-pill fw-semibold text-white text-xs">
                                {{ ucfirst($attendance->status) }}
                            </span><br>
                            Check-in: {{ $attendance->check_in_time ?? '-' }} <br>
                            Notes: {{ $attendance->notes ?? '-' }}
                        </p>
                    @else
                        <p class="mb-0 ">Belum ada absensi</p>
                    @endif
                </div>

                @if($attendee->customInputs->count())
                    <div class="col-md-12">
                        <label class="fw-semibold mb-10">Custom Input</label>
                        <div class="border rounded p-3 bg-dark text-white">
                            @foreach($attendee->customInputs as $input)
                                <p class="mb-1"><strong>{{ $input->name }}:</strong> {{ $input->value }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
