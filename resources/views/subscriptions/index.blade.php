<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Subscription Plans') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Choose a Subscription Plan</h1>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200 rounded">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-200 rounded">{{ session('error') }}</div>
                    @endif
                    @if (session('info'))
                        <div class="mb-4 p-4 bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-200 rounded">{{ session('info') }}</div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse ($plans as $plan)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg shadow-md p-6">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $plan->getTranslation('name', app()->getLocale(), false) }}</h3>
                                <p class="text-3xl font-bold text-gray-800 dark:text-gray-200 mb-3">
                                    €{{ number_format($plan->price, 2) }} <span class="text-sm font-normal text-gray-500 dark:text-gray-400">/ month</span>
                                </p>
                                <div class="text-sm text-gray-600 dark:text-gray-300 mb-4 space-y-1">
                                    {!! nl2br(e($plan->getTranslation('features', app()->getLocale(), false) ?? '')) !!}
                                </div>
                                @if($plan->paypal_plan_id)
                                    <form method="POST" action="{{ route('subscriptions.subscribe', $plan) }}">
                                        @csrf
                                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                                            Subscribe with PayPal
                                        </button>
                                    </form>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">Not available for online subscription currently.</p>
                                @endif
                            </div>
                        @empty
                            <div class="col-span-full text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400 text-lg">No subscription plans are currently available.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
