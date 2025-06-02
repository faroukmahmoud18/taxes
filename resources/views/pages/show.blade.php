@extends('layouts.app') {{-- Assuming a general app layout --}}

{{-- SEO Meta Tags - These will use the StaticPage model's translatable attributes --}}
@section('title', $staticPage->getTranslation('title', app()->getLocale(), false) ?? config('app.name'))
@if($staticPage->getTranslation('meta_keywords', app()->getLocale(), false))
@section('meta_keywords', $staticPage->getTranslation('meta_keywords', app()->getLocale(), false))
@endif
@if($staticPage->getTranslation('meta_description', app()->getLocale(), false))
@section('meta_description', $staticPage->getTranslation('meta_description', app()->getLocale(), false))
@endif

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-12"> {{-- Or col-md-8 offset-md-2 for centered content etc. --}}
            <article class="static-page-content">
                <h1 class="mb-4">{{ $staticPage->title }}</h1> {{-- Uses current locale due to HasTranslations trait --}}
                
                {{-- Ensure content is displayed raw if it contains HTML, otherwise escape it --}}
                {{-- For security, if content is from a WYSIWYG editor that saves HTML, ensure it's purified before saving or display --}}
                {{-- For now, assuming content might be HTML and is trusted: --}}
                <div>
                    {!! $staticPage->content !!} {{-- Uses current locale --}}
                </div>
            </article>
        </div>
    </div>
</div>
@endsection
