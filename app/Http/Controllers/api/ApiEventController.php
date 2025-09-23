<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isEmpty;

class ApiEventController extends Controller
{
    public function getEventById(Request $request, $id)
    {
        $event = Event::with('createdBy', 'eventCategory', 'admins')->find($id);

        $event->attendee_count = $event->countAttendees();
        $event->present_or_late_count = $event->countPresentOrLateAttendees();
        $event->absent_count = $event->countAbsentAttendees();
        if ($event->present_or_late_count + $event->absent_count > 0) {
            $event->attendance_percentage = number_format(($event->present_or_late_count / ($event->present_or_late_count + $event->absent_count)) * 100, 2, '.', '');
        } else {
            $event->attendance_percentage = number_format(0, 2, '.', '');
        }

        $event->attendance_percentage = (float) $event->attendance_percentage;

        if (!$event) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event not found'
            ], 404);
        }

        return response()->json($event);
    }

    public function getAllEvents(Request $request)
    {
        $events = Event::all();

        return response()->json($events);
    }

    public function getUserEvents(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $events = $user->eventAdminAssigned;

        return response()->json([
            $events
        ], 200);
    }

    public function getUserEventsUpcoming(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $events = $user->upcomingEvents()->get();


        return response()->json(
            [
                'status' => 'success',
                'message' => $events->isEmpty() ? 'No upcoming events found for this user' : 'Upcoming events retrieved successfully',

                'meta' => ['count' => $events->count()],
                'data' => EventResource::collection($events)
            ],
            200
        );
    }

    public function getUserEventsDone(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $events = $user->doneEvents()
            ->get();


        return response()->json(
            [
                'status' => 'success',
                'message' => $events->isEmpty() ? 'No past events found for this user' : 'Past events retrieved successfully',
                'meta' => ['count' => $events->count()],
                'data' => EventResource::collection($events)
            ],
            200
        );
    }

    public function getUserEventsOnGoing(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',

                'message' => 'User not found'
            ], 404);
        }

        $events = $user->ongoingEvents()->get();

        


        return response()->json(
            [
                'status' => 'success',
                'message' => $events->isEmpty() ? 'No ongoing events found for this user' : 'Ongoing events retrieved successfully',
                'meta' => ['count' => $events->count()],
                'data' => EventResource::collection($events)
            ],
            200
        );
    }

    public function getEventAttendees(Request $request, $id)
    {
        $attendanceService = new \App\Services\AttendanceService();
        return $attendanceService->getEventAttendees($id);
    }

    public function getUserEventsSummary(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'error' => 'User not found',
                    'message' => 'Authentication token is invalid or has expired'
                ], 401);
            }

            $events = $user->doneEvents->count();
            $upcomingEvents = $user->upcomingEvents->first();
            $ongoingEvent = $user->ongoingEvents->first();
            $currentMonthCount = $user->currentMonthEvents->count();

            return response()->json([
                'status' => 'success',
                'past_count' => $events,
                'upcoming_event' => $upcomingEvents ? $upcomingEvents->title : "No Upcoming Event",
                'ongoing_event' => $user->getOngoingEventAttribute() ?? "No Ongoing Event",
                'ongoing_count' => $user->getOngoingCountAttribute() ?? 0,
                'current_month_count' => $currentMonthCount ?? 0
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal mengambil ringkasan acara',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserEventHistory(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',

                'message' => 'User not found'
            ], 404);
        }

        $eventHistory = $user->eventHistory()
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'location' => $event->location,
                    'category' => optional($event->eventCategory)->name,
                    'created_by' => $event->created_by,
                    'status' => $event->status,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'banner' => $event->banner,
                    'qr_logo' => $event->qr_logo,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                ];
            });

        return response()->json(
            $eventHistory,
            200
        );
    }

    public function search(Request $request, $id)
    {
        try {
            $userId = $id;

            if (empty($userId)) {
                return response()->json([

                    'status' => 'error',
                    'message' => 'User ID is required'
                ], 400);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json([

                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            $query = $request->input('query', '');

            if (!empty($query)) {
                $events = $user->eventAdminAssigned()
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%")
                            ->orWhere('location', 'like', "%{$query}%");
                    })
                    ->get()
                    ->map(function ($event) {
                        return [
                            'id' => $event->id,
                            'title' => $event->title,
                            'description' => $event->description,
                            'location' => $event->location,
                            'category' => optional($event->eventCategory)->name,
                            'created_by' => $event->createdBy->name,
                            'status' => $event->status,
                            'start_date' => $event->start_date,
                            'end_date' => $event->end_date,
                            'banner' => $event->banner,
                            'qr_logo' => $event->qr_logo,
                            'created_at' => $event->created_at,
                            'updated_at' => $event->updated_at,
                        ];
                    });
            } else {
                $events = $user->eventAdminAssigned()->get()->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'description' => $event->description,
                        'location' => $event->location,
                        'category' => optional($event->eventCategory)->name,
                        'created_by' => $event->createdBy->name,
                        'status' => $event->status,
                        'start_date' => $event->start_date,
                        'end_date' => $event->end_date,
                        'banner' => $event->banner,
                        'qr_logo' => $event->qr_logo,
                        'created_at' => $event->created_at,
                        'updated_at' => $event->updated_at,
                    ];
                });
            }

            return response()->json($events, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving user events: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateAttendees(Request $request, $id)
{
    $request->validate([
        'attendees' => 'required|array',
        'attendees.*.id' => 'required|exists:attendees,id',
        'attendees.*.status' => 'required|in:Present,Absent',
    ]);
    
    $attendanceService = new \App\Services\AttendanceService();
    return $attendanceService->updateAttendees($id, $request->attendees, $request->user()->id);
}
}
