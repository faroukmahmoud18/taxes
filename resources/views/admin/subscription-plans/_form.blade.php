@csrf
@php $locales = ['en', 'de', 'ar']; @endphp

@foreach($locales as $locale)
    <div class="mb-3">
        <label for="name_{{ $locale }}" class="form-label">Name ({{ strtoupper($locale) }})</label>
        <input type="text" class="form-control @error('name.' . $locale) is-invalid @enderror" id="name_{{ $locale }}" name="name[{{ $locale }}]" value="{{ old('name.' . $locale, isset($subscription_plan) ? $subscription_plan->getTranslation('name', $locale, false) : '') }}">
        @error('name.' . $locale)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
@endforeach

<div class="mb-3">
    <label for="price" class="form-label">Price (€)</label>
    <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $subscription_plan->price ?? '') }}">
    @error('price')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@foreach($locales as $locale)
    <div class="mb-3">
        <label for="features_{{ $locale }}" class="form-label">Features ({{ strtoupper($locale) }}) (Optional)</label>
        <textarea class="form-control @error('features.' . $locale) is-invalid @enderror" id="features_{{ $locale }}" name="features[{{ $locale }}]" rows="3">{{ old('features.' . $locale, isset($subscription_plan) ? $subscription_plan->getTranslation('features', $locale, false) : '') }}</textarea>
        @error('features.' . $locale)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
@endforeach

<div class="mb-3">
    <label for="paypal_plan_id" class="form-label">PayPal Plan ID (Optional)</label>
    <input type="text" class="form-control @error('paypal_plan_id') is-invalid @enderror" id="paypal_plan_id" name="paypal_plan_id" value="{{ old('paypal_plan_id', $subscription_plan->paypal_plan_id ?? '') }}">
    @error('paypal_plan_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-primary">{{ isset($subscription_plan) ? 'Update Plan' : 'Create Plan' }}</button>
<a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-secondary">Cancel</a>
