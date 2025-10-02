<?php

namespace App\Exports;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AttendeesExport implements FromView, ShouldAutoSize
{
    protected $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function view(): View
    {
        return view('exports.attendees-excell', [
            'event' => $this->event,
            'attendees' => $this->event->attendees()->with('attendance')->get(),
        ]);
    }
}
