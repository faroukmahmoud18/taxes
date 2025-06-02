@csrf
@php $locales = ['en', 'de', 'ar']; @endphp

<div class="mb-3">
    <label for="slug" class="form-label">{{ __('Slug') }}</label>
    <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $staticPage->slug ?? '') }}">
    @error('slug')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small class="form-text text-muted">{{ __('If left empty, the slug will be auto-generated from the English title. Ensure it is unique.') }}</small>
</div>

<div class="mb-3 form-check">
    <input type="hidden" name="is_published" value="0">
    <input type="checkbox" class="form-check-input @error('is_published') is-invalid @enderror" id="is_published" name="is_published" value="1" {{ old('is_published', (isset($staticPage) && $staticPage->is_published) ? true : false) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_published">{{ __('Is Published?') }}</label>
    @error('is_published')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Tabs for translatable content --}}
<ul class="nav nav-tabs mb-3" id="localeTabs" role="tablist">
    @foreach($locales as $idx => $locale)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $idx === 0 ? 'active' : '' }}" id="tab-{{ $locale }}" data-bs-toggle="tab" data-bs-target="#content-{{ $locale }}" type="button" role="tab" aria-controls="content-{{ $locale }}" aria-selected="{{ $idx === 0 ? 'true' : 'false' }}">{{ strtoupper($locale) }}</button>
        </li>
    @endforeach
</ul>

<div class="tab-content" id="localeTabsContent">
    @foreach($locales as $idx => $locale)
        <div class="tab-pane fade {{ $idx === 0 ? 'show active' : '' }}" id="content-{{ $locale }}" role="tabpanel" aria-labelledby="tab-{{ $locale }}">
            <div class="mb-3">
                <label for="title_{{ $locale }}" class="form-label">{{ __('Title') }} ({{ strtoupper($locale) }})</label>
                <input type="text" class="form-control @error('title.' . $locale) is-invalid @enderror" id="title_{{ $locale }}" name="title[{{ $locale }}]" value="{{ old('title.' . $locale, isset($staticPage) ? $staticPage->getTranslation('title', $locale, false) : '') }}">
                @error('title.' . $locale)
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="content_{{ $locale }}" class="form-label">{{ __('Content') }} ({{ strtoupper($locale) }})</label>
                <textarea class="form-control @error('content.' . $locale) is-invalid @enderror" id="content_{{ $locale }}" name="content[{{ $locale }}]" rows="10">{{ old('content.' . $locale, isset($staticPage) ? $staticPage->getTranslation('content', $locale, false) : '') }}</textarea>
                @error('content.' . $locale)
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="meta_keywords_{{ $locale }}" class="form-label">{{ __('Meta Keywords') }} ({{ strtoupper($locale) }}) <small>({{ __('Optional') }})</small></label>
                <input type="text" class="form-control @error('meta_keywords.' . $locale) is-invalid @enderror" id="meta_keywords_{{ $locale }}" name="meta_keywords[{{ $locale }}]" value="{{ old('meta_keywords.' . $locale, isset($staticPage) ? $staticPage->getTranslation('meta_keywords', $locale, false) : '') }}">
                @error('meta_keywords.' . $locale)
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="meta_description_{{ $locale }}" class="form-label">{{ __('Meta Description') }} ({{ strtoupper($locale) }}) <small>({{ __('Optional') }})</small></label>
                <textarea class="form-control @error('meta_description.' . $locale) is-invalid @enderror" id="meta_description_{{ $locale }}" name="meta_description[{{ $locale }}]" rows="3">{{ old('meta_description.' . $locale, isset($staticPage) ? $staticPage->getTranslation('meta_description', $locale, false) : '') }}</textarea>
                @error('meta_description.' . $locale)
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endforeach
</div>

<hr class="my-4">
<button type="submit" class="btn btn-primary">{{ isset($staticPage) ? __('Update Page') : __('Create Page') }}</button>
<a href="{{ route('admin.static-pages.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
