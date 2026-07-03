@extends('admin.master_layout')

@section('title')
    <title>{{ __('Edit Subscription Plan') }}</title>
@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>{{ __('Edit Subscription Plan') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('admin.subscriptions.index') }}">{{ __('Subscriptions') }}</a></div>
                <div class="breadcrumb-item">{{ __('Edit') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('admin.subscriptions.update', $subscription->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="row">
                                    <div class="form-group col-12">
                                        <label>{{ __('Plan Name') }} <span class="text-danger">*</span></label>
                                        <input type="text" id="name" class="form-control" name="name" value="{{ old('name', $subscription->name) }}" placeholder="e.g., Basic Plan, Premium Plan" required>
                                    </div>

                                    <div class="form-group col-12">
                                        <label>{{ __('Duration') }} <span class="text-danger">*</span></label>
                                        <input type="number" id="duration_months" class="form-control" name="duration_months" value="{{ old('duration_months', $subscription->duration_months ?? 1) }}" required min="1" placeholder="e.g., 1, 3, 6, 12">
                                        <small class="text-muted">{{ __('How long this plan stays active after purchase') }}</small>
                                    </div>
                                    
                                    <div class="form-group col-12 col-md-6">
                                        <label>{{ __('Original Price (PKR)') }} <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" id="price_pkr" class="form-control" name="price_pkr" value="{{ old('price_pkr', $subscription->price_pkr) }}" placeholder="e.g., 5000" required min="0">
                                        <small class="text-muted">Original price of the plan</small>
                                    </div>
                                    
                                    <div class="form-group col-12 col-md-6">
                                        <label>{{ __('Saving Price (PKR)') }}</label>
                                        <input type="number" step="0.01" id="saving_price" class="form-control" name="saving_price" value="{{ old('saving_price', $subscription->saving_price) }}" placeholder="e.g., 4000" min="0">
                                    </div>
                                    
                                    <div class="form-group col-12">
                                        <label>{{ __('Features') }}</label>
                                        <textarea name="features" id="features" class="form-control" cols="30" rows="5" placeholder="Enter features (one per line)">{{ old('features', is_array($subscription->features) ? implode("\n", $subscription->features) : $subscription->features) }}</textarea>
                                        <small class="text-muted">{{ __('Enter each feature on a new line. Example:') }}<br>
                                        ✓ 24/7 Support<br>
                                        ✓ Unlimited Bookings<br>
                                        ✓ Priority Service</small>
                                    </div>
                                    
                                    <div class="form-group col-12">
                                        <label>{{ __('Status') }}</label>
                                        <select name="is_active" class="form-control">
                                            <option value="1" {{ old('is_active', (int) $subscription->is_active) == 1 ? 'selected' : '' }}>{{ __('Active') }}</option>
                                            <option value="0" {{ old('is_active', (int) $subscription->is_active) == 0 ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-save"></i> {{ __('Update Plan') }}
                                        </button>
                                        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> {{ __('Cancel') }}
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('js')
@include('admin.partials.system-records-toast')
<script>
    $(document).ready(function() {
        // This function is now removed - no saving calculation display
        // The saving info div has been completely removed
    });
</script>
@endpush