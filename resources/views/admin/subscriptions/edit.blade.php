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

                                    <div class="form-group col-12 col-md-6">
                                        <label>{{ __('Duration (Months)') }} <span class="text-danger">*</span></label>
                                        <input type="number" id="duration_months" class="form-control" name="duration_months" value="{{ old('duration_months', $subscription->duration_months) }}" required min="1">
                                    </div>
                                    
                                    <div class="form-group col-12 col-md-6">
                                        <label>{{ __('Original Price (PKR)') }} <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" id="price_pkr" class="form-control" name="price_pkr" value="{{ old('price_pkr', $subscription->price_pkr) }}" placeholder="e.g., 5000" required min="0">
                                        <small class="text-muted">Original price of the plan</small>
                                    </div>
                                    
                                    <div class="form-group col-12 col-md-6">
                                        <label>{{ __('Saving Price (PKR)') }}</label>
                                        <input type="number" step="0.01" id="saving_price" class="form-control" name="saving_price" value="{{ old('saving_price', $subscription->saving_price) }}" placeholder="e.g., 4000" min="0">
                                        <small class="text-muted">Discounted/offer price (leave empty if no discount)</small>
                                        
                                        @php
                                            $savingAmount = ($subscription->price_pkr ?? 0) - ($subscription->saving_price ?? 0);
                                            $savingPercent = $subscription->price_pkr > 0 ? ($savingAmount / $subscription->price_pkr * 100) : 0;
                                        @endphp
                                        
                                        @if($savingAmount > 0)
                                            <div class="mt-2 text-success">
                                                <i class="fas fa-tag"></i> Current saving: <strong>Rs. {{ number_format($savingAmount, 2) }}</strong> ({{ number_format($savingPercent, 0) }}% off)
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="form-group col-12">
                                        <label>{{ __('Features') }}</label>
                                        <textarea name="features" id="features" class="form-control" cols="30" rows="5" placeholder="Enter features (one per line)">{{ old('features', is_array($subscription->features) ? implode("\n", $subscription->features) : $subscription->features) }}</textarea>
                                        <small class="text-muted">{{ __('Enter each feature on a new line. Example:') }}<br>
                                        ✓ 24/7 Support<br>
                                        ✓ Unlimited Bookings<br>
                                        ✓ Priority Service</small>
                                    </div>

                                    <div class="form-group col-12 col-md-6">
                                        <label>{{ __('Discount %') }}</label>
                                        <input type="number" id="discount_percent" class="form-control" name="discount_percent" value="{{ old('discount_percent', $subscription->discount_percent ?? 0) }}" min="0" max="100">
                                    </div>

                                    <div class="form-group col-12 col-md-6">
                                        <label>{{ __('Tax %') }}</label>
                                        <input type="number" id="tax_percent" class="form-control" name="tax_percent" value="{{ old('tax_percent', $subscription->tax_percent ?? 10) }}" min="0" max="100">
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
<script>
    $(document).ready(function() {
        // Real-time saving calculation
        function calculateSaving() {
            var original = parseFloat($('#price_pkr').val()) || 0;
            var saving = parseFloat($('#saving_price').val()) || 0;
            
            if (saving > 0 && original > 0 && saving < original) {
                var discount = ((original - saving) / original * 100).toFixed(0);
                var saveAmount = (original - saving).toFixed(2);
                
                if ($('.saving-info').length === 0) {
                    $('#saving_price').after('<div class="saving-info text-success mt-2"><i class="fas fa-calculator"></i> Customer will save: Rs. ' + saveAmount + ' (' + discount + '% off)</div>');
                } else {
                    $('.saving-info').html('<i class="fas fa-calculator"></i> Customer will save: Rs. ' + saveAmount + ' (' + discount + '% off)');
                }
            } else {
                $('.saving-info').remove();
            }
        }
        
        $('#price_pkr, #saving_price').on('keyup change', calculateSaving);
        calculateSaving(); // Run on page load
    });
</script>
@endpush