<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        @include('customer.partials.header')

        <!-- Breadcrumb -->
        @hasSection('breadcrumb')
            @yield('breadcrumb')
        @endif

        <!-- Main Content -->
        <main class="flex-1 container mx-auto px-4 sm:px-6 py-4 sm:py-6">
            @yield('content')
        </main>

        <!-- Footer -->
        @include('customer.partials.footer')
    </div>

    @guest
        @include('customer.partials.auth-required-modal')
    @endguest

    @include('customer.partials.confirm-modal')

    <!-- Toast -->
    @include('customer.partials.toast')

    @stack('scripts')
</body>
</html>