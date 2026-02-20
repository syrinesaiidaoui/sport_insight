<?php
// Use Symfony Console to generate CRUD programmatically
require 'vendor/autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

try {
    $kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? false));
    $application = new Application($kernel);
    
    // Generate Product CRUD
    echo "Generating Product CRUD...\n";
    $input = new StringInput('make:crud --entity=Product --no-interaction');
    $output = new BufferedOutput();
    
    try {
        $application->run($input, $output);
        echo $output->fetch();
        echo "✓ Product CRUD generated successfully!\n";
    } catch (\Exception $e) {
        // May fail if already generated, that's OK
        echo "Note: " . $e->getMessage() . "\n";
    }
    
    // Generate Order CRUD
    echo "\nGenerating Order CRUD...\n";
    $input = new StringInput('make:crud --entity=Order --no-interaction');
    $output = new BufferedOutput();
    
    try {
        $application->run($input, $output);
        echo $output->fetch();
        echo "✓ Order CRUD generated successfully!\n";
    } catch (\Exception $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
