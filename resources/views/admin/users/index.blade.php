@extends('admin.master_layout')
@section('title')
    <title>Users</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Users Management</h1>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ ucfirst($user->user_type) }}</td>
                                        <td>
                                            @php
                                                // Determine status display
                                                $displayStatus = $user->status;
                                                $badgeColor = 'warning';
                                                
                                                if($user->user_type === 'customer' && !$user->is_verified) {
                                                    $displayStatus = 'pending';
                                                    $badgeColor = 'warning';
                                                } elseif($user->status === 'active') {
                                                    $badgeColor = 'success';
                                                } elseif($user->status === 'inactive') {
                                                    $badgeColor = 'danger';
                                                } elseif($user->status === 'pending') {
                                                    $badgeColor = 'warning';
                                                }
                                            @endphp
                                            <span class="badge badge-{{ $badgeColor }}">{{ ucfirst($displayStatus) }}</span>
                                            
                                            @if($user->user_type === 'technician' && $user->allDocumentsVerified())
                                                <span class="badge badge-info">Docs OK</span>
                                            @endif
                                            
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-info btn-sm"><i class="fa fa-eye"></i></a>
                                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fa fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection