@extends('admin.master_layout')
@section('title')
    <title>{{ __('Booking Settings') }}</title>
@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>{{ __('Booking Settings') }}</h1>
            <div class="section-header-breadcrumb">
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-primary">
                    <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                </a>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.bookings.settings.update') }}">
                                @csrf

                                <div class="form-group">
                                    <label>{{ __('Request Expiry Minutes') }} <span class="text-danger">*</span></label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        name="booking_request_expiry_minutes"
                                        value="{{ old('booking_request_expiry_minutes', $minutes) }}"
                                        min="1"
                                        max="1440"
                                        required
                                    >
                                    <small class="text-muted">
                                        {{ __('If customer creates a booking request and technician does not accept within this time, request becomes expired.') }}
                                    </small>
                                </div>

                                <button class="btn btn-primary" type="submit">
                                    <i class="fa fa-save"></i> {{ __('Save') }}
                                </button>
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
@endpush

