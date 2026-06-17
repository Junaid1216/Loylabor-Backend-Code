@extends('admin.master_layout')
@section('title')
<title>Complaint Details</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Complaint Details</h1>
            <div class="section-header-breadcrumb">
                <a href="{{ route('admin.help-supports.index') }}" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-8 mx-auto">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Submitted By</th>
                                    <td>
                                        @if($complaint->user)
                                            <a href="{{ route('admin.users.show', $complaint->user->id) }}">{{ $complaint->user->name }}</a> ({{ ucfirst($complaint->user->user_type) }})
                                        @else
                                            User Deleted
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Booking Reference</th>
                                    <td>
                                        @if($complaint->booking_id)
                                            #{{ $complaint->booking_id }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Issue Category</th>
                                    <td>{{ $complaint->issue_category }}</td>
                                </tr>
                                <tr>
                                    <th>Priority</th>
                                    <td>
                                        @if($complaint->priority == 'high')
                                            <span class="badge badge-danger">High</span>
                                        @elseif($complaint->priority == 'medium')
                                            <span class="badge badge-warning">Medium</span>
                                        @else
                                            <span class="badge badge-info">Low</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date Submitted</th>
                                    <td>{{ $complaint->created_at->format('d M, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{!! nl2br(e($complaint->description)) !!}</td>
                                </tr>
                                @if($complaint->screenshot)
                                    <tr>
                                        <th>Screenshot Attached</th>
                                        <td>
                                            <a href="{{ asset('storage/' . $complaint->screenshot) }}" target="_blank">
                                                <img src="{{ asset('storage/' . $complaint->screenshot) }}" alt="Screenshot" style="max-width: 100%; max-height: 400px; border-radius: 8px; border: 1px solid #ddd;">
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
