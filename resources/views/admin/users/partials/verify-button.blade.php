@if($verified)
    <button type="button" class="btn btn-success btn-sm" disabled>
        <i class="fa fa-check"></i> Verified
    </button>
@else
    <button type="button" class="btn btn-warning btn-sm verify-doc-btn"
            data-user-id="{{ $user->id }}"
            data-field="{{ $field }}"
            data-url="{{ route('admin.users.verify-document', $user) }}">
        <i class="fa fa-check-circle"></i> Verify
    </button>
@endif
