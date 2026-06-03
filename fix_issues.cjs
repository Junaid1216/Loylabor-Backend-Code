const fs = require('fs');
const path = require('path');

// Fix 1: Add hover style to dev.css
const cssFile = 'c:\\xampp\\htdocs\\homeservices-12Mar2026\\public\\backend\\css\\dev.css';
if (fs.existsSync(cssFile)) {
    let cssContent = fs.readFileSync(cssFile, 'utf8');
    if (!cssContent.includes('.btn-primary:hover')) {
        cssContent += `
.btn-primary:hover, .btn-primary:focus, .btn-primary:active {
    background-color: #ff6e00 !important;
    border-color: #ff6e00 !important;
}
`;
        fs.writeFileSync(cssFile, cssContent, 'utf8');
        console.log('Fixed dev.css button hover');
    }
}

// Fix 2: Inject toastr proxy in javascripts.blade.php
const jsFiles = [
    'c:\\xampp\\htdocs\\homeservices-12Mar2026\\resources\\views\\admin\\partials\\javascripts.blade.php',
    'c:\\xampp\\htdocs\\homeservices-12Mar2026\\resources\\views\\staff\\partials\\javascripts.blade.php',
    'c:\\xampp\\htdocs\\homeservices-12Mar2026\\resources\\views\\seller\\layouts\\partials\\js.blade.php'
];

const proxyScript = `
<script>
    // Force toastr to use iziToast globally for consistent UI
    window.toastr = {
        success: function(message) {
            iziToast.success({ message: message, position: 'topRight' });
        },
        error: function(message) {
            iziToast.error({ message: message, position: 'topRight' });
        },
        warning: function(message) {
            iziToast.warning({ message: message, position: 'topRight' });
        },
        info: function(message) {
            iziToast.info({ message: message, position: 'topRight' });
        }
    };
</script>
`;

jsFiles.forEach(file => {
    if (fs.existsSync(file)) {
        let content = fs.readFileSync(file, 'utf8');
        if (!content.includes('window.toastr = {')) {
            content += proxyScript;
            fs.writeFileSync(file, content, 'utf8');
            console.log('Injected toastr proxy in ' + file);
        }
    }
});
