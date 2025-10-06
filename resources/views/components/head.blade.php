 <head>
     <meta charset="UTF-8" />
     <meta name="viewport" content="width=device-width, initial-scale=1.0" />
     <title>{{ $title ?? 'Dashboard' }} - {{ config('app.name', 'Laravel') }}</title>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/iconify/3.1.1/iconify.js"></script>
     <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}" sizes="16x16" />
     <!-- remix icon font css  -->
     <link rel="stylesheet" href="{{ asset('assets/css/remixicon.css') }}" />
     <!-- BootStrap css -->
     <link rel="stylesheet" href="{{ asset('assets/css/lib/bootstrap.min.css') }}" />
     <!-- Apex Chart css -->
     <link rel="stylesheet" href="{{ asset('assets/css/lib/apexcharts.css') }}" />
     <!-- Data Table css -->
     <link rel="stylesheet" href="{{ asset('assets/css/lib/dataTables.min.css') }}" />
     <!-- Text Editor css -->
     <link rel="stylesheet" href="{{ asset('assets/css/lib/editor-katex.min.css') }}" />
     <link rel="stylesheet" href="{{ asset('assets/css/lib/editor.atom-one-dark.min.css') }}" />
     <link rel="stylesheet" href="{{ asset('assets/css/lib/editor.quill.snow.css') }}" />
     <!-- Date picker css -->
     <link rel="stylesheet" href="{{ asset('assets/css/lib/flatpickr.min.css') }}" />
     <!-- Calendar css -->
     <link rel="stylesheet" href="{{ asset('assets/css/lib/full-calendar.css') }}" />
     <!-- Vector Map css -->
     <link rel="stylesheet" href="{{ asset('assets/css/lib/jquery-jvectormap-2.0.5.css') }}" />
     <!-- Popup css -->
     <link rel="stylesheet" href="{{ asset('assets/css/lib/magnific-popup.css') }}" />
     <!-- Slick Slider css -->
     <link rel="stylesheet" href="{{ asset('assets/css/lib/slick.css') }}" />
     <!-- prism css -->
     <link rel="stylesheet" href="{{ asset('assets/css/lib/prism.css') }}" />
     <!-- file upload css -->
     <link rel="stylesheet" href="{{ asset('assets/css/lib/file-upload.css') }}" />

     <link rel="stylesheet" href="{{ asset('assets/css/lib/audioplayer.css') }}" />
     <!-- main css -->
     <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
     @vite(['resources/css/app.css', 'resources/js/app.js'])
 </head>
