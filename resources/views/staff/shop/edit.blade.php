@extends('staff.master_layout')
@section('title')
    <title>{{ __('Edit Shop') }}</title>
@endsection
@section('staff-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <div class="section-header-back">
                    <a href="{{ route('staff.shop.index') }}" class="btn btn-icon"><i class="fas fa-arrow-left"></i></a>
                </div>
                <h1>{{ __('Edit Shop') }}</h1>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>{{ __('Edit Shop') }}</h4>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('staff.shop.update', $shop->id) }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="shop_name">{{ __('Shop Name') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="shop_name" name="shop_name"
                                                    value="{{ old('shop_name', $shop->shop_name) }}" class="form-control"
                                                    placeholder="Enter Shop Name">
                                                @error('shop_name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="category">{{ __('Category') }} <span
                                                        class="text-danger">*</span></label>
                                                <select id="category" name="category" class="form-control select2">
                                                    <option value="">{{ __('Select Category') }}</option>
                                                    @foreach($shopCategories as $category)
                                                        <option value="{{ $category->name }}" {{ old('category', $shop->category) == $category->name ? 'selected' : '' }}>
                                                            {{ __($category->name) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('category')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="owner_name">{{ __('Owner Name') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="owner_name" name="owner_name"
                                                    value="{{ old('owner_name', $shop->owner_name) }}" class="form-control"
                                                    placeholder="Enter Owner Name">
                                                @error('owner_name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="phone_number">{{ __('Phone Number') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="phone_number" name="phone_number"
                                                    value="{{ old('phone_number', $shop->phone_number) }}"
                                                    class="form-control" placeholder="Enter Phone Number">
                                                @error('phone_number')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="whatsapp_number">{{ __('WhatsApp Number') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="whatsapp_number" name="whatsapp_number"
                                                    value="{{ old('whatsapp_number', $shop->whatsapp_number) }}"
                                                    class="form-control" placeholder="Enter WhatsApp Number">
                                                @error('whatsapp_number')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="email">{{ __('Email') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="email" id="email" name="email"
                                                    value="{{ old('email', $shop->email) }}" class="form-control"
                                                    placeholder="Enter Email">
                                                @error('email')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="district_id">{{ __('District') }} <span
                                                        class="text-danger">*</span></label>
                                                @if($staffDistrict)
                                                    <input type="text" class="form-control" value="{{ $staffDistrict->name }}"
                                                        readonly>
                                                    <input type="hidden" name="district_id" value="{{ $staffDistrict->id }}">
                                                @else
                                                    <select id="district_id" name="district_id" class="form-control select2"
                                                        required>
                                                        <option value="">{{ __('Select District') }}</option>
                                                        @if(isset($districts))
                                                            @foreach($districts as $district)
                                                                <option value="{{ $district->id }}" {{ old('district_id', $shop->district_id) == $district->id ? 'selected' : '' }}>
                                                                    {{ $district->name }}
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                @endif
                                                @error('district_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="location">{{ __('Location') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="location" name="location" class="form-control"
                                                    value="{{ old('location', $shop->location ?? auth('staff')->user()->location ?? 'University of Management & Technology C-II Block C 2 Phase 1 Johar Town, Lahore, 54770, Pakistan') }}">
                                                @error('location')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="address">{{ __('Address') }} <span
                                                        class="text-danger">*</span></label>
                                                <textarea id="address" name="address" class="form-control" rows="3"
                                                    placeholder="Enter Shop Address">{{ old('address', $shop->address) }}</textarea>
                                                @error('address')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="about_shop">{{ __('About Shop') }} <span
                                                        class="text-danger">*</span></label>
                                                <textarea id="about_shop" name="about_shop" class="form-control" rows="4"
                                                    placeholder="Describe the shop services and details">{{ old('about_shop', $shop->about_shop) }}</textarea>
                                                @error('about_shop')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Existing Shop Photos Section -->
                                    @if($shop->photos && count($shop->photos) > 0)
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>{{ __('Current Shop Photos') }}</label>
                                                    <div class="row">
                                                        @foreach($shop->photos as $photo)
                                                            <div class="col-md-3 mb-3">
                                                                <div class="position-relative">
                                                                    <img src="{{ asset('storage/' . $photo->photo_path) }}"
                                                                        alt="Shop Photo" class="img-fluid rounded"
                                                                        style="height: 150px; width: 100%; object-fit: cover;">

                                                                    @if($photo->is_primary)
                                                                        <span class="badge badge-primary"
                                                                            style="position: absolute; top: 5px; left: 5px;">Primary</span>
                                                                    @endif

                                                                    <div
                                                                        style="position: absolute; top: 5px; right: 5px; display: flex; gap: 5px;">
                                                                        @if(!$photo->is_primary)
                                                                            <button type="button"
                                                                                class="btn btn-success btn-sm set-primary-btn"
                                                                                data-photo-id="{{ $photo->id }}">
                                                                                <i class="fas fa-star"></i> Primary
                                                                            </button>
                                                                        @endif

                                                                        <button type="button"
                                                                            class="btn btn-danger btn-sm delete-photo-btn"
                                                                            data-photo-id="{{ $photo->id }}">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <small
                                                        class="form-text text-muted">{{ __('Click on "Primary" button to set as primary image. Click on delete button to remove photos.') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Add New Photos Section -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="shop_photos">{{ __('Add New Shop Photos') }}</label>
                                                <input type="file" id="shop_photos" name="shop_photos[]"
                                                    class="form-control" multiple accept="image/*">
                                                <small
                                                    class="form-text text-muted">{{ __('You can select multiple photos. Allowed formats: jpg, jpeg, png, gif') }}</small>
                                                @error('shop_photos')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                                @error('shop_photos.*')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
                                            <a href="{{ route('staff.shop.index') }}"
                                                class="btn btn-danger">{{ __('Cancel') }}</a>
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

    <!-- Select2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- jQuery CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>

    <!-- Toastr CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize Select2 for all dropdowns
            $('.select2').select2({
                placeholder: "{{ __('Search and select...') }}",
                allowClear: true,
                width: '100%'
            });

            // Configure Toastr
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            // Handle delete photo - Direct delete without confirmation
            $('.delete-photo-btn').on('click', function () {
                var photoId = $(this).data('photo-id');
                var button = $(this);

                $.ajax({
                    url: '{{ url("/staff/shop/delete-photo") }}/' + photoId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function () {
                        button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success('Deleted successfully', 'Success!');
                            setTimeout(function () {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Failed to delete photo!', 'Error!');
                            button.html('<i class="fas fa-trash"></i>').prop('disabled', false);
                        }
                    },
                    error: function (xhr) {
                        var errorMessage = 'An error occurred!';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage, 'Error!');
                        button.html('<i class="fas fa-trash"></i>').prop('disabled', false);
                    }
                });
            });

            // Handle set primary photo
            $('.set-primary-btn').on('click', function () {
                var photoId = $(this).data('photo-id');
                var button = $(this);

                console.log('Setting primary photo ID:', photoId);

                $.ajax({
                    url: '{{ url("/staff/shop/set-primary-photo") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        photo_id: photoId
                    },
                    beforeSend: function () {
                        button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                    },
                    success: function (response) {
                        console.log('Response:', response);
                        if (response.success) {
                            toastr.success('Updated successfully', 'Success!');
                            setTimeout(function () {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Failed to set primary photo!', 'Error!');
                            button.html('<i class="fas fa-star"></i> Primary').prop('disabled', false);
                        }
                    },
                    error: function (xhr) {
                        console.log('XHR Error:', xhr);
                        var errorMessage = 'An error occurred!';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage, 'Error!');
                        button.html('<i class="fas fa-star"></i> Primary').prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endsection