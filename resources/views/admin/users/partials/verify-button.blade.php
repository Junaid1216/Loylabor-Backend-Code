<form action="{{ route('admin.users.verify-document', $user) }}" method="POST" class="d-inline">
    @csrf
    <input type="hidden" name="field" value="{{ $field }}">
    @if($verified)
        <button type="button" class="btn btn-success btn-sm" disabled>
            <i class="fa fa-check"></i> Verified
        </button>
    @else
        <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Mark this document as verified?')">
            <i class="fa fa-check-circle"></i> Verify
        </button>
    @endif
</form>
