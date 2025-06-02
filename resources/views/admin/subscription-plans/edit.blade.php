@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Subscription Plan: {{ $subscription_plan->getTranslation('name', app()->getLocale(), false) }}</h1>
    <form method="POST" action="{{ route('admin.subscription-plans.update', $subscription_plan) }}">
        @method('PUT')
        @include('admin.subscription-plans._form', ['subscription_plan' => $subscription_plan])
    </form>
</div>
@endsection
