<?php
$dir = __DIR__;
$replacements = [
    '&#128075;' => '&#128075;',
    '&#2547;' => '&#2547;',
    '&mdash;' => '&mdash;',
    '&#8987;' => '<i class="fas fa-clock"></i>',
    '<i class="fas fa-check-circle"></i>' => '<i class="fas fa-check-circle"></i>',
    '<i class="fas fa-times-circle"></i>' => '<i class="fas fa-times-circle"></i>',
    '&middot;' => '&middot;',
    'NEW' => 'NEW',
    '<i class="fas fa-graduation-cap"></i>' => '<i class="fas fa-graduation-cap"></i>',
    '<i class="fas fa-hands-helping"></i>' => '<i class="fas fa-hands-helping"></i>',
    '<i class="fas fa-search"></i>' => '<i class="fas fa-search"></i>',
    '<i class="fas fa-calendar"></i>' => '<i class="fas fa-calendar"></i>',
    '<i class="fas fa-clipboard-list"></i>' => '<i class="fas fa-clipboard-list"></i>',
    '<i class="fas fa-credit-card"></i>' => '<i class="fas fa-credit-card"></i>',
    '<i class="fas fa-tags"></i>' => '<i class="fas fa-tags"></i>',
    '&times;' => '&times;',
    '<i class="fas fa-trash"></i>' => '<i class="fas fa-trash"></i>',
    '&rarr;' => '&rarr;'
];
foreach(glob($dir . '/*.php') as $file) {
    if(is_file($file)) {
        $c = file_get_contents($file);
        $oc = $c;
        foreach($replacements as $old => $new) {
            $c = str_replace($old, $new, $c);
        }
        if($c !== $oc) {
            file_put_contents($file, $c);
            echo "Fixed: " . basename($file) . "\n";
        }
    }
}
?>
