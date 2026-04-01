<?php
$files = [
    'register.php',
    'admin/registrations.php',
    'register-ca.php',
    'register-volunteer.php',
    'events.php',
    'index.php',
    'admin/index.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // â is \xC3\xA2 which indicates an \xE2 UTF-8 byte was read as Windows-1252 and saved as UTF-8
    // ð is \xC3\xB0 which indicates an \xF0 UTF-8 byte was read as Windows-1252 and saved as UTF-8
    if (strpos($content, 'â') !== false || strpos($content, 'ð') !== false) {
        $fixed = mb_convert_encoding($content, 'Windows-1252', 'UTF-8');
        file_put_contents($file, $fixed);
        echo "Fixed double-encoded UTF-8 in $file\n";
    } else {
        echo "Skipped $file (already clean)\n";
    }
}
echo "Done.";
