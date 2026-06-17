@extends('admin.master_layout')
@section('title')
<title>Complaints / Help & Support</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Complaints</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
                <div class="breadcrumb-item">Complaints</div>
            </div>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="table-1">
                                    <thead>
                                        <tr>
                                            <th>SN</th>
                                            <th>User</th>
                                            <th>Issue Category</th>
                                            <th>Priority</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($complaints as $index => $complaint)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    @if($complaint->user)
                                                        {{ $complaint->user->name }}
                                                        <br>
                                                        <small>{{ ucfirst($complaint->user->user_type) }}</small>
                                                    @else
                                                        User Deleted
                                                    @endif
                                                </td>
                                                <td>{{ $complaint->issue_category }}</td>
                                                <td>
                                                    @if($complaint->priority == 'high')
                                                        <span class="badge badge-danger">High</span>
                                                    @elseif($complaint->priority == 'medium')
                                                        <span class="badge badge-warning">Medium</span>
                                                    @else
                                                        <span class="badge badge-info">Low</span>
                                                    @endif
                                                </td>
                                                <td>{{ $complaint->created_at->format('d M, Y') }}</td>
                                                <td>
                                                    <a href="{{ route('admin.help-supports.show', $complaint->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>
                                                    <form action="{{ route('admin.help-supports.destroy', $complaint->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this complaint?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="float-right">
                                {{ $complaints->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
