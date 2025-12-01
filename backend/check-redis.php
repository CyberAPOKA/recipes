<?php
// Script to check if Redis extension is installed
echo "Checking Redis extension...\n";

if (extension_loaded('redis')) {
    echo "✓ Redis extension is loaded\n";
    
    $redis = new Redis();
    echo "✓ Redis class is available\n";
    
    // Try to connect
    try {
        $redis->connect('redis', 6379);
        echo "✓ Successfully connected to Redis server\n";
        $redis->close();
    } catch (Exception $e) {
        echo "✗ Failed to connect to Redis: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Redis extension is NOT loaded\n";
    echo "\nInstalled PHP extensions:\n";
    $extensions = get_loaded_extensions();
    sort($extensions);
    foreach ($extensions as $ext) {
        echo "  - $ext\n";
    }
}

