<?php

namespace App\Http\Controllers;

use App\Jobs\DeleteQrJob;
use App\Models\Event;
use App\Models\Attendee;
use App\Models\Attendance;
use App\Models\EventRegistrationLink;
use App\Models\CustomInputRegistration;
use App\Models\CustomInputRegistrationValue;
use App\Models\DefaultInputRegistrationStatus;
use App\Models\QRCode;
use App\Models\QRCodeLog;
use App\Mail\SendQrCodeMail;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Twilio\Rest\Client;

class EventRegistrationController extends Controller
{
    /**
     * Auto update status link expired
     */
    private function autoUpdateRegistrationLinkStatus()
    {
        EventRegistrationLink::where('status', 'open')
            ->where('valid_until', '<', now())
            ->update(['status' => 'closed']);
    }

    /**
     * Menampilkan form registrasi ke peserta.
     */
    public function showForm($link)
    {
        // sync dulu status link expired
        $this->autoUpdateRegistrationLinkStatus();

        $registrationLink = EventRegistrationLink::with('event')->where('link', $link)->first();

        // Kalau link gak ada atau event-nya udah dihapus
        if (!$registrationLink || !$registrationLink->event) {
            return response()->view('user.form_registration.closed', [
                'event' => null,
                'message' => 'Event tidak tersedia atau sudah dihapus.'
            ]);
        }

        $event = $registrationLink->event;

        // Kalau event-nya sudah selesai atau dibatalkan
        if (in_array($event->status, ['done', 'cancelled'])) {
            return response()->view('user.form_registration.closed', [
                'event' => $event,
                'message' => 'Event sudah selesai atau dibatalkan.'
            ]);
        }

        // Kalau link-nya sudah ditutup
        if ($registrationLink->status === 'closed') {
            return response()->view('user.form_registration.closed', [
                'event' => $registrationLink->event,
                'message' => 'Pendaftaran untuk event ini sudah ditutup.'
            ]);
        }

        $defaultConfig = DefaultInputRegistrationStatus::where('event_id', $event->id)->first();
        $documentLabel = 'Dokumen Tambahan';

        // cari dari custom input (label override)
        $overrideInput = CustomInputRegistration::where('event_id', $event->id)
            ->where('name', 'input_document')
            ->where('type', 'file')
            ->where('status', true)
            ->first();

        if ($overrideInput) {
            $documentLabel = $overrideInput->label;
        }

        $defaultInputs = [];

        if ($defaultConfig) {
            // Gabung label kalau cuma salah satu yang aktif
            $nameCombined = collect([
                $defaultConfig->input_first_name,
                $defaultConfig->input_last_name
            ])->filter()->count() === 1;

            if ($defaultConfig->input_first_name) {
                $defaultInputs[] = [
                    'label' => $nameCombined ? 'Nama' : 'Nama Depan',
                    'name' => 'first_name',
                    'type' => 'text',
                    'required' => true
                ];
            }

            if ($defaultConfig->input_last_name) {
                $defaultInputs[] = [
                    'label' => $nameCombined ? 'Nama' : 'Nama Belakang',
                    'name' => 'last_name',
                    'type' => 'text',
                    'required' => true
                ];
            }

            if ($defaultConfig->input_email) {
                $defaultInputs[] = [
                    'label' => 'Email',
                    'name' => 'email',
                    'type' => 'email',
                    'required' => true
                ];
            }

            if ($defaultConfig->input_phone_number) {
                $defaultInputs[] = [
                    'label' => 'No. HP',
                    'name' => 'phone_number',
                    'type' => 'text',
                    'required' => true
                ];
            }

            if ($defaultConfig->input_document) {
                $defaultInputs[] = [
                    'label' => $documentLabel,
                    'name' => 'input_document',
                    'type' => 'file',
                    'required' => false
                ];
            }
        }

        $customInputs = CustomInputRegistration::where('event_id', $event->id)
            ->where('status', true)
            ->get();

        return view('user.form_registration.form', compact('event', 'defaultInputs', 'customInputs'));
    }

