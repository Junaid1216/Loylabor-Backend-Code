const fs = require('fs');
const path = require('path');

const targetDir = 'c:\\xampp\\htdocs\\homeservices-12Mar2026\\public';

const colorsToReplace = [/#6777ef/ig, /#2046da/ig, /rgb\(8,\s*124,\s*192\)/ig, /#2a9cf5/ig, /#378fff/ig];
const newColor = '#FE7701';

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(function(file) {
        file = path.join(dir, file);
        const stat = fs.statSync(file);
        if (stat && stat.isDirectory()) { 
            if (!file.includes('node_modules') && !file.includes('vendor')) {
                results = results.concat(walk(file));
            }
        } else {
            if (file.endsWith('.css') || file.endsWith('.js')) {
                results.push(file);
            }
        }
    });
    return results;
}

const files = walk(targetDir);
let changedFiles = 0;
files.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    let changed = false;
    colorsToReplace.forEach(regex => {
        if (regex.test(content)) {
            content = content.replace(regex, newColor);
            changed = true;
        }
    });
    if (changed) {
        fs.writeFileSync(file, content, 'utf8');
        console.log('Updated', file);
        changedFiles++;
    }
});
console.log('Done, updated ' + changedFiles + ' files.');
