@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1>{{ __('Edit Static Page') }}: {{ $staticPage->getTranslation('title', app()->getLocale(), false) }}</h1>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.static-pages.update', $staticPage) }}">
                @method('PUT')
                @include('admin.static-pages._form', ['staticPage' => $staticPage])
            </form>
        </div>
    </div>
</div>
@endsection
