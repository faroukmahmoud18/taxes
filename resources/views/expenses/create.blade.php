@extends('layouts.app')
@section('header') <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Record New Expense') }}</h2> @endsection
@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header">{{ __('Record New Expense') }}</div>
        <div class="card-body">
            <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
                @include('expenses._form')
            </form>
        </div>
    </div>
</div>
@endsection
