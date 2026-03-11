<?php
/**
 * Migration: Expand quality and size columns in image_generations table
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Database\Database;

try {
    $db = Database::getConnection();
    
    // Alter the columns to support longer values
    $db->exec('ALTER TABLE image_generations MODIFY COLUMN size VARCHAR(50)');
    $db->exec('ALTER TABLE image_generations MODIFY COLUMN quality VARCHAR(100)');
    
    echo "✓ Successfully expanded size and quality columns\n";
    echo "  - size: VARCHAR(20) → VARCHAR(50)\n";
    echo "  - quality: VARCHAR(20) → VARCHAR(100)\n";
    
} catch (\Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
