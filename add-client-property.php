<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\User;

function printUsage(): void
{
    echo "Usage:\n";
    echo "  php add-client-property.php [--user-id=ID | --email=EMAIL] [--name=NAME] [--type=residential|commercial|mixed_use]\n\n";
    echo "Examples:\n";
    echo "  php add-client-property.php --email=client@example.com\n";
    echo "  php add-client-property.php --user-id=5 --name=\"Sunset Villa\" --type=residential\n\n";
}

$options = getopt('', [
    'user-id::',
    'email::',
    'name::',
    'type::',
    'help::',
]);

if (isset($options['help'])) {
    printUsage();
    exit(0);
}

$propertyType = (string) ($options['type'] ?? 'residential');
$allowedTypes = ['residential', 'commercial', 'mixed_use'];
if (!in_array($propertyType, $allowedTypes, true)) {
    fwrite(STDERR, "Error: Invalid --type value. Allowed: residential, commercial, mixed_use\n\n");
    printUsage();
    exit(1);
}

$user = null;

if (!empty($options['user-id'])) {
    $user = User::find((int) $options['user-id']);
}

if (!$user && !empty($options['email'])) {
    $user = User::where('email', (string) $options['email'])->first();
}

if (!$user) {
    // Fallback to first user with a Client role, then first user in table.
    $user = User::whereHas('roles', function ($q) {
        $q->where('name', 'Client');
    })->first();

    if (!$user) {
        $user = User::first();
    }
}

if (!$user) {
    fwrite(STDERR, "Error: No users found. Create a user first, then rerun.\n");
    exit(1);
}

$propertyName = (string) ($options['name'] ?? ('Client Property ' . now()->format('Y-m-d H:i:s')));
$propertyCode = Property::generatePropertyCode('CLI');

$squareInterior = 1800.0;
$squareGreen = 500.0;
$squarePaved = 200.0;
$squareExtra = 0.0;
$totalSquare = $squareInterior + $squareGreen + $squarePaved + $squareExtra;

$data = [
    'user_id' => $user->id,
    'property_code' => $propertyCode,
    'property_brand' => 'CLI',
    'property_name' => $propertyName,
    'property_address' => '123 Dashboard Street',
    'city' => 'Kampala',
    'province' => 'Central',
    'postal_code' => '00000',
    'country' => 'Uganda',
    'type' => $propertyType,
    'property_subtype' => $propertyType === 'residential' ? 'house' : null,
    'year_built' => 2018,

    'owner_first_name' => $user->name ?? 'Client Owner',
    'owner_phone' => '+256700000000',
    'owner_email' => $user->email,

    'occupied_by' => 'owner',
    'has_pets' => false,
    'has_kids' => false,
    'has_tenants' => false,
    'number_of_units' => 1,

    'square_footage_interior' => $squareInterior,
    'square_footage_green' => $squareGreen,
    'square_footage_paved' => $squarePaved,
    'square_footage_extra' => $squareExtra,
    'total_square_footage' => $totalSquare,

    'personality' => 'calm',
    'personality_notes' => 'Created from CLI script for dashboard testing.',
    'known_problems' => 'Minor wall cracks in living room, Roof leak near gutter',

    'status' => 'pending_approval',
];

try {
    $property = Property::create($data);

    echo "Property created successfully.\n";
    echo "- Client: {$user->name} ({$user->email})\n";
    echo "- Property ID: {$property->id}\n";
    echo "- Property Code: {$property->property_code}\n";
    echo "- Property Name: {$property->property_name}\n\n";

    echo "Open in dashboard:\n";
    echo "- http://localhost/client/properties/{$property->id}\n";
    echo "- http://localhost/client/properties\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Failed to create property: {$e->getMessage()}\n");
    exit(1);
}
