@extends('layouts.layout')

@section('title', $isEdit ? 'Edit Event' : 'Tambah Event')

@section('content')
    <div class="dashboard-main-body">
        <x-breadcrumb>
            <x-slot:title>{{ $isEdit ? 'Edit Event' : 'Tambah Event' }}</x-slot:title>
            <x-slot:icon>solar:calendar-add-broken</x-slot:icon>
        </x-breadcrumb>

        @include('layouts.feedback')

        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ $isEdit ? 'Edit Event' : 'Tambah Event' }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ $isEdit ? route('admin.event.update', $event->id) : route('admin.event.store') }}"
                        method="POST" enctype="multipart/form-data" class="row gy-3 needs-validation" novalidate>
                        @csrf
                        @if ($isEdit)
                            @method('PUT')
                        @endif

                        {{-- Judul --}}
                        <div class="col-md-6">
                            <label class="form-label">Judul</label>
                            <input type="text" name="title" value="{{ old('title', $event->title) }}"
                                class="form-control" required>
                            @error('title')
                                <div style="color:red">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Kategori --}}
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select name="event_category_id" class="form-select" required>
                                <option value="">Pilih Kategori</option>
                                @foreach (\App\Models\EventCategory::all() as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('event_category_id', $event->event_category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('event_category_id')
                                <div style="color:red">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Lokasi --}}
                        <div class="col-md-6">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="location" value="{{ old('location', $event->location) }}"
                                class="form-control" required>
                            @error('location')
                                <div style="color:red">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tanggal Mulai --}}
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="text" id="start_date" name="start_date"
                                value="{{ old('start_date', optional($event->start_date)->format('Y-m-d H:i')) }}"
                                class="form-control flatpickr-datetime"
                                placeholder="Pilih Tanggal dan Waktu untuk memulai event" required>
                            @error('start_date')
                                <div style="color:red">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tanggal Selesai --}}
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="text" id="end_date" name="end_date"
                                value="{{ old('end_date', optional($event->end_date)->format('Y-m-d H:i')) }}"
                                class="form-control flatpickr-datetime"
                                placeholder="Pilih Tanggal dan Waktu untuk mengakhiri event" required>
                            @error('end_date')
                                <div style="color:red">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Deskripsi --}}
                        <div class="col-md-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3" required>{{ old('description', $event->description) }}</textarea>
                            @error('description')
                                <div style="color:red">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Status --}}
                        @if ($isEdit)
                            {{-- Status hanya muncul saat edit --}}
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="">Pilih Status</option>
                                    <option value="active"
                                        {{ old('status', $event->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="done" {{ old('status', $event->status) == 'done' ? 'selected' : '' }}>
                                        Done</option>
                                    <option value="cancelled"
                                        {{ old('status', $event->status) == 'cancelled' ? 'selected' : '' }}>Cancelled
                                    </option>
                                </select>
                                @error('status')
                                    <div style="color:red">{{ $message }}</div>
                                @enderror
                            </div>
                        @else
                            <input type="hidden" name="status" value="active">
                        @endif

                        {{-- Admin --}}
                        {{-- <input type="hidden" name="created_by" value="{{ auth()->user()->id }}">
                        <div class="col-md-6">
                            <label class="form-label">Administrator Event</label>
                            <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                        </div> --}}

                        {{-- Banner Upload --}}
                        <div class="upload-image-wrapper d-flex align-items-center gap-4">
                            <div
                                class="uploaded-img {{ $event->banner ? '' : 'd-none' }} position-relative h-120-px w-120-px border input-form-light radius-8 overflow-hidden border-dashed bg-neutral-50">
                                <button type="button"
                                    class="uploaded-img__remove position-absolute top-0 end-0 z-1 text-2xxl line-height-1 me-8 mt-8 d-flex">
                                    {{-- <iconify-icon icon="radix-icons:cross-2" class="text-xl text-danger-600"></iconify-icon> --}}
                                </button>
                                <img id="uploaded-img__preview" class="w-100 h-100 object-fit-cover"
                                    src="{{ $event->banner ? asset('storage/' . $event->banner) : '#' }}" alt="image">
                            </div>

                            <label
                                class="upload-file h-120-px w-120-px border input-form-light radius-8 overflow-hidden border-dashed bg-neutral-50 bg-hover-neutral-200 d-flex align-items-center flex-column justify-content-center gap-1"
                                for="upload-file">
                                <iconify-icon icon="solar:camera-outline"
                                    class="text-xl text-secondary-light"></iconify-icon>
                                <span class="fw-semibold text-secondary-light">Upload</span>
                                <span class="fw-semibold text-secondary-light">Banner</span>
                                <input id="upload-file" type="file" name="banner" hidden accept="image/*">
                            </label>
                        </div>

                        @error('banner')
                            <div style="color:red">{{ $message }}</div>
                        @enderror

                        {{-- QR Logo Upload --}}
                        <div class="upload-image-wrapper d-flex align-items-center gap-4">
                            {{-- Preview gambar yang sudah diupload --}}
                            <div
                                class="uploaded-img {{ $isEdit && $event->qr_logo ? '' : 'd-none' }} position-relative h-120-px w-120-px border input-form-light radius-8 overflow-hidden border-dashed bg-neutral-50">
                                <button type="button"
                                    class="uploaded-img__remove position-absolute top-0 end-0 z-1 text-2xxl line-height-1 me-8 mt-8 d-flex"
                                    onclick="removeQrLogo(this)">
                                    <iconify-icon icon="radix-icons:cross-2" class="text-xl text-danger-600"></iconify-icon>
                                </button>
                                <img id="qr-logo-preview" class="w-100 h-100 object-fit-cover"
                                    src="{{ $isEdit && $event->qr_logo ? asset('storage/' . $event->qr_logo) : '#' }}"
                                    alt="QR Logo">
                            </div>

                            {{-- Tombol upload --}}
                            <label
                                class="upload-file h-120-px w-120-px border input-form-light radius-8 overflow-hidden border-dashed bg-neutral-50 bg-hover-neutral-200 d-flex align-items-center flex-column justify-content-center gap-1"
                                for="qr_logo_input">
                                <iconify-icon icon="solar:camera-outline"
                                    class="text-xl text-secondary-light"></iconify-icon>
                                <span class="fw-semibold text-secondary-light">Upload</span>
                                <span class="fw-semibold text-secondary-light">QR Logo</span>
                                <input id="qr_logo_input" type="file" name="qr_logo" hidden accept="image/*"
                                    onchange="previewQrLogo(event)">
                            </label>

                            {{-- Hidden input untuk menandai hapus file lama --}}
                            <input type="hidden" name="remove_qr_logo" id="remove_qr_logo" value="0">
                        </div>

                        @error('qr_logo')
                            <div style="color:red">{{ $message }}</div>
                        @enderror

                        {{-- Admin Pendamping --}}
                        <div class="col-md-12">
                            <label class="form-label mb-2">Administrator</label>
                            <div class="border p-3 rounded bg-light">
                                <div class="row g-2">
                                    <select id="admins" name="admins[]" multiple>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}"
                                                {{ in_array($user->id, old('admins', $event->admins->pluck('id')->toArray() ?? [])) ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @error('admins')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12" style="padding-left: 16px;">
                            <label class="form-label mb-2">Upload Foto Tambahan</label>
                            <div class="d-flex flex-wrap gap-3 mb-3">
                                <div class="uploaded-imgs-container d-flex gap-3 flex-wrap">
                                    @if ($isEdit && $event->eventPhotos && $event->eventPhotos->count())
                                        @foreach ($event->eventPhotos as $photo)
                                            <div class="existing-photo-wrapper position-relative h-120-px w-120-px border input-form-light radius-8 overflow-hidden border-dashed bg-neutral-50"
                                                data-photo-id="{{ $photo->id }}">
                                                <button type="button"
                                                    class="uploaded-img__remove position-absolute top-0 end-0 z-1 text-2xxl line-height-1 me-8 mt-8 d-flex">
                                                    <iconify-icon icon="radix-icons:cross-2"
                                                        class="text-xl text-danger-600"></iconify-icon>
                                                </button>
                                                <img class="w-100 h-100 object-fit-cover"
                                                    src="{{ asset('storage/' . $photo->photo) }}" alt="Foto">
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <label
                                    class="upload-file-multiple h-120-px w-120-px border input-form-light radius-8 overflow-hidden border-dashed bg-neutral-50 bg-hover-neutral-200 d-flex align-items-center flex-column justify-content-center gap-1"
                                    for="upload-file-multiple">
                                    <iconify-icon icon="solar:camera-outline"
                                        class="text-xl text-secondary-light"></iconify-icon>
                                    <span class="fw-semibold text-secondary-light">Upload</span>
                                    <span class="fw-semibold text-secondary-light">Photo</span>
                                    <input id="upload-file-multiple" type="file" name="photos[]" multiple hidden
                                        accept="image/*">
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button class="btn btn-primary-600"
                                type="submit">{{ $isEdit ? 'Update' : 'Simpan' }}</button>
                            <a href="{{ route('admin.event.index') }}" class="btn btn-danger-600">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('beforeAppScripts')
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Optional: Tema dark/material -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Tom Select CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css">

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <!-- Tom Select CSS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

    <script>
        flatpickr(".flatpickr-datetime", {
            enableTime: true,
            dateFormat: "Y-m-d\\TH:i",
            time_24hr: true,
            locale: "id"
        });

        new TomSelect("#admins", {
            plugins: ['remove_button'],
            persist: false,
            create: false,
            placeholder: "Pilih administrator",
        });

        // Preview banner
        const fileInput = document.getElementById("upload-file");
        const imagePreview = document.getElementById("uploaded-img__preview");
        const uploadedImgContainer = document.querySelector(".uploaded-img");
        const removeButton = document.querySelector(".uploaded-img__remove");

        fileInput?.addEventListener("change", (e) => {
            if (e.target.files.length) {
                const src = URL.createObjectURL(e.target.files[0]);
                imagePreview.src = src;
                uploadedImgContainer.classList.remove('d-none');
            }
        });
        removeButton?.addEventListener("click", () => {
            imagePreview.src = "";
            uploadedImgContainer.classList.add('d-none');
            fileInput.value = "";
        });

        function previewQrLogo(event) {
            const file = event.target.files[0];
            if (file) {
                const wrapper = event.target.closest('.upload-image-wrapper');
                const previewDiv = wrapper.querySelector('.uploaded-img');
                const previewImg = previewDiv.querySelector('img');

                previewImg.src = URL.createObjectURL(file);
                previewDiv.classList.remove('d-none');

                // Jika upload baru, pastikan flag hapus direset
                wrapper.querySelector('#remove_qr_logo').value = "0";
            }
        }

        function removeQrLogo(button) {
            const wrapper = button.closest('.upload-image-wrapper');
            wrapper.querySelector('input[type=file]').value = '';
            wrapper.querySelector('.uploaded-img').classList.add('d-none');
            wrapper.querySelector('img').src = '#';

            // Tandai bahwa file lama dihapus
            wrapper.querySelector('#remove_qr_logo').value = "1";
        }

        // Preview multiple image
        const fileInputMultiple = document.getElementById("upload-file-multiple");
        const uploadedImgsContainer = document.querySelector(".uploaded-imgs-container");

        fileInputMultiple?.addEventListener("change", (e) => {
            const files = e.target.files;

            Array.from(files).forEach(file => {
                const src = URL.createObjectURL(file);

                const imgContainer = document.createElement('div');
                imgContainer.classList.add('position-relative', 'h-120-px', 'w-120-px', 'border',
                    'input-form-light', 'radius-8', 'overflow-hidden', 'border-dashed', 'bg-neutral-50');

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.classList.add('uploaded-img__remove', 'position-absolute', 'top-0', 'end-0',
                    'z-1', 'text-2xxl', 'line-height-1', 'me-8', 'mt-8', 'd-flex');
                removeButton.innerHTML =
                    '<iconify-icon icon="radix-icons:cross-2" class="text-xl text-danger-600"></iconify-icon>';

                const imagePreview = document.createElement('img');
                imagePreview.classList.add('w-100', 'h-100', 'object-fit-cover');
                imagePreview.src = src;

                imgContainer.appendChild(removeButton);
                imgContainer.appendChild(imagePreview);
                uploadedImgsContainer.appendChild(imgContainer);

                removeButton.addEventListener('click', () => {
                    URL.revokeObjectURL(src);
                    imgContainer.remove();
                });
            });

            // fileInputMultiple.value = ''; // Opsional
        });

        const allLinks = @json($existingLinks);
        // const titleInput = document.querySelector('input[name="title"]');
        // const linkInput = document.getElementById('link');
        const isEdit = {{ $isEdit ? 'true' : 'false' }};
        const currentLink = '{{ optional($event->registrationLink)->link }}';

        function slugify(text) {
            return text.toString().toLowerCase()
                .replace(/\s+/g, '-') // replace spaces with -
                .replace(/[^\w\-]+/g, '') // remove all non-word chars
                .replace(/\-\-+/g, '-') // replace multiple - with single -
                .replace(/^-+/, '') // trim - from start
                .replace(/-+$/, ''); // trim - from end
        }

        function generateUniqueLink(base) {
            let newLink = base;
            let counter = 1;
            while (allLinks.includes(newLink) && newLink !== currentLink) {
                newLink = `${base}-${counter}`;
                counter++;
            }
            return newLink;
        }

        // titleInput?.addEventListener('input', function() {
        //     const baseSlug = slugify(this.value);
        //     const uniqueLink = generateUniqueLink(baseSlug);
        //     linkInput.value = uniqueLink;
        // });

        // Optional: validasi akhir saat submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const value = linkInput.value;
            if (allLinks.includes(value) && value !== currentLink) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Link registrasi sudah digunakan',
                    text: 'Harap ubah judul agar menghasilkan link unik',
                });
            }
        });

        // Hapus existing photo secara visual
        document.querySelectorAll('.uploaded-img__remove').forEach(button => {
            button.addEventListener('click', function() {
                const wrapper = this.closest('.existing-photo-wrapper') || this.closest(
                    '.position-relative');
                if (!wrapper) return;

                const photoId = wrapper.getAttribute('data-photo-id');

                if (photoId) {
                    // Cek dulu console.log buat debug
                    console.log('Hapus photo id:', photoId);

                    // Tambahkan input tersembunyi untuk mengirim ID foto yang dihapus
                    const removedInput = document.createElement('input');
                    removedInput.type = 'hidden';
                    removedInput.name = 'removed_photos[]';
                    removedInput.value = photoId;

                    document.querySelector('form').appendChild(removedInput);
                }

                wrapper.remove();
            });
        });
    </script>
@endsection
