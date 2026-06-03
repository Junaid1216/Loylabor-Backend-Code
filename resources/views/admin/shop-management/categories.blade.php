@extends('admin.master_layout')
@section('title')
    <title>{{ __('Manage Shop Categories') }}</title>
@endsection

@section('admin-content')
    <div class="main-content">
        <section class="section">
            <x-admin.breadcrumb title="{{ __('Manage Shop Categories') }}" :list="[
            ]" />

            <div class="section-body">
                <div class="mt-4 row">
                    <div class="col">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <x-admin.form-title :text="__('Shop Categories')" /> 
                                <div>
                                    @can('shop.category.create')
                                    <!-- Manual onclick instead of data-toggle -->
                                    <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                                        <i class="fas fa-plus"></i> {{ __('Add New Category') }}
                                    </button>
                                    @endcan
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('SN') }}</th>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($categories as $index => $category)
                                                <tr>
                                                    <td>{{ $categories->firstItem() + $index }}</td>
                                                    <td>{{ $category->name }}</td>
                                                    <td>
                                                                                            @can('shop.category.edit')

                                                        <input onchange="changeCategoryStatus({{ $category->id }})"
    id="status_toggle_{{ $category->id }}" 
    type="checkbox"
    {{ $category->is_active ? 'checked' : '' }}
    data-toggle="toggle" 
    data-onlabel="{{ __('Active') }}"
    data-offlabel="{{ __('Inactive') }}" 
    data-onstyle="success"
    data-offstyle="danger">
      @endcan
                                                    </td>
                                                    <td>
                                                        @can('shop.category.delete')
                                                        <button type="button" onclick="deleteCategory({{ $category->id }})"
                                                            class="btn btn-sm btn-danger">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                        @endcan
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center">
                                                        <div class="alert alert-info mb-0">
                                                            <i class="fas fa-info-circle"></i> 
                                                            {{ __('No categories found!') }}
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    
                                    @if($categories->isNotEmpty())
                                        <div class="float-right">
                                            {{ $categories->links() }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Create Category Modal -->
    <div class="modal fade" id="createCategoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Create New Category') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="createCategoryForm" action="{{ route('admin.shop-categories.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">{{ __('Category Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="{{ __('Enter category name') }}" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="is_active">{{ __('Status') }}</label>
                            <select name="is_active" id="is_active" class="form-control">
                                <option value="1">{{ __('Active') }}</option>
                                <option value="0">{{ __('Inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">{{ __('Save Category') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Item Delete Confirmation') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>{{ __('Are You sure want to delete this item ?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                   <button type="button" class="btn" id="confirmDeleteBtn" style="background-color: #FE7701; border-color: #FE7701; color: #fff;">
						
    {{ __('Yes,Delete') }}
</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    // Function to open create modal
    function openCreateModal() {
        $('#createCategoryModal').modal('show');
    }
    
    // Change Category Status Function
    function changeCategoryStatus(id) {
        var isDemo = "{{ env('APP_MODE') ?? 'LIVE' }}";
        if (isDemo == 'DEMO') {
            toastr.error('This Is Demo Version. You Can Not Change Anything');
            $('#status_toggle_' + id).bootstrapToggle('toggle');
            return;
        }
        
        $.ajax({
            type: "PUT",
            url: "{{ url('admin/shop-management/categories/update-status') }}/" + id,
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success(response.message);
            },
            error: function(err) {
                toastr.error('Something went wrong!');
                $('#status_toggle_' + id).bootstrapToggle('toggle');
                console.log(err);
            }
        });
    }
    
    $(document).ready(function() {
        // Initialize bootstrap toggle
        if (typeof $.fn.bootstrapToggle !== 'undefined') {
            $('input[data-toggle="toggle"]').bootstrapToggle();
        }
    });
    
    var deleteCategoryId = null;

    // Create Category
    $('#createCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var url = form.attr('action');
        var submitBtn = $('#submitBtn');
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __("Saving...") }}');
        
        $.ajax({
            type: "POST",
            url: url,
            data: form.serialize(),
            success: function(response) {
                $('#createCategoryModal').modal('hide');
                toastr.success(response.message);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('{{ __("Save Category") }}');
                
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        toastr.error(value[0]);
                    });
                } else {
                    toastr.error('Something went wrong!');
                }
            }
        });
    });

    // Delete Category
    function deleteCategory(id) {
        deleteCategoryId = id;
        $('#deleteCategoryModal').modal('show');
    }

    // Confirm Delete
    $('#confirmDeleteBtn').on('click', function() {
        if (!deleteCategoryId) return;
        
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __("Deleting...") }}');
        
        $.ajax({
            type: "DELETE",
            url: "{{ url('admin/shop-management/categories') }}/" + deleteCategoryId,
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#deleteCategoryModal').modal('hide');
                toastr.success(response.message);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr) {
                $('#deleteCategoryModal').modal('hide');
                btn.prop('disabled', false).html('{{ __("Delete") }}');
                
                if (xhr.status === 400) {
                    toastr.error(xhr.responseJSON.error);
                } else {
                    toastr.error('Cannot delete category! It may have shops associated.');
                }
                deleteCategoryId = null;
            }
        });
    });
    
    // Reset form when modal closes
    $('#createCategoryModal').on('hidden.bs.modal', function() {
        $('#createCategoryForm')[0].reset();
        $('#is_active').val('1');
        $('#submitBtn').prop('disabled', false).html('{{ __("Save Category") }}');
    });
    
    $('#deleteCategoryModal').on('hidden.bs.modal', function() {
        $('#confirmDeleteBtn').prop('disabled', false).html('{{ __("Delete") }}');
        deleteCategoryId = null;
    });
</script>
@endpush