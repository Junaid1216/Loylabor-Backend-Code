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
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4>Basic Info</h4>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Name</th><td>{{ $user->name }}</td>
                                    <th>Email</th><td>{{ $user->email }}</td>
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
                                    <td>{{ $user->is_verified ? 'Yes' : 'No' }}</td>
                                </tr>
                                <tr>
                                    <th>Photo</th>
                                    <td colspan="3">
                                        <img src="{{ $user->documentUrl($user->photo, 'technician-photo.jpg') }}" width="120" class="rounded border">
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
                                        <th>Subscription</th><td>{{ $user->subscription }} @if($user->subscription_end)(Ends: {{ $user->subscription_end }})@endif</td>
                                    </tr>
                                    <tr>
                                        <th>Skills</th>
                                        <td>
                                            @foreach(($user->skills ?? []) as $skill)
                                                <span class="badge badge-primary">{{ is_array($skill) ? ($skill['name'] ?? '') : $skill }}</span>
                                            @endforeach
                                        </td>
                                        <th>Service Area</th>
                                        <td>
                                            @foreach(($user->service_area ?? []) as $area)
                                                <span class="badge badge-info">{{ is_array($area) ? ($area['name'] ?? '') : $area }}</span>
                                            @endforeach
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

                                <h4 class="mt-4">Documents <small class="text-muted">(dummy preview if not uploaded)</small></h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card border">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <strong>CNIC Front</strong>
                                                @include('admin.users.partials.verify-button', ['field' => 'cnic_front', 'verified' => $user->cnic_front_verified])
                                            </div>
                                            <div class="card-body text-center">
                                                <a href="{{ $user->documentUrl($user->cnic_front, 'cnic-front.jpg') }}" target="_blank">
                                                    <img src="{{ $user->documentUrl($user->cnic_front, 'cnic-front.jpg') }}" class="img-fluid border" style="max-height:220px;">
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
                                                <a href="{{ $user->documentUrl($user->cnic_back, 'cnic-back.jpg') }}" target="_blank">
                                                    <img src="{{ $user->documentUrl($user->cnic_back, 'cnic-back.jpg') }}" class="img-fluid border" style="max-height:220px;">
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
                                                <strong>Certificate (PDF)</strong>
                                                @include('admin.users.partials.verify-button', ['field' => 'certificates', 'verified' => $user->certificates_verified])
                                            </div>
                                            <div class="card-body text-center">
                                                @php
                                                    $certFiles = $user->certificates ?? [];
                                                    $firstCert = is_array($certFiles) && count($certFiles) ? $certFiles[0] : null;
                                                    $certUrl = $firstCert
                                                        ? (str_ends_with(strtolower($firstCert), '.pdf') ? asset('storage/'.$firstCert) : asset('storage/'.$firstCert))
                                                        : asset('dummy/certificate.pdf');
                                                @endphp
                                                <div class="p-3 bg-light rounded">
                                                    <i class="fas fa-file-pdf fa-3x text-danger"></i>
                                                    <p class="mb-2 mt-2"><strong>Technician Certification</strong></p>
                                                    <a href="{{ $certUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fa fa-eye"></i> View PDF
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
</div>
@endsection
