<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\QRCode;
use App\Models\QRCodeLog;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
  public function scanAttendance($eventId, $qrcodeData, $userId)
  {


    /**
     * Retrieves the event details based on the provided parameters.
     * This method is typically used to fetch event information for further processing,
     * such as validating scan actions or displaying event data.
     *
     * @return \Illuminate\Http\Response
     */
    // Get the event
    $event = Event::findOrFail($eventId);

    // Check if event has started
    $now = Carbon::now();
    $eventStarted = $now->isAfter($event->start_date) || $now->isSameDay($event->start_date);
    $eventEnded = $now->isAfter($event->end_date);

    if (!$eventStarted) {
      // Event hasn't started yet
      return response()->json([
        'success' => false,
        'message' => 'Event has not started yet',
      ], 400);
    }

    // Check if event is active but already ended
    if ($event->status === 'active' && $eventEnded) {
      return response()->json([
        'success' => false,
        'message' => 'The event status is active but it has already ended',
      ], 400);
    }

    // Check if event is done/cancelled but still within date range
    if (($event->status === 'done' || $event->status === 'cancelled') && !$eventEnded) {
      return response()->json([
        'success' => false,
        'message' => 'The event is marked as ' . $event->status . ' but the event dates are still ongoing',
      ], 400);
    }

    /**
     * Searches for the specified QR code within the database.
     * This step is essential to verify the existence and validity of the QR code
     * before proceeding with further event-related operations.
     */
    // Find the QR code in database
    $qrCode = QRCode::where('qrcode_data', $qrcodeData)
      ->where('event_id', $eventId)
      ->first();

    if (!$qrCode) {
      // Log invalid scan attempt
      QRCodeLog::create([
        'qr_code_id' => null,
        'attendee_id' => null,
        'user_id' => $userId,
        'status' => 'invalid',
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Invalid QR code',
      ], 400);
    }

    /**
     * Verifies whether the attendance for the current event has already been recorded.
     * Prevents duplicate attendance entries for the same participant and event.
     */
    // Check if attendance already recorded
    $existingAttendance = Attendance::where('attendee_id', $qrCode->attendee_id)
      ->where('event_id', $eventId)
      ->where('status', 'Present')
      ->first();

    if ($existingAttendance) {
      /**
       * Logs an attempt to re-scan a previously scanned event.
       * Useful for tracking duplicate scan attempts and auditing user actions.
       */
      // Log attempted re-scan
      QRCodeLog::create([
        'qr_code_id' => $qrCode->id,
        'attendee_id' => $qrCode->attendee_id,
        'user_id' => $userId,
        'status' => 'Invalid',
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Attendance already recorded for this attendee',
        'data' => [
          'attendee' => $qrCode->attendee,
          'attendance' => $existingAttendance,
          'qr_code' => $qrCode,

        ],
      ], 200);
    }

    /**
     * Checks if the provided QR code exists and is associated with the specified event.
     * If the QR code is not found or does not correspond to the event, handles the error response.
     */
    // If QR code doesn't exist or doesn't match event
    if (!$qrCode) {
      // Log invalid scan attempt
      QRCodeLog::create([
        'qr_code_id' => null,
        'attendee_id' => null,
        'user_id' => $userId,
        'status' => 'invalid',
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Invalid QR code',
      ], 400);
    }

    /**
     * Verifies whether the scanned QR code has expired.
     * This check ensures that only valid, non-expired QR codes are processed for event access.
     * If the QR code is expired, appropriate handling or error response should be triggered.
     */
    // Check if QR code has expired
    if (Carbon::now()->isAfter($qrCode->valid_until)) {
      QRCodeLog::create([
        'qr_code_id' => $qrCode->id,
        'attendee_id' => $qrCode->attendee_id,
        'user_id' => $userId,
        'status' => 'invalid',
      ]);

      return response()->json([
        'success' => false,
        'message' => 'QR code has expired',
      ], 400);
    }

    /**
     * Retrieves the attendee information associated with the provided QR code.
     * This method is used to identify and fetch the attendee details based on the scanned QR code data.
     * Typically called during event check-in to verify attendee registration.
     */
    // Get the attendee associated with this QR code
    $attendee = $qrCode->attendee;

    /**
     * Ensures that the attendee is registered for the current event.
     * This validation step prevents misuse of QR codes across different events,
     * guaranteeing that only attendees registered for this specific event can check in.
     * It helps maintain event integrity and prevents cross-event attendance fraud.
     */
    // Verify that the attendee is registered for this specific event
    // This prevents cross-event attendance fraud by ensuring QR codes 
    // can only be used for their intended event
    if ($attendee->event_id != $eventId) {
      QRCodeLog::create([
        'qr_code_id' => $qrCode->id,
        'attendee_id' => $attendee->id,
        'user_id' => $userId,
        'status' => 'invalid',
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Attendee not registered for this event',
      ], 400);
    }

    try {
      DB::beginTransaction();

      // Log successful QR code scan
      QRCodeLog::create([
        'qr_code_id' => $qrCode->id,
        'attendee_id' => $attendee->id,
        'user_id' => $userId,
        'status' => 'scanned',
      ]);

      $isLate = Carbon::now()->greaterThan($event->start_date->copy()->addMinutes($event->start_date->diffInMinutes($event->end_date) * 0.95)) ? true : false;

      /**
       * Updates an existing attendance record or creates a new one if it does not exist.
       * This method ensures that the attendance information for an event is accurately
       * recorded based on the provided scan data.
       *
       * Typical usage involves scanning a participant's QR code and updating their
       * attendance status for the event.
       */
      // Update or create attendance record


      $attendance = Attendance::updateOrCreate(
        [
          'attendee_id' => $attendee->id,
          'event_id' => $eventId,
        ],
        [
          // event is considered 'Late' if scanned after 95% of event duration has passed
          'status' => Carbon::now()->greaterThan($event->start_date->copy()->addMinutes($event->start_date->diffInMinutes($event->end_date) * 0.95)) ? 'Late' : 'Present',
          'check_in_time' => Carbon::now(),
        ]
      );

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Attendance recorded successfully',
        'data' => [
          'attendee' => $attendee,
          'attendance' => $attendance,
        ],
      ], 200);
    } catch (\Exception $e) {
      DB::rollBack();

      return response()->json([
        'success' => false,
        'message' => 'Failed to record attendance: ' . $e->getMessage(),
      ], 500);
    }
  }

  public function scanIdentityCheck($eventId, $qrcodeData, $userId)
  {
    // Get the event
    $event = Event::findOrFail($eventId);

    // Find the QR code in database
    $qrCode = QRCode::where('qrcode_data', $qrcodeData)
      ->where('event_id', $eventId)
      ->first();

    // If QR code doesn't exist or doesn't match event
    if (!$qrCode) {
      // Log invalid scan attempt in QRCodeLog
      QRCodeLog::create([
        'qr_code_id' => null,
        'attendee_id' => null,
        'user_id' => $userId,
        'status' => 'invalid',
      ]);

      // Log in UserLog
      UserLog::create([
        'user_id' => $userId,
        'action' => "Identity Check Invalid QR for Event ID $eventId with QR Code $qrcodeData",
        'ip_address' => request()->ip(),
        'device_info' => request()->userAgent(),
        'status' => 'failed',
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Invalid QR code',
      ], 400);
    }

    // Check if QR code has expired
    if (Carbon::now()->isAfter($qrCode->valid_until)) {
      // Log expired QR attempt
      QRCodeLog::create([
        'qr_code_id' => $qrCode->id,
        'attendee_id' => $qrCode->attendee_id,
        'user_id' => $userId,
        'status' => 'invalid',
      ]);

      // Log in UserLog
      UserLog::create([
        'user_id' => $userId,
        'action' => "Identity Check Expired QR for Event ID $eventId with QR Code $qrcodeData",
        'ip_address' => request()->ip(),
        'device_info' => request()->userAgent(),
        'status' => 'failed',
      ]);

      return response()->json([
        'success' => false,
        'message' => 'QR code has expired',
      ], 400);
    }

    // Get the attendee associated with this QR code
    $attendee = $qrCode->attendee;

    // Verify that the attendee is registered for this specific event
    if ($attendee->event_id != $eventId) {
      // Log wrong event attempt
      QRCodeLog::create([
        'qr_code_id' => $qrCode->id,
        'attendee_id' => $attendee->id,
        'user_id' => $userId,
        'status' => 'invalid',
      ]);

      // Log in UserLog
      UserLog::create([
        'user_id' => $userId,
        'action' => "Identity Check Wrong Event for Event ID $eventId with QR Code $qrcodeData",
        'ip_address' => request()->ip(),
        'device_info' => request()->userAgent(),
        'status' => 'failed',
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Attendee not registered for this event',
      ], 400);
    }

    // Get attendance record (if exists)
    $attendance = Attendance::where('attendee_id', $attendee->id)
      ->where('event_id', $eventId)
      ->first();

    // Log successful identity check in QRCodeLog
    QRCodeLog::create([
      'qr_code_id' => $qrCode->id,
      'attendee_id' => $attendee->id,
      'user_id' => $userId,
      'status' => 'scanned',
    ]);

    // Log in UserLog
    UserLog::create([
      'user_id' => $userId,
      'action' => "Checked identity for attendee ID {$attendee->id} at event ID {$eventId}",
      'ip_address' => request()->ip(),
      'device_info' => request()->userAgent(),
      'status' => 'success',
    ]);

    // Return attendee and attendance data (without creating/updating anything)
    return response()->json([
      'success' => true,
      'message' => 'Identity verified successfully',
      'data' => [
        'attendee' => $attendee,
        'attendance' => $attendance,
        'qr_code' => $qrCode,
      ],
    ], 200);
  }

  public function getEventAttendees($eventId)
  {
    $event = Event::findOrFail($eventId);
    $attendees = $event->attendees()->with('attendance')->get();
    return response()->json([
      'success' => true,
      'data' => [
        'attendees' => $attendees,
      ],
    ], 200);
  }

  public function updateAttendees($eventId, $attendeesData, $userId)
  {
    try {
      // Verify the event exists
      $event = Event::findOrFail($eventId);

      // Begin transaction
      DB::beginTransaction();

      $updatedCount = 0;
      $notFoundCount = 0;

      foreach ($attendeesData as $attendeeData) {
        $attendeeId = $attendeeData['id'];
        // Laravel best practice: consistent status capitalization
        $status = strtolower($attendeeData['status']) === 'present' ? 'Present' : 'Absent';

        // Check if attendee belongs to this event
        $attendee = $event->attendees()->where('id', $attendeeId)->first();

        if (!$attendee) {
          $notFoundCount++;
          continue; // Skip this attendee
        }

        // Find or create attendance record
        $attendance = Attendance::updateOrCreate(
          [
            'attendee_id' => $attendeeId,
            'event_id' => $eventId,
          ],
          [
            'status' => $status,
            'check_in_time' => ($status === 'Present' && !Attendance::where('attendee_id', $attendeeId)->where('event_id', $eventId)->whereNotNull('check_in_time')->exists())
              ? now()
              : DB::raw('check_in_time'), // Preserve existing check_in_time
          ]
        );

        // Log the update
        UserLog::create([
          'user_id' => $userId,
          'action' => "Updated attendee ID {$attendeeId} status to {$status} for event ID {$eventId}",
          'ip_address' => request()->ip(),
          'device_info' => request()->userAgent(),
          'status' => 'success',
        ]);

        $updatedCount++;
      }

      // Commit transaction
      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Attendees status updated successfully',
        'data' => [
          'total_updated' => $updatedCount,
          'total_not_found' => $notFoundCount,
        ],
      ], 200);
    } catch (\Exception $e) {
      // Rollback transaction if error occurs
      DB::rollBack();

      // Log error
      UserLog::create([
        'user_id' => $userId,
        'action' => "Failed to update attendees for event ID {$eventId}: {$e->getMessage()}",
        'ip_address' => request()->ip(),
        'device_info' => request()->userAgent(),
        'status' => 'failed',
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Failed to update attendees: ' . $e->getMessage(),
      ], 500);
    }
  }
}
