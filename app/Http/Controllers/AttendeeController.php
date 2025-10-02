<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Attendee;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\AttendeesExport;
use Illuminate\Http\Request;

class AttendeeController extends Controller
{
    public function index(Event $event)
    {
        $this->authorize('view', $event);

        $attendees = $event->attendees()->with('attendance')->get();
        return view('admin.attendee.index', compact('event', 'attendees'));
    }

    public function edit(Attendee $attendee)
    {
        $this->authorize('update', $attendee->event);

        return view('admin.attendee.edit', compact('attendee'));
    }

    public function update(Request $request, Attendee $attendee)
    {
        $this->authorize('update', $attendee->event);

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $attendee->update($request->only(['first_name', 'last_name', 'email', 'phone_number']));

        return redirect()
            ->route('admin.attendee.index', $attendee->event_id)
            ->with('success', 'Data attendee berhasil diperbarui.');
    }

    public function show(Attendee $attendee)
    {
        $this->authorize('view', $attendee->event);

        $attendee->load([
            'event',
            'qrCodeLogs' => fn($q) => $q->latest(),
            'customInputs'
        ]);

        $attendance = $attendee->qrCodeLogs->first();
        $event = $attendee->event;

        return view('admin.attendee.show', compact('attendee', 'attendance', 'event'));
    }

    public function exportExcel(Event $event)
    {
        $this->authorize('view', $event);

        return Excel::download(
            new AttendeesExport($event),
            'daftar_peserta_event_' . str_replace(' ', '_', strtolower($event->title)) . '.xlsx'
        );
    }

    public function exportPdf(Event $event)
    {
        $this->authorize('view', $event);

        $pdf = Pdf::loadView('exports.attendees-pdf', [
            'event' => $event,
            'attendees' => $event->attendees()->with('attendance')->get(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            'daftar_peserta_event_' . str_replace(' ', '_', strtolower($event->title)) . '.pdf'
        );
    }

    public function destroy(Attendee $attendee)
    {
        $this->authorize('delete', $attendee->event);

        $attendee->delete();

        return redirect()->back()->with('success', 'Data attendee berhasil dihapus.');
    }
}
