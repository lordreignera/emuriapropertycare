<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Property;
use App\Models\User;

echo "Testing Property Creation...\n\n";

// Get a client user (assuming user ID 1 exists)
$user = User::find(1);

if (!$user) {
    echo "Error: No user found. Please create a user first.\n";
    exit(1);
}

echo "Using user: {$user->name} (ID: {$user->id})\n\n";

// Test data
$propertyData = [
    'user_id' => $user->id,
    'property_code' => Property::generatePropertyCode('TEST'),
    'property_brand' => 'Test Brand',
    
    // Required owner fields
    'owner_first_name' => 'John',
    'owner_phone' => '+1-555-1234',
    'owner_email' => 'john@test.com',
    
    // Property details
    'property_name' => 'Test Property ' . date('Y-m-d H:i:s'),
    'property_address' => '123 Test Street',
    'city' => 'Test City',
    'province' => 'Test Province',
    'postal_code' => '12345',
    'country' => 'United States',
    'type' => 'residential',
    'year_built' => 2020,
    
    // Occupancy
    'occupied_by' => 'tenants', // Testing with 'tenants' (plural)
    'has_pets' => true,
    'has_kids' => false,
    'has_tenants' => true,
    'number_of_units' => 1,
    
    'status' => 'pending_approval',
];

echo "Attempting to create property with data:\n";
echo "- Property Name: {$propertyData['property_name']}\n";
echo "- Property Type: {$propertyData['type']}\n";
echo "- Occupied By: {$propertyData['occupied_by']}\n";
echo "- Country: {$propertyData['country']}\n\n";

try {
    $property = Property::create($propertyData);
    
    echo "✓ SUCCESS! Property created successfully!\n\n";
    echo "Property Details:\n";
    echo "- ID: {$property->id}\n";
    echo "- Property Code: {$property->property_code}\n";
    echo "- Property Name: {$property->property_name}\n";
    echo "- Occupied By: {$property->occupied_by}\n";
    echo "- Status: {$property->status}\n";
    echo "- Created At: {$property->created_at}\n\n";
    
    echo "Testing all occupied_by enum values...\n\n";
    
    $enumValues = ['owner', 'family', 'tenants', 'mixed'];
    
    foreach ($enumValues as $value) {
        try {
            $testProperty = Property::create([
                'user_id' => $user->id,
                'property_code' => Property::generatePropertyCode('TEST'),
                'property_brand' => 'Test Brand',
                'owner_first_name' => 'John',
                'owner_phone' => '+1-555-1234',
                'owner_email' => 'john@test.com',
                'property_name' => "Test Property - {$value}",
                'property_address' => '123 Test Street',
                'city' => 'Test City',
                'province' => 'Test Province',
                'postal_code' => '12345',
                'country' => 'United States',
                'type' => 'residential',
                'year_built' => 2020,
                'occupied_by' => $value,
                'status' => 'pending_approval',
            ]);
            
            echo "✓ '{$value}': SUCCESS (ID: {$testProperty->id}, Code: {$testProperty->property_code})\n";
            
            // Clean up test property
            $testProperty->delete();
            
        } catch (\Exception $e) {
            echo "✗ '{$value}': FAILED - {$e->getMessage()}\n";
        }
    }
    
    echo "\n=== All Tests Completed ===\n";
    
} catch (\Illuminate\Database\QueryException $e) {
    echo "✗ DATABASE ERROR!\n\n";
    echo "Error Code: {$e->getCode()}\n";
    echo "Error Message: {$e->getMessage()}\n\n";
    
    if (strpos($e->getMessage(), 'occupied_by') !== false) {
        echo "This is the occupied_by enum validation error!\n";
        echo "The database expects: 'owner', 'family', 'tenants', or 'mixed'\n";
    }
    
    exit(1);
    
} catch (\Exception $e) {
    echo "✗ ERROR!\n\n";
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}\n";
    echo "Line: {$e->getLine()}\n\n";
    
    exit(1);
}
