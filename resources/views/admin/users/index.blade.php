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
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="type_filter">{{ __('Filter by Type') }}</label>
                                        <select id="type_filter" class="form-control select2">
                                            <option value="">{{ __('All') }}</option>
                                            <option value="user" @selected(request('type') === 'user')>{{ __('Users') }}</option>
                                            <option value="technician" @selected(request('type') === 'technician')>{{ __('Technicians') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
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
                                        <td>{{ $user->user_type === 'customer' ? 'User' : ucfirst($user->user_type) }}</td>
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
                                            <a class="btn btn-danger btn-sm deleteForm" href="javascript:;" data-url="{{ route('admin.users.destroy', $user->id) }}"><i class="fa fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="float-right">
                                {{ $users->links() }}
                            </div>
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
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "{{ __('Search type...') }}",
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: Infinity
            });

            $('#type_filter').on('change', function() {
                var type = $(this).val();
                var url = new URL(window.location.href);

                if (type === '') {
                    url.searchParams.delete('type');
                } else {
                    url.searchParams.set('type', type);
                }

                url.searchParams.delete('page');
                window.location.href = url.toString();
            });
        });
    </script>
@endpush