    /**
     * Generate QR Code untuk peserta.
     */

    private function generateQRCodeAsBase64($text, $event)
    {
        $logoPath = null;

        // Kalau ada logo di event & filenya ada di storage
        if (!empty($event->qr_logo) && Storage::disk('public')->exists($event->qr_logo)) {
            $logoPath = storage_path('app/public/' . $event->qr_logo);
        }

        // Build config dasar
        $builderParams = [
            'writer' => new PngWriter(),
            'writerOptions' => [],
            'validateResult' => false,
            'data' => $text,
            'encoding' => new Encoding('UTF-8'),
            'errorCorrectionLevel' => ErrorCorrectionLevel::High,
            'size' => 300,
            'margin' => 10,
            'roundBlockSizeMode' => RoundBlockSizeMode::Margin,
        ];

        // Tambahkan logo kalau ada
        if ($logoPath) {
            $builderParams['logoPath'] = $logoPath;
            $builderParams['logoResizeToWidth'] = 75;
            $builderParams['logoPunchoutBackground'] = true;
        }

        $builder = new Builder(...$builderParams);

        $result = $builder->build();

        return [
            'dataUri' => $result->getDataUri(),
            'binary' => $result->getString()
        ];
    }

    /**
     * Menyimpan data registrasi peserta.
     */
    public function submit(Request $request, $link)
    {
        $registrationLink = EventRegistrationLink::where('link', $link)->firstOrFail();
        $event = $registrationLink->event;
        $eventId = $event->id;

        // Validasi input seperti sebelumnya
        $defaultInputs = DefaultInputRegistrationStatus::where('event_id', $eventId)->first();
        $customInputs = CustomInputRegistration::where('event_id', $eventId)->where('status', true)->get();

        $rules = [];
        if ($defaultInputs->input_first_name)
            $rules['first_name'] = 'required|string|max:255';
        if ($defaultInputs->input_last_name)
            $rules['last_name'] = 'nullable|string|max:255';
        if ($defaultInputs->input_email)
            $rules['email'] = 'required|email';
        if ($defaultInputs->input_phone_number)
            $rules['phone_number'] = 'required|string';
        if ($defaultInputs->input_document)
            $rules['input_document'] = 'nullable|file|mimes:pdf,jpg,png|max:2048';

        foreach ($customInputs as $input) {
            if ($input->type === 'select_multiple') {
                $rules['custom.' . $input->name] = $input->is_required ? 'required|array' : 'nullable|array';
                $rules['custom.' . $input->name . '.*'] = 'string';
            } else {
                $rules['custom.' . $input->name] = $input->is_required ? 'required|string' : 'nullable|string';
            }
        }

        $validated = $request->validate($rules);

        // Cek duplikasi data (first_name, last_name, email, phone_number) di event yang sama
        $exists = Attendee::where('event_id', $eventId)
            ->where('first_name', $request->first_name)
            ->where('last_name', $request->last_name)
            ->where('email', $request->email)
            ->where('phone_number', $request->phone_number)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'duplicate' => 'Data sudah pernah terdaftar di event ini!'
            ])->withInput();
        }

        // Simpan attendee
        $attendee = Attendee::create([
            'event_id' => $eventId,
            'first_name' => $request->first_name ?? null,
            'last_name' => $request->last_name ?? null,
            'email' => $request->email ?? null,
            'phone_number' => $request->phone_number ?? null,
            'input_document' => $request->input_document ?? null,
            'code' => strtoupper(Str::random(20)),
        ]);

        if ($request->hasFile('input_document')) {
            $path = $request->file('input_document')->store('documents', 'public');
            $attendee->update(['input_document' => $path]);
        }

        // Simpan attendance record
        Attendance::create([
            'attendee_id' => $attendee->id,
            'event_id' => $eventId,
            'status' => 'absent',
            'check_in_time' => null,
            'notes' => null,
        ]);

        // Generate data QR
        $qrcodeData = $attendee->id . $attendee->code . 'event' . $eventId;
        $namePart = $attendee->first_name ?: $attendee->last_name ?: 'QR';
        $namePart = preg_replace('/[^A-Za-z0-9_\-]/', '_', $namePart);
        $qrFilename = 'QR_' . $attendee->id . '_' . $namePart . '_' . $attendee->code . '.png';

        // Generate QR (Data URI + binary untuk lampiran email)
        $qrResult = $this->generateQRCodeAsBase64($qrcodeData, $event);

        // Simpan QR data ke tabel qr_codes
        $qrCode = QRCode::create([
            'event_id' => $eventId,
            'attendee_id' => $attendee->id,
            'qrcode_data' => $qrcodeData,
            'valid_until' => $event->end_date,
        ]);

        // Simpan log setelah QR code dibuat
        QRCodeLog::create([
            'qr_code_id' => $qrCode->id,
            'attendee_id' => $attendee->id,
            'user_id' => auth()->id(),
            'status' => 'Not Scanned',
        ]);

        // Simpan custom input values
        $customValues = $request->input('custom', []);
        foreach ($customInputs as $input) {
            $value = $customValues[$input->name] ?? null;

            if ($value !== null) {
                // kalau multiple → gabung jadi string
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                CustomInputRegistrationValue::create([
                    'custom_input_id' => $input->id,
                    'event_id' => $eventId,
                    'attendee_id' => $attendee->id,
                    'name' => $input->name,
                    'value' => $value,
                ]);
            }
        }

        // ======== KONDISI PENGIRIMAN QR ========

        // 1. Kirim email kalau ada
        if (!empty($attendee->email)) {
            \Mail::to($attendee->email)->send(
                new SendQrCodeMail($event, $attendee, $qrResult['binary'], $qrFilename)
            );
        }

        // 2. Kirim WA kalau ada
        if (!empty($attendee->phone_number)) {
            $twilioSid = env('TWILIO_SID');
            $twilioToken = env('TWILIO_TOKEN');
            $twilioWhatsappFrom = env('TWILIO_FROM');

            if ($twilioSid && $twilioToken && $twilioWhatsappFrom) {
                $client = new Client($twilioSid, $twilioToken);

                try {
                    $qrPath = 'qrcodes/' . $qrFilename;
                    Storage::disk('public')->put($qrPath, $qrResult['binary']);
                    $qrUrl = asset('storage/' . $qrPath);

                    // Format tanggal mulai & selesai event
                    $startDate = Carbon::parse($event->start_date)->locale('id')->translatedFormat('l, d F Y H:i'); // Example: Senin, 01 Januari 2024 14:00
                    $endDate = Carbon::parse($event->end_date)->locale('id')->translatedFormat('l, d F Y H:i'); // Example: Senin, 01 Januari 2024 16:00

                    // Nama lengkap atau fallback ke "Peserta"
                    $fullName = trim(($attendee->first_name ?: '') . ' ' . ($attendee->last_name ?: '')) ?: 'Peserta';

                    // Pesan WhatsApp
                    $body = "Halo *{$fullName}!* 🎉\n\n";
                    $body .= "Terima kasih sudah mendaftar di *{$event->title}*.\n\n";
                    $body .= "📅 Jadwal:\n";
                    $body .= "   • Mulai: *{$startDate}*\n";
                    $body .= "   • Selesai: *{$endDate}*\n\n";
                    $body .= "📍 Lokasi: *{$event->location}*\n\n";
                    $body .= "Silakan gunakan QR Code berikut untuk absensi event.\n\n";
                    $body .= "_*Jangan bagikan QR ini ke orang lain.*_";

                    $client->messages->create(
                        "whatsapp:{$attendee->phone_number}",
                        [
                            'from' => "whatsapp:$twilioWhatsappFrom",
                            'body' => $body,
                            'mediaUrl' => [$qrUrl],
                        ]
                    );

                    // Dispatch job delete dengan delay 1 menit
                    dispatch(new DeleteQrJob($qrPath))
                        ->delay(now()->addMinute());
                } catch (Exception $e) {
                    return response()->json([
                        'message' => 'Failed to send WhatsApp message',
                        'error' => $e->getMessage(),
                        'phone_formatted' => $request->phone_number,
                    ], 500);
                }
            }
        }

        // 3. Kalau gak ada email & gak ada WA → tampilkan QR di blade
        if (empty($attendee->email) && empty($attendee->phone_number)) {
            return view('user.form_registration.thankyou', [
                'event' => $event,
                'attendee' => $attendee,
                'showQr' => true,
                'qrData' => $qrResult['dataUri']
            ]);
        }

        $defaultConfig = DefaultInputRegistrationStatus::where('event_id', $eventId)->first();
        $showQrOnPage = $defaultConfig->show_qr ?? false;

        return view('user.form_registration.thankyou', [
            'event' => $event,
            'attendee' => $attendee,
            'showQr' => $showQrOnPage,
            'qrData' => $qrResult['dataUri'] ?? null
        ]);
    }

    /**
     * Admin: Tampilkan form edit input registrasi (default + custom).
     */
    public function editInputs(Event $event)
    {
        $this->authorize('update', $event);

        $defaultInputs = DefaultInputRegistrationStatus::firstOrCreate(['event_id' => $event->id]);
        $customInputs = CustomInputRegistration::where('event_id', $event->id)->get();

        return view('admin.event_registration.admin_input_form', compact('event', 'defaultInputs', 'customInputs'));
    }

    /**
     * Admin: Simpan pengaturan input (default + custom).
     */
    public function updateInputs(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $default = DefaultInputRegistrationStatus::updateOrCreate(
            ['event_id' => $event->id],
            [
                'input_document' => $request->has('input_document'),
                'input_first_name' => $request->has('input_first_name'),
                'input_last_name' => $request->has('input_last_name'),
                'input_email' => $request->has('input_email'),
                'input_phone_number' => $request->has('input_phone_number'),
                'show_qr' => $request->has('show_qr'),
            ]
        );

        // Ambil semua custom input lama dari DB
        $existingCustomInputs = CustomInputRegistration::where('event_id', $event->id)->get()->keyBy('id');

        // Custom input dari request
        $submittedInputs = $request->input('custom_inputs', []);

        // Loop input yang dikirim (update/create)
        foreach ($submittedInputs as $id => $data) {
            $status = isset($data['status']) && $data['status'] == '1';

            $customInput = CustomInputRegistration::find($id);

            if ($customInput) {
                // Record sudah ada → cuma update
                $customInput->update([
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'status' => $status,
                    'updated_by' => Auth::id(),
                ]);
            } else {
                // Record baru → set created_by & updated_by
                CustomInputRegistration::create([
                    'event_id' => $event->id,
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'options' => $data['options'] ?? null,
                    'status' => $status,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            // Hapus dari list existing karena sudah diproses
            $existingCustomInputs->forget($id);
        }

        // Sisanya berarti tidak dikirim → status jadi false
        foreach ($existingCustomInputs as $input) {
            $input->update([
                'status' => false,
                'updated_by' => Auth::id() ?? 1,
            ]);
        }

        $deletedIds = array_filter(explode(',', $request->input('deleted_input_ids', '')));
        if (count($deletedIds)) {
            CustomInputRegistration::where('event_id', $event->id)
                ->whereIn('id', $deletedIds)
                ->delete();
        }

        return redirect()
            ->route('admin.event.show', $event->id)
            ->with('success', 'Pengaturan input berhasil diperbarui.');
    }
}
