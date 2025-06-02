@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Subscription Plan</h1>
    <form method="POST" action="{{ route('admin.subscription-plans.store') }}">
        @include('admin.subscription-plans._form')
    </form>
</div>
@endsection
