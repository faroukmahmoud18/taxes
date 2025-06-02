@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">{{ $staticPage->getTranslation('title', app()->getLocale(), false) }} <small class="text-muted">({{ $staticPage->slug }})</small></h1>
                <div>
                     @if ($staticPage->is_published)
                        <span class="badge bg-success">{{ __('Published') }}</span>
                    @else
                        <span class="badge bg-secondary">{{ __('Not Published') }}</span>
                    @endif
                    <a href="{{ route('admin.static-pages.edit', $staticPage) }}" class="btn btn-warning ms-2">{{ __('Edit') }}</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    @foreach(['en', 'de', 'ar'] as $idx => $locale)
                    <button class="nav-link {{ $idx == 0 ? 'active' : '' }}" id="nav-{{ $locale }}-tab" data-bs-toggle="tab" data-bs-target="#nav-{{ $locale }}" type="button" role="tab" aria-controls="nav-{{ $locale }}" aria-selected="{{ $idx == 0 ? 'true' : 'false' }}">{{ strtoupper($locale) }}</button>
                    @endforeach
                </div>
            </nav>
            <div class="tab-content p-3 border border-top-0" id="nav-tabContent">
                @foreach(['en', 'de', 'ar'] as $idx => $locale)
                <div class="tab-pane fade {{ $idx == 0 ? 'show active' : '' }}" id="nav-{{ $locale }}" role="tabpanel" aria-labelledby="nav-{{ $locale }}-tab">
                    <h3 class="mt-3">{{ $staticPage->getTranslation('title', $locale, false) }}</h3>
                    <hr>
                    <div>
                        {!! nl2br(e($staticPage->getTranslation('content', $locale, false))) !!} {{-- Using nl2br(e()) for basic formatting, use {!! $var !!} if content is trusted HTML --}}
                    </div>
                    <hr>
                    <p><strong>{{ __('Meta Keywords') }}:</strong> {{ $staticPage->getTranslation('meta_keywords', $locale, false) ?? __('N/A') }}</p>
                    <p><strong>{{ __('Meta Description') }}:</strong> {{ $staticPage->getTranslation('meta_description', $locale, false) ?? __('N/A') }}</p>
                </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.static-pages.index') }}" class="btn btn-secondary">{{ __('Back to List') }}</a>
        </div>
    </div>
</div>
@endsection
