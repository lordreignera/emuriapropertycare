<?php

namespace Database\Seeders;

use App\Models\InspectionSystem;
use App\Models\InspectionSubsystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InspectionSystemsSeeder extends Seeder
{
    public function run(): void
    {
        $systems = [
            'Interior – Walls, Trim & Paint' => ['Walls', 'Trim', 'Paint', 'Drywall', 'Ceiling'],
            'Windows & Trim' => ['Window Frame', 'Glass', 'Sill', 'Trim', 'Weather Seals'],
            'Doors & Hardware' => ['Doors', 'Hinges', 'Locks', 'Handles', 'Door Seals'],
            'Floors' => ['Tile', 'Hardwood', 'Laminate', 'Carpet', 'Subfloor'],
            'Bathrooms' => ['Toilet', 'Vanity', 'Shower', 'Tub', 'Bathroom Ventilation'],
            'Kitchen' => ['Cabinets', 'Countertops', 'Sink', 'Fixtures', 'Backsplash'],
            'Baseboards / Trim' => ['Baseboards', 'Corners', 'Casing', 'Transitions'],
            'Caulking / Water Control' => ['Caulking', 'Expansion Joints', 'Moisture Seals'],
            'Crown Moulding' => ['Moulding', 'Corner Joints', 'Paint Finish'],
            'Electrical' => ['Lighting', 'Outlets', 'Switches', 'Panel', 'Wiring'],
            'Plumbing' => ['Supply Lines', 'Drain Lines', 'Fixtures', 'Venting'],
            'Ventilation' => ['Exhaust Fans', 'Ducts', 'Air Vents', 'Filters'],
            'Exterior' => ['Siding', 'Fascia', 'Soffit', 'Exterior Paint'],
            'Roof & Drainage' => ['Roof Covering', 'Flashing', 'Gutters', 'Downspouts'],
            'Deck & Stairs' => ['Deck Boards', 'Stairs', 'Railings', 'Anchors'],
            'Landscaping / Pruning' => ['Trees', 'Shrubs', 'Perimeter', 'Drainage Grade'],
            'Accessibility' => ['Entry Slope', 'Handrails', 'Thresholds', 'Ramps'],
            'Garage' => ['Floor', 'Walls', 'Storage', 'Door System'],
            'Foundation / Sump' => ['Foundation Walls', 'Cracks', 'Sump Pump', 'Drainage'],
            'Improvement Projects (Upsell / Planning)' => ['Basement Finishing', 'Kitchen Renovation', 'Bathroom Remodel', 'Structural Modifications'],
        ];

        $systemOrder = 1;

        foreach ($systems as $systemName => $subsystems) {
            $system = InspectionSystem::updateOrCreate(
                ['slug' => Str::slug($systemName)],
                [
                    'name' => $systemName,
                    'description' => 'Inspection system for ' . $systemName,
                    'sort_order' => $systemOrder,
                    'is_active' => true,
                ]
            );

            foreach (array_values($subsystems) as $subOrder => $subName) {
                $slug = Str::slug($systemName . ' ' . $subName);

                InspectionSubsystem::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'system_id' => $system->id,
                        'name' => $subName,
                        'description' => $subName . ' checks within ' . $systemName,
                        'sort_order' => $subOrder + 1,
                        'is_active' => true,
                    ]
                );
            }

            $systemOrder++;
        }
    }
}
