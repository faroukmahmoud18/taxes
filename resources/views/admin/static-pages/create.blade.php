@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1>{{ __('Create Static Page') }}</h1>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.static-pages.store') }}">
                @include('admin.static-pages._form')
            </form>
        </div>
    </div>
</div>
@endsection
