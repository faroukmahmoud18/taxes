@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ __('My Expenses') }}</h1>
        <a href="{{ route('expenses.create') }}" class="btn btn-primary">{{ __('Record New Expense') }}</a>
        <a href="{{ route('expenses.report') }}" class="btn btn-info ms-2">{{ __('View Report') }}</a>
    </div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th class="text-end">{{ __('Amount') }} (€)</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Business?') }}</th>
                        <th>{{ __('Receipt') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                            <td>{{ $expense->description }}</td>
                            <td class="text-end">{{ number_format($expense->amount, 2, ',', '.') }}</td>
                            <td>{{ $expense->category ?? '-' }}</td>
                            <td>@if ($expense->is_business_expense) <span class="badge bg-success">{{ __('Yes') }}</span> @else <span class="badge bg-secondary">{{ __('No') }}</span> @endif</td>
                            <td>@if ($expense->receipt_path) <a href="{{ Storage::url($expense->receipt_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">{{ __('View Receipt') }}</a> @else - @endif</td>
                            <td>
                                <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-warning">{{ __('Edit') }}</a>
                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}" style="display:inline-block;" onsubmit="return confirm('{{ __('Are you sure you want to delete this expense?') }}');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">{{ __("You haven't recorded any expenses yet.") }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())
            <div class="card-footer">{{ $expenses->links() }}</div>
        @endif
    </div>
</div>
@endsection
