const fs = require('fs');

const authFiles = [
    'c:\\xampp\\htdocs\\homeservices-12Mar2026\\resources\\views\\admin\\auth\\app.blade.php',
    'c:\\xampp\\htdocs\\homeservices-12Mar2026\\resources\\views\\staff\\auth\\app.blade.php'
];

const iziToastCSS = `<link rel="stylesheet" href="{{ asset('backend/css/iziToast.min.css') }}">`;
const iziToastJS = `<script src="{{ asset('backend/js/iziToast.min.js') }}"></script>
    <script>
        window.toastr = {
            success: function(message) { iziToast.success({ message: message, position: 'topRight' }); },
            error: function(message) { iziToast.error({ message: message, position: 'topRight' }); },
            warning: function(message) { iziToast.warning({ message: message, position: 'topRight' }); },
            info: function(message) { iziToast.info({ message: message, position: 'topRight' }); }
        };
    </script>`;

authFiles.forEach(file => {
    if (fs.existsSync(file)) {
        let content = fs.readFileSync(file, 'utf8');
        
        // Replace toastr css
        content = content.replace(/<link href="\{\{ asset\('global\/toastr\/toastr\.min\.css'\) \}\}" rel="stylesheet">/g, iziToastCSS);
        
        // Replace toastr js
        content = content.replace(/<script src="\{\{ asset\('global\/toastr\/toastr\.min\.js'\) \}\}"><\/script>[\s\n]*<script src="\{\{ asset\('backend\/js\/modules-toastr\.js'\) \}\}"><\/script>/g, iziToastJS);
        
        fs.writeFileSync(file, content, 'utf8');
        console.log('Fixed auth toastr in ' + file);
    }
});
