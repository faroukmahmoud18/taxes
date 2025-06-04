<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900" x-data="{ isSidebarOpen: false }">
            <div class="flex"> {{-- Main flex container for sidebar + content --}}
                <!-- Sidebar -->
                <x-sidebar />

                <!-- Main Content Area -->
                <div class="flex-1 flex flex-col h-screen overflow-y-auto"> {{-- Make content area scrollable --}}

                    <!-- Top Navigation -->
                    @include('layouts.navigation')

                    <!-- Page Heading -->
                    @if (isset($header))
                        <header class="bg-white dark:bg-gray-800 shadow">
                            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </header>
                    @endif

                    <!-- Page Content -->
                    <main class="flex-grow p-6"> {{-- Add some padding to main content --}}
                        {{ $slot }}
                    </main>

                    {{-- Optional Footer can go here if needed --}}
                    {{-- <footer class="py-4 text-center text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700">
                        Your App &copy; {{ date('Y') }}
                    </footer> --}}
                </div>
            </div>
        </div>
    </body>
</html>
