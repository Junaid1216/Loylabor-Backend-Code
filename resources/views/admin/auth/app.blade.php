<!DOCTYPE html>
<html lang="en">

    <head>
        <link type="image/x-icon" href="" rel="shortcut icon">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">

        @yield('title')

        <link href="{{ asset('backend/css/bootstrap.min.css') }}" rel="stylesheet">
        <link href="{{ asset('backend/fontawesome/css/all.min.css') }}" rel="stylesheet">
        <link href="{{ asset('backend/css/style.css') }}?v={{ $setting?->version }}" rel="stylesheet">
        <link href="{{ asset('backend/css/bootstrap-social.css') }}" rel="stylesheet">
        <link href="{{ asset('backend/css/components.css') }}?v={{ $setting?->version }}" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('backend/css/iziToast.min.css') }}">
        <link href="{{ asset('backend/css/bootstrap4-toggle.min.css') }}" rel="stylesheet">
        <link href="{{ asset('backend/css/dev.css') }}?v={{ $setting?->version }}" rel="stylesheet">
        @if (session()->has('text_direction') && session()->get('text_direction') !== 'ltr')
            <link href="{{ asset('backend/css/rtl.css') }}?v={{ $setting?->version }}" rel="stylesheet">
            <link href="{{ asset('backend/css/dev_rtl.css') }}?v={{ $setting?->version }}" rel="stylesheet">
        @endif
        <link href="{{ asset('backend/css/select2.min.css') }}" rel="stylesheet">
        <link href="{{ asset('backend/css/tagify.css') }}" rel="stylesheet">
        <link href="{{ asset('backend/css/bootstrap-tagsinput.css') }}" rel="stylesheet">
        <link href="{{ asset('backend/css/fontawesome-iconpicker.min.css') }}" rel="stylesheet">
        <link href="{{ asset('backend/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
        <link href="{{ asset('backend/clockpicker/dist/bootstrap-clockpicker.css') }}" rel="stylesheet">
        <link href="{{ asset('backend/datetimepicker/jquery.datetimepicker.css') }}" rel="stylesheet">
        <link href="{{ asset('backend/css/iziToast.min.css') }}" rel="stylesheet">

        <script src="{{ asset('global/js/jquery-3.7.1.min.js') }}"></script>

    </head>

    <body>
        <div id="app">
            @yield('content')
        </div>

        <script src="{{ asset('backend/js/popper.min.js') }}"></script>
        <script src="{{ asset('backend/js/bootstrap.min.js') }}"></script>
        <script src="{{ asset('backend/js/jquery.nicescroll.min.js') }}"></script>
        <script src="{{ asset('backend/js/moment.min.js') }}"></script>
        <script src="{{ asset('backend/js/stisla.js') }}"></script>
        <script src="{{ asset('backend/js/scripts.js') }}?v={{ $setting?->version }}"></script>
        <script src="{{ asset('backend/js/iziToast.min.js') }}"></script>
    <script>
        window.toastr = {
            success: function(message) { iziToast.success({ message: message, position: 'topRight' }); },
            error: function(message) { iziToast.error({ message: message, position: 'topRight' }); },
            warning: function(message) { iziToast.warning({ message: message, position: 'topRight' }); },
            info: function(message) { iziToast.info({ message: message, position: 'topRight' }); }
        };
    </script>

        <script>
            @if (Session::has('message'))
                var type = "{{ Session::get('alert-type', 'info') }}"
                switch (type) {
                    case 'info':
                        toastr.info("{{ Session::get('message') }}");
                        break;
                    case 'success':
                        toastr.success("{{ Session::get('message') }}");
                        break;
                    case 'warning':
                        toastr.warning("{{ Session::get('message') }}");
                        break;
                    case 'error':
                        toastr.error("{{ Session::get('message') }}");
                        break;
                }
            @endif
        </script>

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <script>
                    toastr.error('{{ $error }}');
                </script>
            @endforeach
        @endif

    </body>

</html>
