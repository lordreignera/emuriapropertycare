<?php

use Illuminate\Support\Facades\DB;

// Check current properties
echo "=== PROPERTIES STATUS CHECK ===" . PHP_EOL . PHP_EOL;

$properties = DB::table('properties')
    ->select('id', 'property_code', 'property_name', 'status', 'user_id')
    ->get();

if ($properties->isEmpty()) {
    echo "No properties found in database." . PHP_EOL;
} else {
    echo "Found " . $properties->count() . " properties:" . PHP_EOL . PHP_EOL;
    
    foreach ($properties as $property) {
        echo "ID: {$property->id}" . PHP_EOL;
        echo "  Code: {$property->property_code}" . PHP_EOL;
        echo "  Name: {$property->property_name}" . PHP_EOL;
        echo "  Status: {$property->status}" . PHP_EOL;
        echo "  User ID: {$property->user_id}" . PHP_EOL;
        echo str_repeat('-', 50) . PHP_EOL;
    }
}

echo PHP_EOL . "=== AVAILABLE STATUS VALUES ===" . PHP_EOL;
echo "- pending_approval (needs admin approval)" . PHP_EOL;
echo "- approved (can schedule inspection)" . PHP_EOL;
echo "- rejected" . PHP_EOL;
echo "- awaiting_inspection" . PHP_EOL;

echo PHP_EOL . "To approve a property, run:" . PHP_EOL;
echo "UPDATE properties SET status = 'approved' WHERE id = [property_id];" . PHP_EOL;
