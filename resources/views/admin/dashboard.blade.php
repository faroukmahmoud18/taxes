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
                    <p class="mb-2">{{ __("From here you can manage various aspects of the application.") }}</p>
                    <p class="mb-6 text-sm text-gray-600 dark:text-gray-400"> {{ __("Total Users:") }} {{ $userCount ?? 'N/A' }}</p>


                    <!-- Subscription Overview Section -->
                    <div class="mb-8 bg-white dark:bg-gray-700 shadow-md rounded-lg p-6 border border-gray-200 dark:border-gray-600">
                        <h4 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">{{ __('Subscription Overview') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg shadow">
                                <h5 class="text-lg font-medium mb-3 text-gray-700 dark:text-gray-300">{{ __('Active Subscriptions per Plan') }}</h5>
                                @if(isset($subscriptionPlansData) && count($subscriptionPlansData) > 0)
                                    <ul class="space-y-2">
                                        @foreach($subscriptionPlansData as $plan)
                                            <li class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-md shadow-sm">
                                                <span class="text-gray-700 dark:text-gray-300">{{ $plan->getTranslation('name', app()->getLocale()) }}</span>
                                                <span class="px-3 py-1 text-sm font-semibold text-blue-800 bg-blue-100 dark:bg-blue-600 dark:text-blue-100 rounded-full">{{ $plan->user_subscriptions_count }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-gray-500 dark:text-gray-400">{{ __('No subscription plans found or no active subscriptions.') }}</p>
                                @endif
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-lg shadow flex flex-col items-center justify-center text-center">
                                <h5 class="text-lg font-medium mb-2 text-gray-700 dark:text-gray-300">{{ __('Estimated MRR') }}</h5>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                    €{{ number_format($totalEstimatedMrr ?? 0, 2, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- End Subscription Overview Section -->

                    <h4 class="text-lg font-semibold mt-6 mb-4 text-gray-800 dark:text-gray-200">{{ __('Management Links') }}</h4>
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
