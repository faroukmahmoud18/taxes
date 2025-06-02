@csrf
@php $locales = ['en', 'de', 'ar']; @endphp
<ul class="nav nav-tabs mb-3" id="localeTabsDescription" role="tablist">
    @foreach($locales as $idx => $locale)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $idx === 0 ? 'active' : '' }}" id="tab-desc-{{ $locale }}" data-bs-toggle="tab" data-bs-target="#content-desc-{{ $locale }}" type="button" role="tab" aria-controls="content-desc-{{ $locale }}" aria-selected="{{ $idx === 0 ? 'true' : 'false' }}">{{ strtoupper($locale) }} {{ __('Description') }}</button>
        </li>
    @endforeach
</ul>
<div class="tab-content" id="localeTabsDescriptionContent">
    @foreach($locales as $idx => $locale)
        <div class="tab-pane fade {{ $idx === 0 ? 'show active' : '' }}" id="content-desc-{{ $locale }}" role="tabpanel" aria-labelledby="tab-desc-{{ $locale }}">
            <div class="mb-3">
                <label for="description_{{ $locale }}" class="form-label">{{ __('Description') }} ({{ strtoupper($locale) }})</label>
                <textarea class="form-control @error('description.' . $locale) is-invalid @enderror" id="description_{{ $locale }}" name="description[{{ $locale }}]" rows="3">{{ old('description.' . $locale, isset($expense) ? $expense->getTranslation('description', $locale, false) : '') }}</textarea>
                @error('description.' . $locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    @endforeach
</div>
@error('description') <div class="alert alert-danger mt-2">{{ $message }}</div> @enderror
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="amount" class="form-label">{{ __('Amount') }} (€)</label>
        <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $expense->amount ?? '') }}" required>
        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="expense_date" class="form-label">{{ __('Expense Date') }}</label>
        <input type="date" class="form-control @error('expense_date') is-invalid @enderror" id="expense_date" name="expense_date" value="{{ old('expense_date', isset($expense) && $expense->expense_date ? $expense->expense_date->format('Y-m-d') : date('Y-m-d')) }}" required>
        @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
<div class="mb-3">
    <label for="category" class="form-label">{{ __('Category') }} <small>({{ __('Optional') }})</small></label>
    <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $expense->category ?? '') }}">
    @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-3">
    <label for="receipt" class="form-label">{{ __('Receipt') }} <small>({{ __('Optional, Max 2MB: JPG, PNG, PDF') }})</small></label>
    <input type="file" class="form-control @error('receipt') is-invalid @enderror" id="receipt" name="receipt">
    @error('receipt') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @if (isset($expense) && $expense->receipt_path)
        <div class="mt-2">
            <p>{{ __('Current receipt') }}: <a href="{{ Storage::url($expense->receipt_path) }}" target="_blank">{{ __('View Receipt') }}</a></p>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remove_receipt" value="1" id="remove_receipt_checkbox">
                <label class="form-check-label" for="remove_receipt_checkbox">{{ __('Remove current receipt') }}</label>
            </div>
        </div>
    @endif
</div>
<div class="mb-3 form-check">
    <input type="hidden" name="is_business_expense" value="0">
    <input type="checkbox" class="form-check-input" id="is_business_expense" name="is_business_expense" value="1" {{ old('is_business_expense', (isset($expense) && $expense->is_business_expense) ? true : false) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_business_expense">{{ __('Is this a business expense?') }}</label>
</div>
<hr class="my-4">
<button type="submit" class="btn btn-primary">{{ isset($expense) ? __('Update Expense') : __('Record Expense') }}</button>
<a href="{{ route('expenses.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
