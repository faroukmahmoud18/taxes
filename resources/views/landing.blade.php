<x-guest-layout>
    <div class="bg-gray-100 dark:bg-gray-900 min-h-screen flex flex-col items-center justify-center pt-6 sm:pt-0">
        <div class="w-full max-w-4xl mx-auto p-6">
            <div class="text-center">
                <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 dark:text-white leading-tight mb-6">
                    Manage Your Subscriptions and Expenses, Effortlessly
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-10">
                    Join us to take control of your financial tracking with powerful tools and insights.
                </p>
                <div class="flex justify-center">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition duration-150 ease-in-out">
                        Get Started
                    </a>
                    @if (Route::has('login'))
                    <a href="{{ route('login') }}"
                       class="ml-4 inline-flex items-center justify-center px-8 py-3 border border-indigo-600 text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50 dark:text-indigo-400 dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-indigo-500 transition duration-150 ease-in-out">
                        Log In
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Optional: Simple Features Section -->
        <div class="w-full max-w-4xl mx-auto p-6 mt-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div class="p-4">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white mx-auto mb-4">
                        {{-- Replace with actual SVG icon if available --}}
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Subscription Management</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Keep track of all your subscriptions in one place.</p>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white mx-auto mb-4">
                        {{-- Replace with actual SVG icon if available --}}
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Expense Tracking</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor your spending with detailed categorization.</p>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white mx-auto mb-4">
                        {{-- Replace with actual SVG icon if available --}}
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" /></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Clear Reports</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Generate insightful reports to understand your finances.</p>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
