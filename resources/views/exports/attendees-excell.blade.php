<table style="width:100%; border-collapse:collapse; font-family:Arial, sans-serif; font-size:12px;">
    <tr>
        <td colspan="5" align="center" style="font-size:16px; font-weight:bold; padding:8px;">
            Daftar Peserta Event: {{ $event->title }}
        </td>
    </tr>
    <tr>
        <td colspan="5" align="center" style="font-size:12px; color:#555; padding-bottom:12px;">
            Lokasi: {{ $event->location }} |
            Tanggal: {{ $event->start_date->format('d M Y') }} - {{ $event->end_date->format('d M Y') }}
        </td>
    </tr>

    <thead>
        <tr>
            <th style="background:#2F5597; color:white; font-weight:bold; text-align:left; padding:6px; border:1px solid #ddd;">ID</th>
            <th style="background:#2F5597; color:white; font-weight:bold; text-align:left; padding:6px; border:1px solid #ddd;">Nama Lengkap</th>
            <th style="background:#2F5597; color:white; font-weight:bold; text-align:left; padding:6px; border:1px solid #ddd;">Telepon</th>
            <th style="background:#2F5597; color:white; font-weight:bold; text-align:left; padding:6px; border:1px solid #ddd;">Email</th>
            <th style="background:#2F5597; color:white; font-weight:bold; text-align:left; padding:6px; border:1px solid #ddd;">Status</th>
        </tr>
    </thead>

    <tbody>
        @foreach($attendees as $index => $attendee)
            <tr style="background: {{ $index % 2 === 0 ? '#f9f9f9' : 'white' }};">
                <td style="padding:6px; border:1px solid #ddd;">{{ $attendee->id }}</td>
                <td style="padding:6px; border:1px solid #ddd;">{{ $attendee->first_name }} {{ $attendee->last_name }}</td>
                <td style="padding:6px; border:1px solid #ddd;">{{ $attendee->phone_number ?? '-'}}</td>
                <td style="padding:6px; border:1px solid #ddd;">{{ $attendee->email ?? '-'}}</td>
                <td style="padding:6px; border:1px solid #ddd;
                    @switch($attendee->attendance?->status)
                        @case('present') background:#e2f5e9; color:#2e7d32; font-weight:bold; @break
                        @case('late') background:#fff4e5; color:#e65100; font-weight:bold; @break
                        @case('absent') background:#fdecea; color:#c62828; font-weight:bold; @break
                        @default background:#f5f5f5; color:#616161; font-weight:bold;
                    @endswitch">
                    @switch($attendee->attendance?->status)
                        @case('present') Hadir @break
                        @case('late') Terlambat @break
                        @case('absent') Absen @break
                        @default Pending
                    @endswitch
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
