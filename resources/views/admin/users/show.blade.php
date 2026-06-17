@extends('admin.master_layout')
@section('title')
    <title>User Details</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>User Details</h1>
            <div class="section-header-breadcrumb">
                <a href="{{ route('admin.users.index') }}" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4>Basic Info</h4>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Name</th><td>{{ $user->name }}</td>
                                    <th>Email</th>
                                    <td>
                                        {{ $user->email }}
                                        @if(!$user->is_verified)
                                            <button type="button" class="btn btn-sm btn-success ml-2 verify-email-btn" 
                                                    data-user-id="{{ $user->id }}" 
                                                    data-url="{{ route('admin.users.verify-email', $user->id) }}">
                                                <i class="fas fa-check-circle"></i> Verify
                                            </button>
                                        @endif
                                     </td>
                                 </tr>
                                 <tr>
                                    <th>Phone</th><td>{{ $user->phone }}</td>
                                    <th>Type</th><td>{{ ucfirst($user->user_type) }}</td>
                                 </tr>
                                 <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'warning' }}">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                     </td>
                                    <th>Email Verified</th>
                                    <td>
                                        @if($user->is_verified)
                                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Verified</span>
                                        @else
                                            <span class="badge badge-warning"><i class="fas fa-times-circle"></i> Not Verified</span>
                                        @endif
                                     </td>
                                 </tr>
                                 <tr>
                                    <th>Photo</th>
                                    <td colspan="3">
                                        @php
                                            $photoPath = $user->photo;
                                            if ($photoPath && !str_starts_with($photoPath, 'http') && !str_starts_with($photoPath, 'backend/')) {
                                                $photoPath = 'storage/' . $photoPath;
                                            } elseif ($photoPath && str_starts_with($photoPath, 'backend/')) {
                                                $photoPath = asset($photoPath);
                                            } elseif ($photoPath && !str_starts_with($photoPath, 'http')) {
                                                $photoPath = asset($photoPath);
                                            } else {
                                                $photoPath = asset('backend/img/default-profile.jpg');
                                            }
                                        @endphp
                                        <img src="{{ $photoPath }}" width="120" class="rounded border">
                                        @if($user->user_type == 'technician')
                                            @include('admin.users.partials.verify-button', ['field' => 'photo', 'verified' => $user->photo_verified])
                                        @endif
                                     </td>
                                 </tr>
                             </table>

                            @if($user->user_type == 'technician')
                                <h4 class="mt-4">Technician Details</h4>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Bio</th><td colspan="3">{{ $user->bio ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Experience</th><td>{{ $user->experience ?? 'N/A' }}</td>
                                        <th>Subscription Plan</th>
                                        <td>
                                            @php $plan = $user->subscriptionPlan; @endphp
                                            @if($user->subscription_id && $plan)
                                                <strong>{{ $plan->name }}</strong>
                                                <span class="text-muted">({{ $plan->duration_months }} {{ __('Months') }})</span>
                                                <br>
                                                <small class="text-muted">
                                                    {{ __('Price') }}: Rs. {{ number_format($plan->price_pkr, 2) }}
                                                    @if($plan->saving_price)
                                                        | {{ __('Offer') }}: Rs. {{ number_format($plan->saving_price, 2) }}
                                                    @endif
                                                </small>
                                                <br>
                                                <span class="badge badge-{{ $user->payment_status == 'verified' ? 'success' : 'warning' }}">
                                                    {{ __('Payment') }}: {{ ucfirst($user->payment_status ?? 'pending') }}
                                                </span>
                                                <span class="badge badge-{{ $user->subscription == 'active' ? 'success' : 'secondary' }}">
                                                    {{ __('Subscription') }}: {{ ucfirst($user->subscription ?? 'inactive') }}
                                                </span>
                                                @if($user->subscription_end)
                                                    <small class="text-muted d-block">{{ __('Ends') }}: {{ $user->subscription_end }}</small>
                                                @endif
                                                @if($user->payment_status !== 'verified')
                                                    <button type="button" class="btn btn-sm btn-primary ml-2 mt-1" data-bs-toggle="modal" data-bs-target="#verifyPaymentModal">
                                                        <i class="fas fa-check-circle"></i> Verify
                                                    </button>
                                                @endif
                                            @elseif($user->subscription_id)
                                                <span class="text-warning">{{ __('Plan ID') }}: {{ $user->subscription_id }} ({{ __('plan not found') }})</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                         </td>
                                    </tr>
                                    <tr>
                                        <th>Payment Screenshot</th>
                                        <td colspan="3">
                                            @if($user->payment_screenshot)
                                                <a href="{{ asset('storage/' . $user->payment_screenshot) }}" target="_blank">
                                                    <img src="{{ asset('storage/' . $user->payment_screenshot) }}" width="120" class="rounded border">
                                                </a>
                                            @else
                                                <span class="text-muted">No screenshot uploaded</span>
                                            @endif
                                         </td>
                                    </tr>
                                    <tr>
                                        <th>Skills</th>
                                        <td>
                                            @php
                                                $skills = $user->skills;
                                                if (is_string($skills)) {
                                                    $skills = json_decode($skills, true);
                                                }
                                                $skills = is_array($skills) ? $skills : [];
                                            @endphp
                                            
                                            @forelse($skills as $skill)
                                                <span class="badge badge-primary">
                                                    {{ is_array($skill) ? ($skill['name'] ?? json_encode($skill)) : $skill }}
                                                </span>
                                            @empty
                                                <span class="text-muted">No skills added</span>
                                            @endforelse
                                         </td>
                                        <th>Service Area</th>
                                        <td>
                                            @php
                                                $serviceArea = $user->service_area;
                                                if (is_string($serviceArea)) {
                                                    $serviceArea = json_decode($serviceArea, true);
                                                }
                                                $serviceArea = is_array($serviceArea) ? $serviceArea : [];
                                            @endphp
                                            
                                            @forelse($serviceArea as $area)
                                                <span class="badge badge-info">
                                                    {{ is_array($area) ? ($area['name'] ?? json_encode($area)) : $area }}
                                                </span>
                                            @empty
                                                <span class="text-muted">No service areas added</span>
                                            @endforelse
                                         </td>
                                    </tr>
                                    <tr>
                                        <th>Weekly Availability</th>
                                        <td colspan="3">
                                            @if($user->availabilities->count())
                                                <table class="table table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Day</th>
                                                            <th>Start</th>
                                                            <th>End</th>
                                                            <th>Available</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($user->availabilities as $slot)
                                                            <tr>
                                                                <td>{{ ucfirst($slot->day) }}</td>
                                                                <td>{{ $slot->start_time ? substr($slot->start_time, 0, 5) : '-' }}</td>
                                                                <td>{{ $slot->end_time ? substr($slot->end_time, 0, 5) : '-' }}</td>
                                                                <td>
                                                                    <span class="badge badge-{{ $slot->is_available ? 'success' : 'secondary' }}">
                                                                        {{ $slot->is_available ? 'Yes' : 'No' }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <span class="text-muted">No schedule saved (default Mon-Sat 9-6 applied on register).</span>
                                            @endif
                                         </td>
                                    </tr>
                                    <tr>
                                        <th>Admin Verification</th>
                                        <td colspan="3">
                                            <span class="badge badge-{{ $user->cnic_front_verified ? 'success' : 'secondary' }}">CNIC Front</span>
                                            <span class="badge badge-{{ $user->cnic_back_verified ? 'success' : 'secondary' }}">CNIC Back</span>
                                            <span class="badge badge-{{ $user->photo_verified ? 'success' : 'secondary' }}">Photo</span>
                                            <span class="badge badge-{{ $user->certificates_verified ? 'success' : 'secondary' }}">Certificates</span>
                                            @if($user->allDocumentsVerified())
                                                <span class="badge badge-success ml-2">All Verified — Account can go Active</span>
                                            @endif
                                         </td>
                                    </tr>
                                </table>

                                <h4 class="mt-4">Documents</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card border">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <strong>CNIC Front</strong>
                                                @include('admin.users.partials.verify-button', ['field' => 'cnic_front', 'verified' => $user->cnic_front_verified])
                                            </div>
                                            <div class="card-body text-center">
                                                @php
                                                    $cnicFrontPath = $user->cnic_front;
                                                    if ($cnicFrontPath && !str_starts_with($cnicFrontPath, 'http') && !str_starts_with($cnicFrontPath, 'backend/')) {
                                                        $cnicFrontPath = asset('storage/' . $cnicFrontPath);
                                                    } elseif ($cnicFrontPath && str_starts_with($cnicFrontPath, 'backend/')) {
                                                        $cnicFrontPath = asset($cnicFrontPath);
                                                    } elseif ($cnicFrontPath && !str_starts_with($cnicFrontPath, 'http')) {
                                                        $cnicFrontPath = asset($cnicFrontPath);
                                                    } else {
                                                        $cnicFrontPath = asset('backend/img/cnic-sample.jpg');
                                                    }
                                                @endphp
                                                <a href="{{ $cnicFrontPath }}" target="_blank">
                                                    <img src="{{ $cnicFrontPath }}" class="img-fluid border" style="max-height:220px;" onerror="this.src='{{ asset('backend/img/cnic-sample.jpg') }}'">
                                                </a>
                                                @if(!$user->cnic_front)
                                                    <p class="text-muted small mt-2 mb-0">Showing sample CNIC (dummy)</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <strong>CNIC Back</strong>
                                                @include('admin.users.partials.verify-button', ['field' => 'cnic_back', 'verified' => $user->cnic_back_verified])
                                            </div>
                                            <div class="card-body text-center">
                                                @php
                                                    $cnicBackPath = $user->cnic_back;
                                                    if ($cnicBackPath && !str_starts_with($cnicBackPath, 'http') && !str_starts_with($cnicBackPath, 'backend/')) {
                                                        $cnicBackPath = asset('storage/' . $cnicBackPath);
                                                    } elseif ($cnicBackPath && str_starts_with($cnicBackPath, 'backend/')) {
                                                        $cnicBackPath = asset($cnicBackPath);
                                                    } elseif ($cnicBackPath && !str_starts_with($cnicBackPath, 'http')) {
                                                        $cnicBackPath = asset($cnicBackPath);
                                                    } else {
                                                        $cnicBackPath = asset('backend/img/cnic-sample.jpg');
                                                    }
                                                @endphp
                                                <a href="{{ $cnicBackPath }}" target="_blank">
                                                    <img src="{{ $cnicBackPath }}" class="img-fluid border" style="max-height:220px;" onerror="this.src='{{ asset('backend/img/cnic-sample.jpg') }}'">
                                                </a>
                                                @if(!$user->cnic_back)
                                                    <p class="text-muted small mt-2 mb-0">Showing sample CNIC back (dummy)</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <strong>Certificates</strong>
                                                @include('admin.users.partials.verify-button', ['field' => 'certificates', 'verified' => $user->certificates_verified])
                                            </div>
                                            <div class="card-body text-center">
                                                @php
                                                    $certFiles = $user->certificates;
                                                    if (is_string($certFiles)) {
                                                        $certFiles = json_decode($certFiles, true);
                                                    }
                                                    $certFiles = is_array($certFiles) ? $certFiles : [];
                                                    $firstCert = count($certFiles) > 0 ? $certFiles[0] : null;
                                                    
                                                    if ($firstCert) {
                                                        if (!str_starts_with($firstCert, 'http') && !str_starts_with($firstCert, 'backend/')) {
                                                            $certUrl = asset('storage/' . $firstCert);
                                                        } elseif (str_starts_with($firstCert, 'backend/')) {
                                                            $certUrl = asset($firstCert);
                                                        } else {
                                                            $certUrl = asset($firstCert);
                                                        }
                                                    } else {
                                                        $certUrl = asset('backend/img/sample-certificate.pdf');
                                                    }
                                                    
                                                    $totalCertificates = count($certFiles);
                                                @endphp
                                                <div class="p-3 bg-light rounded">
                                                    <i class="fas fa-file-pdf fa-3x text-danger"></i>
                                                    <p class="mb-2 mt-2">
                                                        <strong>Technician {{ $totalCertificates > 1 ? 'Certificates (' . $totalCertificates . ')' : 'Certificate' }}</strong>
                                                    </p>
                                                    
                                                    @if($totalCertificates > 1)
                                                        <div class="dropdown mb-2">
                                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                                                View All Certificates ({{ $totalCertificates }})
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                @foreach($certFiles as $index => $cert)
                                                                    @php
                                                                        if (!str_starts_with($cert, 'http') && !str_starts_with($cert, 'backend/')) {
                                                                            $certLink = asset('storage/' . $cert);
                                                                        } elseif (str_starts_with($cert, 'backend/')) {
                                                                            $certLink = asset($cert);
                                                                        } else {
                                                                            $certLink = asset($cert);
                                                                        }
                                                                    @endphp
                                                                    <a class="dropdown-item" href="{{ $certLink }}" target="_blank">
                                                                        Certificate #{{ $index + 1 }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                    
                                                    <a href="{{ $certUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fa fa-eye"></i> {{ $totalCertificates > 1 ? 'View First Certificate' : 'View Certificate' }}
                                                    </a>
                                                    
                                                    @if(!$firstCert)
                                                        <p class="text-muted small mt-2 mb-0">Sample certificate (dummy PDF)</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<!-- Verify Payment Modal -->
<div class="modal fade" id="verifyPaymentModal" tabindex="-1" aria-labelledby="verifyPaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('admin.users.verify-payment', $user->id) }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="verifyPaymentModalLabel">Verify Subscription Payment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Please upload the payment screenshot to verify the subscription plan.</p>
            <div class="form-group">
                <label>Payment Screenshot <span class="text-danger">*</span></label>
                <input type="file" name="payment_screenshot" class="form-control" accept="image/*" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Verify Payment</button>
          </div>
      </form>
    </div>
  </div>
</div>

</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Toast function
    function showToast(message, type = 'success') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        
        Toast.fire({
            icon: type,
            title: message
        });
    }
    
    // Verify document button click for technicians
    $(document).on('click', '.verify-doc-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const field = $btn.data('field');
        const url = $btn.data('url');
        
        const originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Verifying...').prop('disabled', true);
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                user_id: userId,
                field: field,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    $btn.replaceWith('<span class="badge badge-success"><i class="fas fa-check-circle"></i> Verified</span>');
                    
                    if (response.status_updated) {
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    showToast(response.message, 'error');
                    $btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function(xhr) {
                let message = 'Something went wrong!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showToast(message, 'error');
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
    
    // Email verification button for customers
    $(document).on('click', '.verify-email-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const url = $btn.data('url');
        
        const originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Verifying...').prop('disabled', true);
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                user_id: userId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    
                    // Remove the verify button
                    $btn.remove();
                    
                    // Update the email verified status display
                    $('th:contains("Email Verified")').next('td').html('<span class="badge badge-success"><i class="fas fa-check-circle"></i> Verified</span>');
                    
                    // Reload page after 2 seconds to reflect all changes
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(response.message, 'error');
                    $btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function(xhr) {
                let message = 'Something went wrong!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showToast(message, 'error');
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush