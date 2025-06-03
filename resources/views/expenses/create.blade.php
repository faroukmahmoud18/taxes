<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Record New Expense') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    {{-- The card-header might be redundant if the header slot is styled similarly --}}
                    {{-- <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('Record New Expense') }}</h3> --}}
                    <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
                        @csrf {{-- Add CSRF token --}}
                        @include('expenses._form')
                        {{-- Assuming _form.blade.php contains form fields and a submit button --}}
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
