<?php
// Quick cleanup and CRUD generation helper
$basePath = __DIR__;

// Remove incorrect files
$files_to_remove = [
    $basePath . '/src/Controller/YesController.php',
    $basePath . '/src/Form/Product1Type.php',
];

foreach ($files_to_remove as $file) {
    if (file_exists($file)) {
        unlink($file);
        echo "Removed: $file\n";
    }
}

// Remove templates/yes directory
$yesDir = $basePath . '/templates/yes';
if (is_dir($yesDir)) {
    array_map('unlink', glob("$yesDir/*.*"));
    rmdir($yesDir);
    echo "Removed: templates/yes\n";
}

echo "Cleanup complete. Ready for CRUD generation.\n";
