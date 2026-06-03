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
                                    <th>Email</th><td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <th>Phone</th><td>{{ $user->phone }}</td>
                                    <th>Type</th><td>{{ ucfirst($user->user_type) }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th><td>{{ ucfirst($user->status) }}</td>
                                    <th>Photo</th>
                                    <td>
                                        @if($user->photo)
                                            <img src="{{ $user->photo }}" width="100">
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            @if($user->user_type == 'technician')
                                <h4 class="mt-4">Technician Details</h4>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Bio</th><td colspan="3">{{ $user->bio }}</td>
                                    </tr>
                                    <tr>
                                        <th>Experience</th><td>{{ $user->experience }}</td>
                                        <th>Subscription</th><td>{{ $user->subscription }} (Ends: {{ $user->subscription_end }})</td>
                                    </tr>
                                    <tr>
                                        <th>Skills</th>
                                        <td>
                                            @php $skills = json_decode($user->skills, true) ?? []; @endphp
                                            @foreach($skills as $skill)
                                                <span class="badge badge-primary">{{ $skill }}</span>
                                            @endforeach
                                        </td>
                                        <th>Service Area</th>
                                        <td>
                                            @php $areas = json_decode($user->service_area, true) ?? []; @endphp
                                            @foreach($areas as $area)
                                                <span class="badge badge-info">{{ $area }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Availability</th>
                                        <td colspan="3">
                                            @php $avail = json_decode($user->availability, true) ?? []; @endphp
                                            <ul>
                                                @foreach($avail as $day => $time)
                                                    <li><strong>{{ $day }}:</strong> {{ $time }}</li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                </table>

                                <h4 class="mt-4">Documents</h4>
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <h5>CNIC Front</h5>
                                        @if($user->cnic_front)
                                            <img src="{{ $user->cnic_front }}" class="img-fluid border p-1" style="max-height:200px;">
                                        @else
                                            <p>Not Uploaded</p>
                                        @endif
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <h5>CNIC Back</h5>
                                        @if($user->cnic_back)
                                            <img src="{{ $user->cnic_back }}" class="img-fluid border p-1" style="max-height:200px;">
                                        @else
                                            <p>Not Uploaded</p>
                                        @endif
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <h5>Certificates</h5>
                                        @php $certs = json_decode($user->certificates, true) ?? []; @endphp
                                        @if(count($certs) > 0)
                                            @foreach($certs as $cert)
                                                <img src="{{ $cert }}" class="img-fluid border p-1 mb-2" style="max-height:200px;">
                                            @endforeach
                                        @else
                                            <p>Not Uploaded</p>
                                        @endif
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
