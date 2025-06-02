@extends('layouts.app') {{-- Assuming admin uses the same base layout --}}

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ __('Static Pages') }}</h1>
        <a href="{{ route('admin.static-pages.create') }}" class="btn btn-primary">{{ __('Create New Page') }}</a>
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
                        <th>{{ __('ID') }}</th>
                        <th>{{ __('Title') }} ({{ strtoupper(app()->getLocale()) }})</th>
                        <th>{{ __('Slug') }}</th>
                        <th>{{ __('Published') }}</th>
                        <th>{{ __('Last Updated') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td>{{ $page->id }}</td>
                            <td>{{ $page->title }}</td> {{-- Uses current locale due to HasTranslations --}}
                            <td>{{ $page->slug }}</td>
                            <td>
                                @if ($page->is_published)
                                    <span class="badge bg-success">{{ __('Yes') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td>{{ $page->updated_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.static-pages.show', $page) }}" class="btn btn-sm btn-info">{{ __('View') }}</a>
                                <a href="{{ route('admin.static-pages.edit', $page) }}" class="btn btn-sm btn-warning">{{ __('Edit') }}</a>
                                <form method="POST" action="{{ route('admin.static-pages.destroy', $page) }}" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('Are you sure you want to delete this page?') }}')">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('No static pages found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pages->hasPages())
            <div class="card-footer">
                {{ $pages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
