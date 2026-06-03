const fs = require('fs');

const filesToFix = [
    'c:\\xampp\\htdocs\\homeservices-12Mar2026\\resources\\views\\staff\\dashboard.blade.php',
    'c:\\xampp\\htdocs\\homeservices-12Mar2026\\resources\\views\\staff\\shop\\index.blade.php',
    'c:\\xampp\\htdocs\\homeservices-12Mar2026\\Modules\\GlobalSetting\\resources\\views\\notifications\\index.blade.php'
];

filesToFix.forEach(file => {
    if (fs.existsSync(file)) {
        let content = fs.readFileSync(file, 'utf8');
        
        // Regex to match Swal.fire success blocks
        const swalRegex = /Swal\.fire\(\{\s*icon:\s*['"]success['"],\s*title:\s*['"][^'"]+['"],\s*text:\s*([^,]+),\s*(?:confirmButtonColor:\s*['"][^'"]+['"],\s*)?(?:confirmButtonText:\s*['"][^'"]+['"],\s*)?(?:timer:\s*\d+,\s*)?(?:showConfirmButton:\s*(?:true|false)\s*)?\}\);/gs;
        
        content = content.replace(swalRegex, "toastr.success($1);");
        
        fs.writeFileSync(file, content, 'utf8');
        console.log('Fixed Swal in ' + file);
    }
});
