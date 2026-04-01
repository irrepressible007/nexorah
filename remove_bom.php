<?php
// Script to recursively strip UTF-8 BOM from all .php files
// Place this in your public_html root and run it from the browser.
echo "<h1>Checking for UTF-8 BOM...</h1><pre>";

function removeBOM($dir) {
    if (!is_dir($dir)) return;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            removeBOM($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
                echo "Found BOM in: " . $path . "\n";
                // Strip the exact 3 bytes
                $new_content = substr($content, 3);
                file_put_contents($path, $new_content);
                echo "-> Stripped BOM from: " . $path . "\n\n";
            }
        }
    }
}

removeBOM(__DIR__);
echo "<b>Process complete. All .php files checked.</b></pre>";
?>
