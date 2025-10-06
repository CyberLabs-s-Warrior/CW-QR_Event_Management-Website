<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Models\EventRegistrationLink;
use App\Models\User;
use App\Models\DefaultInputRegistrationStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    private function autoUpdateEventStatus()
    {
        Event::where('status', 'active')
            ->where('end_date', '<', now())
            ->update(['status' => 'done']);
    }

    private function autoUpdateRegistrationLinkStatus()
    {
        EventRegistrationLink::where('status', 'open')
            ->where('valid_until', '<', now())
            ->update(['status' => 'closed']);
    }

    public function index()
    {
        $this->autoUpdateEventStatus();
        $this->autoUpdateRegistrationLinkStatus();

        $user = auth()->user();

        if ($user->role === 'super_admin') {
            $events = Event::with(['registrationLink', 'createdBy', 'eventCategory'])->get();
        } else {
            $events = Event::with(['registrationLink', 'createdBy', 'eventCategory'])
                ->where('created_by', $user->id)
                ->orWhereHas('admins', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->get();
        }

        return view('admin.event.index', compact('events'));
    }

    public function create()
    {
        $existingLinks = EventRegistrationLink::pluck('link')->toArray();

        return view('admin.event.form', [
            'event' => new Event(), // kalo create, kita buat instance baru
            'isEdit' => false, // menandakan ini adalah form untuk membuat event baru
            'users' => User::all(),
            'existingLinks' => $existingLinks,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'required|string|max:255',
            'location' => 'required|string|max:150',
            'event_category_id' => 'required|exists:event_categories,id',
            'start_date' => 'required|date_format:Y-m-d\TH:i',
            'end_date' => 'required|date_format:Y-m-d\TH:i|after_or_equal:start_date',
            'banner' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'qr_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'admins' => 'required|array|min:1',
            'admins.*' => 'exists:users,id',
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'title.required' => 'Judul event wajib diisi.',
            'title.max' => 'Judul event maksimal 100 karakter.',
            'description.required' => 'Deskripsi event wajib diisi.',
            'description.max' => 'Deskripsi event maksimal 255 karakter.',
            'location.required' => 'Lokasi event wajib diisi.',
            'location.max' => 'Lokasi event maksimal 150 karakter.',
            'event_category_id.required' => 'Kategori event wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            'banner.required' => 'Banner event wajib diunggah.',
            'banner.image' => 'Banner harus berupa gambar.',
            'banner.max' => 'Ukuran banner maksimal 2MB.',
            'qr_logo.image' => 'QR Logo harus berupa gambar.',
            'qr_logo.max' => 'Ukuran QR Logo maksimal 2MB.',
            'admins.required' => 'Minimal harus ada satu admin pendamping.',
            'admins.*.exists' => 'Admin yang dipilih tidak valid.',
            'photos.*.image' => 'Setiap foto harus berupa gambar.',
            'photos.*.max' => 'Ukuran setiap foto maksimal 2MB.',
        ]);

        // Upload banner
        $bannerPath = $request->hasFile('banner')
            ? $request->file('banner')->store('banners', 'public')
            : null;

        // Upload QR Logo
        $qrLogoPath = $request->hasFile('qr_logo')
            ? $request->file('qr_logo')->store('qr_logos', 'public')
            : null;

        // Simpan event
        $event = Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'event_category_id' => $request->event_category_id,
            'created_by' => auth()->id(),
            'status' => 'active',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'banner' => $bannerPath,
            'qr_logo' => $qrLogoPath ?? null,
        ]);

        // Buat link pendaftaran event
        $link = preg_replace('/[^a-z0-9]+/', '-', strtolower($request->title));
        $link = trim($link, '-');

        // Cek dan buat link unik jika sudah ada
        $baseLink = $link;
        $counter = 1;
        while (EventRegistrationLink::where('link', $link)->exists()) {
            $link = $baseLink . '-' . $counter++;
        }

        // Validasi tanggal valid_until
        $validUntil = Carbon::parse($event->start_date)->subDay()->endOfDay();
        if ($validUntil->isPast()) {
            $validUntil = now()->endOfDay(); // fallback biar ga langsung expired
        }

        EventRegistrationLink::create([
            'event_id' => $event->id,
            'status' => 'open',
            'link' => $link,
            'valid_until' => $validUntil,
        ]);

        DefaultInputRegistrationStatus::create([
            'event_id' => $event->id,
            'input_first_name' => true,
            'input_last_name' => true,
            'input_email' => true,
            'input_phone_number' => true,
            'input_document' => false,
            'show_qr' => false,
        ]);

        // Simpan admin pendamping (jika ada)
        if ($request->has('admins')) {
            $event->admins()->sync($request->admins); // event_admins pivot
        }

        // Upload foto-foto event
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPath = $photo->store('event_photos', 'public');
                $event->eventPhotos()->create(['photo' => $photoPath]);
            }
        }

        return redirect()->route('admin.event.index')->with('success', 'Event berhasil dibuat.');
    }

    public function show(Event $event)
    {
        $this->authorize('view', $event);

        $this->autoUpdateEventStatus();

        $allLinks = EventRegistrationLink::where('id', '!=', optional($event->registrationLink)->id)
            ->pluck('link')
            ->toArray();

        return view('admin.event.show', compact('event', 'allLinks'));
    }

    public function edit(Event $event)
    {
        $this->authorize('update', $event);

        $existingLinks = EventRegistrationLink::pluck('link')->toArray();
        return view('admin.event.form', [
            'event' => $event,
            'isEdit' => true,
            'users' => User::all(),
            'existingLinks' => $existingLinks,
        ]);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $this->authorize('update', $event);

        $request->validate([
            'title' => 'required',
            'description' => 'required|max:255',
            'location' => 'required',
            'event_category_id' => 'required|exists:event_categories,id',
            'status' => 'required|in:active,done,cancelled',
            'start_date' => 'required|date_format:Y-m-d\TH:i',
            'end_date' => 'required|date_format:Y-m-d\TH:i|after_or_equal:start_date',
            'banner' => 'nullable|image|max:2048',
            'qr_logo' => 'nullable|image|max:2048',
            'admins' => 'nullable|array',
            'admins.*' => 'exists:users,id',
            'photos.*' => 'nullable|image|max:2048',
        ]);

        // Banner
        $bannerPath = $event->banner;
        if ($request->hasFile('banner')) {
            if ($event->banner && Storage::disk('public')->exists($event->banner)) {
                Storage::disk('public')->delete($event->banner);
            }
            $bannerPath = $request->file('banner')->store('banners', 'public');
        }

        // QR Logo
        $qrLogoPath = $event->qr_logo;

        // Jika user hapus QR logo
        if ($request->remove_qr_logo == "1") {
            if ($event->qr_logo && Storage::disk('public')->exists($event->qr_logo)) {
                Storage::disk('public')->delete($event->qr_logo);
            }
            $qrLogoPath = null;
        }

        // Jika upload baru
        if ($request->hasFile('qr_logo')) {
            if ($event->qr_logo && Storage::disk('public')->exists($event->qr_logo)) {
                Storage::disk('public')->delete($event->qr_logo);
            }
            $qrLogoPath = $request->file('qr_logo')->store('qr_logos', 'public');
        }

        // Update event
        $event->update([
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'event_category_id' => $request->event_category_id,
            // 'updated_by' => auth()->id(),
            'status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'banner' => $bannerPath,
            'qr_logo' => $qrLogoPath,
        ]);

        // Update admin pendamping
        $event->admins()->sync($request->admins ?? []);

        // Upload foto-foto baru
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPath = $photo->store('event_photos', 'public');
                $event->eventPhotos()->create(['photo' => $photoPath]);
            }
        }

        // Hapus foto lama yang dipilih
        if ($request->has('removed_photos')) {
            foreach ($request->removed_photos as $photoId) {
                $photo = EventPhoto::find($photoId);
                if ($photo) {
                    if (Storage::disk('public')->exists($photo->photo)) {
                        Storage::disk('public')->delete($photo->photo);
                    }
                    $photo->delete();
                }
            }
        }

        //! KESALAHAN logika di bawah ini, karena valid_until harusnya di-handle di update link, bukan di update event
        // // Kalau event punya registration link
        // if ($event->registrationLink) {
        //     // Kalau admin tidak isi manual di modal, set default ke H-1 start_date
        //     if (!$request->has('valid_until') || empty($request->valid_until)) {
        //         $event->registrationLink->update([
        //             'valid_until' => Carbon::parse($event->start_date)->subDay()->endOfDay(),
        //         ]);
        //     }
        // }

        return redirect()->route('admin.event.show', $event->id)->with('success', 'Event berhasil diperbarui.');
    }
    public function updateRegistrationLink(Request $request, $id)
    {
        $link = EventRegistrationLink::where('id', $id)->firstOrFail();

        $this->authorize('update',$link);

        $request->validate([
            'link' => [
                'required',
                'string',
                'max:255',
                Rule::unique('event_registration_links', 'link')->ignore($link->id),
            ],
            'valid_until' => 'required|date',
        ]);

        $link = EventRegistrationLink::findOrFail($link->id);

        $validUntil = Carbon::parse($request->valid_until);
        $status = $validUntil->isPast() ? 'closed' : 'open';

        $link->update([
            'link' => $request->link,
            'valid_until' => $request->valid_until,
            'status' => $status,
        ]);

        return redirect()->back()->with('success', 'Registration link berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        // $this->authorize('delete', $event);

        // if ($event->banner && Storage::disk('public')->exists($event->banner)) {
        //     Storage::disk('public')->delete($event->banner);
        // }

        // if ($event->qr_logo && Storage::disk('public')->exists($event->qr_logo)) {
        //     Storage::disk('public')->delete($event->qr_logo);
        // }

        // foreach ($event->eventPhotos as $photo) {
        //     if (Storage::disk('public')->exists($photo->photo)) {
        //         Storage::disk('public')->delete($photo->photo);
        //     }
        //     $photo->delete(); // hapus dari database
        // }

        // $event->admins()->detach();

        // if ($event->registrationLink) {
        //     $event->registrationLink->delete();
        // }

        $event->delete();

        return redirect()->route('admin.event.index')->with('success', 'Event berhasil dihapus.');
    }
}
