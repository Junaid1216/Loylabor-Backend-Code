@extends('admin.master_layout')
@section('title')
    <title>{{ __('Agent List') }}</title>
@endsection
@section('admin-content')
    @can('staff.view')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('Agent List') }}</h1>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h4>{{ __('Agent List') }} <small class="font-weight-bold text-danger"> ({{ __('The default password for all agent members is 12345678. This password is automatically generated when a new agent member is created.') }})</small></h4>
                                @can('staff.create')
                                <div>
                                    <a class="btn btn-primary" href="{{ route('admin.staff.create') }}"><i
                                            class="fa fa-plus"></i> {{ __('Add New') }}</a>
                                </div>
                                @endcan
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="district_filter">{{ __('Filter by District') }}</label>
                                            <select id="district_filter" class="form-control select2">
                                                <option value="">{{ __('All Districts') }}</option>
                                                @if(isset($districts))
                                                    @foreach($districts as $district)
                                                        <option value="{{ $district->id }}">{{ $district->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive table-invoice">
                                    <table class="table table-striped" id="staffTable">
                                        <thead>
                                            <tr>
                                                <th>{{ __('SN') }}</th>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Email') }}</th>
                                                <th>{{ __('Phone') }}</th>
                                                <th>{{ __('District') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($staffs as $index => $staff)
                                                <tr>
                                                    <td>{{ $staffs->firstItem() + $index }}</td>
                                                    <td>{{ $staff->name }}</td>
                                                    <td>{{ $staff->email }}</td>
                                                    <td>{{ $staff->phone }}</td>
                                                    <td data-district-id="{{ $staff->district_id ?? '' }}">
                                                        @if($staff->district)
                                                            <span class="badge badge-info">{{ $staff->district->name }}</span>
                                                        @else
                                                            <span class="text-muted">{{ __('N/A') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <!-- Toggle Button -->
                                                         @can('staff.edit')
                                                        <input 
                                                            type="checkbox" 
                                                            id="status_toggle_{{ $staff->id }}"
                                                            onchange="changeStaffStatus({{ $staff->id }})"
                                                            {{ $staff->status == 1 ? 'checked' : '' }}
                                                            data-toggle="toggle" 
                                                            data-onlabel="{{ __('Active') }}"
                                                            data-offlabel="{{ __('Inactive') }}" 
                                                            data-onstyle="success"
                                                            data-offstyle="danger"
                                                            data-size="small"
                                                        >
                                                        @endcan
                                                    </td>
                                                    <td>
                                                        @can('staff.edit')
                                                        <a class="btn btn-primary btn-sm"
                                                            href="{{ route('admin.staff.edit', $staff->id) }}"><i
                                                                class="fa fa-edit" aria-hidden="true"></i></a>
                                                        @endcan
                                                        @can('staff.delete')
                                                        <a class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                                            data-bs-target="#deleteModal" href="javascript:;"
                                                            onclick="deleteData({{ $staff->id }})"><i
                                                                class="fa fa-trash" aria-hidden="true"></i></a>
                                                        @endcan
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-center">
                                {{ $staffs->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @else
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('Access Denied') }}</h1>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="alert alert-danger">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    {{ __('You do not have permission to view staff list.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @endcan
@endsection

@push('js')
    <!-- Select2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        "use strict";

        $(document).ready(function() {
            // Initialize Select2 for district filter
            $('.select2').select2({
                placeholder: "{{ __('Search district...') }}",
                allowClear: true,
                width: '100%'
            });

            // District filtering functionality
            $('#district_filter').on('change', function() {
                var districtId = $(this).val();
                
                if(districtId === '') {
                    // Show all rows
                    $('#staffTable tbody tr').show();
                } else {
                    // Filter by district
                    $('#staffTable tbody tr').each(function() {
                        var row = $(this);
                        var staffDistrictId = row.find('td:eq(4)').data('district-id');
                        
                        if(staffDistrictId == districtId) {
                            row.show();
                        } else {
                            row.hide();
                        }
                    });
                }
            });
        });

        function deleteData(id) {
            let url = '{{ route('admin.staff.destroy', ':id') }}';
            url = url.replace(':id', id);
            $("#deleteForm").attr('action', url);
        }

        function changeStaffStatus(staffId) {
            // Get the status from checkbox
            let status = $('#status_toggle_' + staffId).prop('checked') ? 1 : 0;
            
            $.ajax({
                url: '{{ route('staff.change.status', ':id') }}'.replace(':id', staffId),
                type: 'POST',
                data: {
                    status: status,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if(response.success) {
                        toastr.success('Updated successfully');
                    } else {
                        toastr.error('Something went wrong');
                        // Revert the toggle if failed
                        $('#status_toggle_' + staffId).bootstrapToggle(status == 1 ? 'off' : 'on');
                    }
                },
                error: function(xhr) {
                    toastr.error('Error updating status');
                    // Revert the toggle if error
                    $('#status_toggle_' + staffId).bootstrapToggle(status == 1 ? 'off' : 'on');
                }
            });
        }
    </script>
@endpush