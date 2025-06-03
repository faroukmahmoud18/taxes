<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Message -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium">{{ __('Welcome back, ') }} {{ $user->name }}!</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Column 1: Subscription Status & Quick Actions -->
                <div class="md:col-span-1 space-y-6">
                    <!-- Subscription Status -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('My Subscription') }}</h4>
                            @if ($activeSubscription)
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('Current Plan:') }} <span class="font-semibold">{{ $activeSubscription->subscriptionPlan ? $activeSubscription->subscriptionPlan->name : __('N/A') }}</span>
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('Status:') }} <span class="font-semibold capitalize">{{ $activeSubscription->status }}</span>
                                </p>
                                @if ($activeSubscription->ends_at)
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $activeSubscription->status === 'cancelled' || $activeSubscription->ends_at->isPast() ? __('Expires On:') : __('Next Billing Date:') }} <span class="font-semibold">{{ $activeSubscription->ends_at->toFormattedDateString() }}</span>
                                </p>
                                @endif
                                <div class="mt-4">
                                    <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                        {{ __('Manage Subscription') }}
                                    </a>
                                </div>
                            @else
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('You are not currently subscribed to any plan.') }}</p>
                                <div class="mt-4">
                                    <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                                        {{ __('View Plans') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('Quick Actions') }}</h4>
                            <div class="space-y-3">
                                <a href="{{ route('expenses.create') }}" class="block w-full text-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    {{ __('Add New Expense') }}
                                </a>
                                <a href="{{ route('expenses.report') }}" class="block w-full text-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('View Expense Report') }}
                                </a>
                                <a href="{{ route('tax-estimation.show') }}" class="block w-full text-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('Estimate Taxes') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Recent Expenses -->
                <div class="md:col-span-2">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Recent Expenses') }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                {{ __('Total This Month:') }} <span class="font-semibold">€{{ number_format($totalExpensesThisMonth, 2) }}</span>
                            </p>

                            @if ($recentExpenses->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Date') }}</th>
                                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Description') }}</th>
                                                <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('Amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach ($recentExpenses as $expense)
                                                <tr>
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $expense->expense_date->toFormattedDateString() }}</td>
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        <a href="{{ route('expenses.show', $expense) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                                            {{ Str::limit($expense->description, 50) }}
                                                        </a>
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">€{{ number_format($expense->amount, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4">
                                     <a href="{{ route('expenses.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        {{ __('View All Expenses') }} &rarr;
                                    </a>
                               </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No expenses recorded recently.') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
