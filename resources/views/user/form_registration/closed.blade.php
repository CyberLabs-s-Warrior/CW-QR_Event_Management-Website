<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Ditutup</title>
    @vite('resources/css/app.css')
</head>

<body class="bg-gradient-to-r from-red-100 via-white to-red-100 flex items-center justify-center min-h-screen font-sans">
    <div class="text-center max-w-2xl px-6">

        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto w-40 h-42 mb-4 bounce-svg" viewBox="0 0 64 64">
            <circle cx="32" cy="32" r="30" fill="#f56565">
                <animate attributeName="cy" values="32;28;32" dur="0.6s" repeatCount="indefinite"
                    keyTimes="0;0.5;1" />
            </circle>

            <line x1="32" y1="18" x2="32" y2="38" stroke="#fff" stroke-width="4"
                stroke-linecap="round">
                <animate attributeName="y1" values="18;14;18" dur="0.6s" repeatCount="indefinite"
                    keyTimes="0;0.5;1" />
                <animate attributeName="y2" values="38;34;38" dur="0.6s" repeatCount="indefinite"
                    keyTimes="0;0.5;1" />
            </line>

            <circle cx="32" cy="46" r="2" fill="#fff">
                <animate attributeName="cy" values="46;42;46" dur="0.6s" repeatCount="indefinite"
                    keyTimes="0;0.5;1" />
            </circle>
        </svg>

        <h1 class="text-5xl font-extrabold text-gray-800 tracking-tight">
            {{ isset($event) ? 'Pendaftaran Ditutup' : 'Event Tidak Ditemukan' }}
        </h1>

        <p class="mt-6 text-lg text-gray-600 leading-relaxed">
            {{ $message }}
        </p>

        <p class="mt-3 text-gray-500 text-base">
            Event: <span class="font-semibold text-gray-700">{{ $event->title ?? 'Event Tidak Ditemukan Atau Sudah Dihapus'}}</span>
        </p>

    </div>
</body>

</html>
