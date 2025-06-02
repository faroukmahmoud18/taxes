@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Choose a Subscription Plan</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="row">
        @forelse ($plans as $plan)
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3>{{ $plan->getTranslation('name', app()->getLocale(), false) }}</h3>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">€{{ number_format($plan->price, 2) }} / month</h5>
                        <p class="card-text">
                            {!! nl2br(e($plan->getTranslation('features', app()->getLocale(), false))) !!}
                        </p>
                        @if($plan->paypal_plan_id)
                            <form method="POST" action="{{ route('subscriptions.subscribe', $plan) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">Subscribe with PayPal</button>
                            </form>
                        @else
                            <p><small class="text-muted">Not available for online subscription currently.</small></p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col">
                <p>No subscription plans are currently available.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
