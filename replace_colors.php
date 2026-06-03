<?php
$directory = 'c:\xampp\htdocs\homeservices-12Mar2026';
$colors_to_replace = ['/#6777ef/i', '/#2046da/i', '/rgb\(8,\s*124,\s*192\)/i', '/#2a9cf5/i'];
$new_color = '#FE7701';

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (strpos($path, 'node_modules') !== false || strpos($path, 'vendor') !== false || strpos($path, '.git') !== false) {
        continue;
    }
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (in_array($ext, ['css', 'scss', 'js', 'php'])) {
        $content = file_get_contents($path);
        $changed = false;
        foreach ($colors_to_replace as $color) {
            if (preg_match($color, $content)) {
                $content = preg_replace($color, $new_color, $content);
                $changed = true;
            }
        }
        if ($changed) {
            file_put_contents($path, $content);
            echo "Updated $path\n";
        }
    }
}
echo "Done\n";
