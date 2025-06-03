<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Expense') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data">
                        @csrf {{-- Add CSRF token --}}
                        @method('PUT')
                        @include('expenses._form', ['expense' => $expense])
                        {{-- Assuming _form.blade.php contains form fields and a submit button --}}
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
