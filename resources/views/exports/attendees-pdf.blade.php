<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Daftar Attendee - {{ $event->title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 20px;
        }

        body {
            font-family: "Arial", sans-serif;
            font-size: 12px;
            color: #333;
        }

        h2 {
            margin: 0;
            text-align: center;
            font-size: 18px;
            color: #2c3e50;
        }

        .event-info {
            text-align: center;
            margin-bottom: 15px;
            font-size: 12px;
            color: #555;
        }

        table {
            border-collapse: collapse;
            table-layout: auto;
            width: auto;
            margin: 0 auto;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #2c3e50;
            color: #fff;
            font-size: 12px;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        tr:hover td {
            background: #f1f7ff;
        }

        .status-present {
            color: #27ae60;
            font-weight: bold;
        }

        .status-absent {
            color: #e74c3c;
            font-weight: bold;
        }

        .status-late {
            color: #f39c12;
            font-weight: bold;
        }

        .status-pending {
            color: #7f8c8d;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
            text-align: right;
            color: #777;
        }
    </style>

</head>

<body>
    <h2>Daftar Attendee</h2>
    <div class="event-info">
        <h2>{{ $event->title }}</h2><br>
        Lokasi: {{ $event->location }} <br>
        Tanggal: {{ $event->start_date->format('d M Y') }} - {{ $event->end_date->format('d M Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Lengkap</th>
                <th>Telepon</th>
                <th>Email</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($event->attendees as $attendee)
                <tr>
                    <td>{{ $attendee->id }}</td>
                    <td>{{ $attendee->first_name }} {{ $attendee->last_name }}</td>
                    <td>{{ $attendee->phone_number ?? '-' }}</td>
                    <td>{{ $attendee->email ?? '-' }}</td>
                    <td
                        class="
                        @if ($attendee->attendance?->status === 'present') status-present
                        @elseif($attendee->attendance?->status === 'absent') status-absent
                        @elseif($attendee->attendance?->status === 'late') status-late
                        @else status-pending @endif">
                        {{ ucfirst($attendee->attendance->status ?? 'pending') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d M Y H:i') }}
    </div>
</body>

</html>
