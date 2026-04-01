<?php
// Read the broken file
$broken = file_get_contents('register.php');
// Attempt inversion of double-encoded UTF-8
$fixed = mb_convert_encoding($broken, 'Windows-1252', 'UTF-8');
// Save
file_put_contents('register_fixed.php', $fixed);
echo "Done.";
