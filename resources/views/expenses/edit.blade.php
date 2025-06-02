@extends('layouts.app')
@section('header') <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Edit Expense') }}</h2> @endsection
@section('content')
<div class="container py-4">
     <div class="card">
        <div class="card-header">{{ __('Edit Expense') }}</div>
        <div class="card-body">
            <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data">
                @method('PUT')
                @include('expenses._form', ['expense' => $expense])
            </form>
        </div>
    </div>
</div>
@endsection
