<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">{{ __('Welcome to the Admin Dashboard!') }}</h3>
                    <p class="mb-6">{{ __("From here you can manage various aspects of the application.") }}</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Manage Subscription Plans -->
                        <a href="{{ route('admin.subscription-plans.index') }}" class="block p-6 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Subscription Plans') }}</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">{{ __('Manage subscription plans available to users.') }}</p>
                        </a>

                        <!-- Manage Static Pages -->
                        <a href="{{ route('admin.static-pages.index') }}" class="block p-6 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Static Pages') }}</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">{{ __('Manage static content pages like About Us, Terms, etc.') }}</p>
                        </a>

                        <!-- Tax Configuration -->
                        <a href="{{ route('admin.tax-configuration.index') }}" class="block p-6 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Tax Configuration') }}</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">{{ __('Configure tax rates and settings.') }}</p>
                        </a>

                        {{-- Add more links here as other admin features are developed --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
