@extends('layouts.app') {{-- Assuming you have a layout file, e.g., from Breeze --}}

@section('content')
<div class="container">
    <h1>Subscription Plans</h1>
    <a href="{{ route('admin.subscription-plans.create') }}" class="btn btn-primary mb-3">Create New Plan</a>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name (Default: {{ app()->getLocale() }})</th>
                <th>Price (€)</th>
                <th>PayPal Plan ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($subscription_plans as $plan)
                <tr>
                    <td>{{ $plan->id }}</td>
                    <td>{{ $plan->name }}</td> {{-- This will use the current locale --}}
                    <td>{{ number_format($plan->price, 2) }}</td>
                    <td>{{ $plan->paypal_plan_id ?? '-' }}</td>
                    <td>
                        <a href="{{ route('admin.subscription-plans.edit', $plan) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form method="POST" action="{{ route('admin.subscription-plans.destroy', $plan) }}" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No subscription plans found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $subscription_plans->links() }}
</div>
@endsection
