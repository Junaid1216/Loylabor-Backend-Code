const fs = require('fs');
const path = require('path');
const dirs = ['c:\\xampp\\htdocs\\homeservices-12Mar2026\\app', 'c:\\xampp\\htdocs\\homeservices-12Mar2026\\resources\\views', 'c:\\xampp\\htdocs\\homeservices-12Mar2026\\Modules'];

function walk(dir) {
    let results = [];
    if (!fs.existsSync(dir)) return results;
    const list = fs.readdirSync(dir);
    list.forEach(function(file) {
        file = path.join(dir, file);
        const stat = fs.statSync(file);
        if (stat && stat.isDirectory()) { 
            results = results.concat(walk(file));
        } else {
            if (file.endsWith('.php') || file.endsWith('.js')) {
                results.push(file);
            }
        }
    });
    return results;
}

let files = [];
dirs.forEach(d => files = files.concat(walk(d)));

let count = 0;
files.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    let changed = false;

    // For created
    content = content.replace(/(['"])[A-Za-z0-9\s]+(?:created|added|saved|inserted)\s+successfully!?\1/ig, "$1Created successfully$1");
    // For updated
    content = content.replace(/(['"])[A-Za-z0-9\s]+(?:updated|changed|modified)\s+successfully!?\1/ig, "$1Updated successfully$1");
    // For deleted
    content = content.replace(/(['"])[A-Za-z0-9\s]+(?:deleted|removed|destroyed)\s+successfully!?\1/ig, "$1Deleted successfully$1");
    
    if (content !== fs.readFileSync(file, 'utf8')) {
        fs.writeFileSync(file, content, 'utf8');
        count++;
    }
});
console.log('Updated ' + count + ' files with standard messages.');
