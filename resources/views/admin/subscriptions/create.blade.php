@extends('admin.master_layout')

@section('title')
    <title>{{ __('Create Subscription Plan') }}</title>
@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>{{ __('Create Subscription Plan') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ route('admin.subscriptions.index') }}">{{ __('Subscriptions') }}</a></div>
                <div class="breadcrumb-item">{{ __('Create') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('admin.subscriptions.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-12">
                                        <label>{{ __('Plan Name') }} <span class="text-danger">*</span></label>
                                        <input type="text" id="name" class="form-control" name="name" value="{{ old('name') }}" placeholder="e.g., Basic Plan, Premium Plan" required>
                                    </div>
                                    
                                    <div class="form-group col-12">
                                        <label>{{ __('Duration') }} <span class="text-danger">*</span></label>
                                        <input type="number" id="duration_months" class="form-control" name="duration_months" value="{{ old('duration_months', 1) }}" required min="1" placeholder="e.g., 1, 3, 6, 12">
                                        <small class="text-muted">{{ __('How long this plan stays active after purchase') }}</small>
                                    </div>

                                    <div class="form-group col-12 col-md-6">
                                        <label>{{ __('Original Price (PKR)') }} <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" id="price_pkr" class="form-control" name="price_pkr" value="{{ old('price_pkr') }}" placeholder="e.g., 5000" required min="0">
                                    </div>
                                    
                                    <div class="form-group col-12 col-md-6">
                                        <label>{{ __('Saving Price (PKR)') }}</label>
                                        <input type="number" step="0.01" id="saving_price" class="form-control" name="saving_price" value="{{ old('saving_price') }}" placeholder="e.g., 4000" min="0">
                                    </div>

                                    <!-- <div class="form-group col-12 col-md-6">
                                        <label>{{ __('Discount %') }}</label>
                                        <input type="number" id="discount_percent" class="form-control" name="discount_percent" value="{{ old('discount_percent', 0) }}" min="0" max="100">
                                    </div>

                                    <div class="form-group col-12 col-md-6">
                                        <label>{{ __('Tax %') }}</label>
                                        <input type="number" id="tax_percent" class="form-control" name="tax_percent" value="{{ old('tax_percent', 10) }}" min="0" max="100">
                                    </div> -->
                                    
                                    <div class="form-group col-12">
                                        <label>{{ __('Features') }} <span class="text-danger">*</span></label>
                                        <div class="feature-input-container">
                                            <div class="input-group mb-3">
                                                <input type="text" id="featureInput" class="form-control" placeholder="e.g., Free Chat, 24/7 Support, Priority Service">
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="button" id="addFeatureBtn">
                                                        <i class="fas fa-plus"></i> Add Feature
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div id="featuresList" class="features-tags-container">
                                                <!-- Features will appear as tags here -->
                                            </div>
                                            
                                            <textarea name="features" id="features" style="display: none;"></textarea>
                                            <small class="text-muted">Click on feature tags to remove them</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group col-12">
                                        <label>{{ __('Status') }}</label>
                                        <select name="is_active" class="form-control">
                                            <option value="1">{{ __('Active') }}</option>
                                            <option value="0">{{ __('Inactive') }}</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-save"></i> {{ __('Save Plan') }}
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

@push('css')
<style>
    .features-tags-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        padding: 15px;
        min-height: 100px;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        background: #f8f9fc;
        margin-top: 10px;
    }
    
    .feature-tag {
        display: inline-flex;
        align-items: center;
        padding: 8px 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        animation: fadeIn 0.3s ease;
    }
    
    .feature-tag:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }
    
    .feature-tag i {
        margin-right: 8px;
        font-size: 12px;
    }
    
    .feature-tag .remove-icon {
        margin-left: 10px;
        font-size: 14px;
        opacity: 0.8;
        transition: opacity 0.2s;
    }
    
    .feature-tag .remove-icon:hover {
        opacity: 1;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .empty-features {
        color: #858796;
        text-align: center;
        width: 100%;
        padding: 20px;
        font-style: italic;
    }
    
    .feature-input-container .input-group {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .feature-input-container .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
</style>
@endpush

@push('js')
@include('admin.partials.system-records-toast')
<script>
    $(document).ready(function() {
        let features = [];
        
        // Function to render features
        function renderFeatures() {
            const container = $('#featuresList');
            container.empty();
            
            if (features.length === 0) {
                container.html('<div class="empty-features"><i class="fas fa-plus-circle"></i> Add features using the input above</div>');
            } else {
                features.forEach((feature, index) => {
                    const tag = $(`
                        <div class="feature-tag" data-index="${index}">
                            <i class="fas fa-check-circle"></i>
                            ${escapeHtml(feature)}
                            <span class="remove-icon"><i class="fas fa-times-circle"></i></span>
                        </div>
                    `);
                    
                    tag.find('.remove-icon').on('click', function(e) {
                        e.stopPropagation();
                        features.splice(index, 1);
                        renderFeatures();
                        updateTextarea();
                    });
                    
                    container.append(tag);
                });
            }
        }
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        // Update hidden textarea
        function updateTextarea() {
            $('#features').val(features.join('\n'));
        }
        
        // Add feature
        $('#addFeatureBtn').on('click', function() {
            const feature = $('#featureInput').val().trim();
            
            if (feature === '') {
                toastr.warning('Please enter a feature first!');
                return;
            }
            
            if (features.includes(feature)) {
                toastr.error('This feature already exists!');
                return;
            }
            
            features.push(feature);
            renderFeatures();
            updateTextarea();
            $('#featureInput').val('').focus();
        });
        
        // Add feature on Enter key
        $('#featureInput').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#addFeatureBtn').click();
            }
        });
        
        // Load existing features if editing
        @if(isset($subscription) && $subscription->features)
            let existingFeatures = {!! json_encode(is_array($subscription->features) ? $subscription->features : explode("\n", trim($subscription->features))) !!};
            features = existingFeatures.filter(f => f.trim() !== '');
            renderFeatures();
            updateTextarea();
        @endif
    });
</script>
@endpush