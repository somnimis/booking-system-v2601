<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'CPU Booking')</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

   
  
  <!-- Your existing global styles -->
  <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
  
  <!-- NEW: Responsive styles for all user-facing pages -->
  <link rel="stylesheet" href="{{ asset('css/public/responsive-styles.css') }}" />
  
  <style>

        :root {
    /* Primary Color Scheme */
    --cpu-primary: #003366; /* Main CPU blue */
    --cpu-primary-hover: #004a94; /* Hover state for primary elements */
    --cpu-primary-light: rgba(0, 51, 102, 0.1); /* Light background variants */

    /* Secondary Color Scheme */
    --cpu-secondary: #e8b342; /* Gold accent color */
    --cpu-secondary-hover: #d39b21; /* Gold hover state */

    /* Neutral Colors */
    --cpu-white: #ffffff;
    --cpu-light-bg: #f5f7f8; /* Navbar background */
    --cpu-text-dark: #333333;
    --light-gray: #f8f9fa; /* Default text color */

    /* Functional Colors */
    --cpu-border-accent: #f2b123; /* Navbar bottom border */
    --cpu-shadow: rgba(0, 0, 0, 0.1); /* Shadow color */
}

    /* Custom pagination colors using CPU theme */
    .pagination .page-link {
      color: var(--cpu-primary); /* dark blue text */
    }

    .pagination .page-link:hover {
      color: var(--cpu-primary-hover); /* hover text color */
    }

    /* Active page */
    .pagination .page-item.active .page-link {
      background-color: var(--cpu-primary);
      border-color: var(--cpu-primary);
      color: #fff; /* white text for contrast */
    }

    /* Disabled state */
    .pagination .page-item.disabled .page-link {
      color: #6c757d; /* gray */
      pointer-events: none;
      background-color: var(--light-gray);
      border-color: #dee2e6;
    }

    * {
      font-family: 'Inter', 'Segoe UI', Roboto, Arial, sans-serif;
    }

    /* Make all scrollbars thin and subtle across browsers */
    * {
      scrollbar-width: thin;
      /* Firefox */
      scrollbar-color: #adb5bd transparent;
    }

    *::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }

    *::-webkit-scrollbar-track {
      background: transparent;
    }

    *::-webkit-scrollbar-thumb {
      background-color: #adb5bd;
      border-radius: 10px;
    }
    /* Hide top-header bar on events calendar page */
body.events-calendar-page .top-header-bar, footer.footer-container {
    display: none !important;
}

  </style>

</head>
<body class="@yield('body_class', '')">
  {{-- Header + Navbar --}}
  @include('partials.navbar')

  {{-- Page-specific content --}}
  <main>
    @yield('content')
  </main>

  {{-- Footer --}}
  @include('partials.footer')

{{-- Scripts --}}

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('js/public/bootstrap-init.js') }}"></script>
<script src="{{ asset('js/public/responsive-helpers.js') }}"></script>


@yield('scripts')
</body>
</html>