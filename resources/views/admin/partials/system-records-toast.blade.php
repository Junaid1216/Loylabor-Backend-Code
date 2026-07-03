@if(session('success'))
<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            toastr.success(@json(session('success')));
        }, 300);
    });
</script>
@endif
@if(session('error'))
<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            toastr.error(@json(session('error')));
        }, 300);
    });
</script>
@endif
