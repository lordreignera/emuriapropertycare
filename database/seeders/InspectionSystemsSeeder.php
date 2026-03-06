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
            $systemRecommendations = $this->getSystemRecommendations($systemName);

            $system = InspectionSystem::updateOrCreate(
                ['slug' => Str::slug($systemName)],
                [
                    'name' => $systemName,
                    'description' => 'Inspection system for ' . $systemName,
                    'recommended_actions' => $systemRecommendations,
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
                        'recommended_actions' => $this->getSubsystemRecommendations($systemName, $subName, $systemRecommendations),
                        'sort_order' => $subOrder + 1,
                        'is_active' => true,
                    ]
                );
            }

            $systemOrder++;
        }
    }

    private function getSystemRecommendations(string $systemName): array
    {
        $map = [
            'Interior – Walls, Trim & Paint' => ['Patch and repaint', 'Install wall bumpers', 'Furniture pads', 'Fill and paint', 'Monitor 6 months', 'Manage humidity', 'Touch-up paint', 'Add wall guard', 'Shoe/coat system', 'Flexible filler', 'Moisture movement review', 'Inspect subfloor', 'Moisture paint', 'Kick guard', 'Improve ventilation', 'Repair mould-resistant', 'Install dehumidifier', 'Seal feather seam', 'Annual monitoring', 'Humidity control', 'Patch/paint', 'Ventilation upgrade', 'Insulation check'],
            'Windows & Trim' => ['Fill and paint', 'Install blind bumpers', 'Handle with care signage', 'Sand and seal', 'Check flashing', 'Add dehumidifier', 'Reseal frame joints', 'Replace weather seals', 'Install drip edge', 'Repair trim and repaint'],
            'Doors & Hardware' => ['Patch and paint', 'Install door stop', 'Wall protector', 'Adjust strike plate', 'Lubricate latch', 'Tighten hinges', 'Plane edge', 'Improve ventilation', 'Lubricate', 'Adjust weatherstrip', 'Replace lockset', 'Add weatherstrip', 'Check airflow', 'Tighten screws', 'Rust protection', 'Install door sweep'],
            'Floors' => ['Deep clean', 'Add mat', 'Cleaning schedule', 'Spot clean', 'Dry immediately', 'Shoe tray', 'Seal cracks', 'Monitor annually', 'Humidity balance', 'Document level', 'Structural review', 'Level adjustment', 'Measure and map', 'Structure consult', 'Correct slope', 'Patch/refinish', 'Pads under furniture', 'Add rug', 'Replace carpet', 'Chair floor sliders', 'Stain-resistant pad', 'Replace flooring', 'Dehumidifier', 'Vapor barrier', 'Seal crack', 'Add runner', 'Foundation review', 'Foundation assessment', 'Moisture control'],
            'Bathrooms' => ['Plane door', 'Vent fan timer', 'Dehumidify', 'Clear clog', 'Enzyme treatment', 'Education signage', 'Replace LED', 'Vapor-rated fixture', 'Fan timer', 'Clean and seal', 'Back splash strip', 'Drip tray', 'Sand and seal', 'Silicone bead', 'Ventilation timer'],
            'Kitchen' => ['Tighten/replace hinge', 'Add bump stops', 'Alignment adjust', 'Degrease and polish', 'Seal finish', 'Install splash guard', 'Reseal wet areas', 'Repair cabinet fittings', 'Replace damaged fixtures'],
            'Baseboards / Trim' => ['Replace board', 'Fix moisture', 'Improve airflow', 'Re-secure trim', 'Caulk top', 'Seal edges', 'Fill/caulk', 'Monitor humidity', 'Inspect for settling', 'Secure trim', 'Clean fins', 'Annual maintenance', 'Seal gap', 'Install deterrent traps', 'Food-seal bins', 'Prime and repaint'],
            'Caulking / Water Control' => ['Re-caulk', 'Disinfect', 'Ventilate', 'Remove and reseal', 'Inspect joints', 'Moisture check', 'Seal expansion gaps', 'Inspect water ingress paths'],
            'Crown Moulding' => ['High-temp caulk', 'Check airflow', 'Repaint', 'Fill and smooth joints', 'Treat moisture spots'],
            'Electrical' => ['Replace fixture', 'Check wiring', 'LED upgrade', 'Replace LED', 'Vapor seal', 'Fan timer', 'Secure electrical connections', 'Safety compliance check'],
            'Plumbing' => ['Clear line', 'Enzyme treatment', 'Check venting', 'Fix leak points', 'Pressure test lines'],
            'Ventilation' => ['Clear vent', 'Clean grille', 'Airflow check', 'Clean vent', 'Vacuum duct start', 'Replace filters', 'Balance airflow', 'Repair fan unit'],
            'Exterior' => ['Replace fascia', 'Prime and paint', 'Fix gutter slope', 'Clean vents', 'Screen install', 'Improve airflow', 'Soft wash', 'Touch-up paint', 'Seal gaps', 'Repair external damage'],
            'Roof & Drainage' => ['Clean gutters', 'Adjust slope', 'Add guards', 'Extend downspouts', 'Splash pads', 'Soil regrade', 'Repair flashing', 'Seal roof penetrations'],
            'Deck & Stairs' => ['Tighten anchors', 'Replace bolts', 'Add support', 'Replace boards', 'Sand and seal deck', 'Stain finish', 'Anti-slip treatment'],
            'Landscaping / Pruning' => ['Trim shrubs', 'Maintain 12" clearance', 'Add mulch barrier', 'Prune branches', 'Remove dead limbs', 'Annual trimming', 'Regrade drainage path'],
            'Accessibility' => ['Add ramp', 'Handrail install', 'Threshold slope', 'Improve walkway safety', 'Enhance transition safety'],
            'Garage' => ['Grind concrete', 'Fill cracks', 'Moisture check', 'Apply primer', 'Epoxy coat', 'Poly top coat', 'Install shelving', 'Wall anchors', 'Tool rails', 'Door hardware service'],
            'Foundation / Sump' => ['Seal crack', 'Monitor annually', 'Improve drainage', 'Test pump', 'Clean pit', 'Add backup battery', 'Apply waterproof barrier', 'Inspect settlement movement'],
            'Improvement Projects (Upsell / Planning)' => ['Design plan', 'Moisture barrier', 'Install egress', 'Structural engineer review', 'LVL beam', 'Re-route utilities', 'Replace cabinets/fixtures', 'Install walk-in shower', 'Waterproof membrane', 'Provide budget estimate', 'Plan phased upgrade'],
        ];

        return $map[$systemName] ?? ['Inspect and diagnose', 'Repair damaged area', 'Preventive maintenance', 'Schedule follow-up'];
    }

    private function getSubsystemRecommendations(string $systemName, string $subsystemName, array $fallback): array
    {
        $systemSpecificMap = [
            'Interior – Walls, Trim & Paint::Walls' => ['Patch and repaint', 'Install wall bumpers', 'Furniture pads', 'Seal wall cracks', 'Monitor for recurring cracks'],
            'Interior – Walls, Trim & Paint::Trim' => ['Fill and paint', 'Manage humidity', 'Touch-up paint', 'Add wall guard'],
            'Windows & Trim::Trim' => ['Repair trim and repaint', 'Fill and paint', 'Seal trim joints', 'Install corner guard'],
            'Garage::Walls' => ['Install wall anchors', 'Tool rails', 'Seal wall surface'],
        ];

        $systemScopedKey = $systemName . '::' . $subsystemName;
        if (array_key_exists($systemScopedKey, $systemSpecificMap)) {
            return $systemSpecificMap[$systemScopedKey];
        }

        $subsystemMap = [
            'Walls' => ['Patch and repaint', 'Install wall bumpers', 'Furniture pads', 'Seal wall cracks', 'Monitor for recurring cracks'],
            'Trim' => ['Fill and paint', 'Manage humidity', 'Touch-up paint', 'Add wall guard'],
            'Paint' => ['Scrape and repaint', 'Apply primer coat', 'Use moisture-resistant paint', 'Touch-up paint', 'Moisture paint'],
            'Drywall' => ['Repair damaged drywall', 'Re-tape joints', 'Skim coat and finish', 'Flexible filler'],
            'Ceiling' => ['Patch/paint', 'Ventilation upgrade', 'Insulation check'],
            'Window Frame' => ['Reseal window frame', 'Treat frame rot', 'Repaint and weatherproof', 'Check flashing'],
            'Glass' => ['Handle with care signage', 'Install blind bumpers', 'Sand and seal'],
            'Sill' => ['Fill and paint', 'Moisture check', 'Seal sill edges'],
            'Weather Seals' => ['Replace weather stripping', 'Seal draft gaps', 'Inspect seasonal wear'],
            'Doors' => ['Adjust door alignment', 'Plane edge', 'Install door stop', 'Install door sweep'],
            'Hinges' => ['Lubricate hinges', 'Tighten screws', 'Replace worn hinge set'],
            'Locks' => ['Adjust lock mechanism', 'Replace lockset', 'Test secure engagement'],
            'Handles' => ['Secure handle set', 'Replace worn handles', 'Rust protection'],
            'Door Seals' => ['Replace weather seals', 'Add draft blocker', 'Check airflow'],
            'Tile' => ['Regrout tiles', 'Replace cracked tiles', 'Apply sealant', 'Spot clean'],
            'Hardwood' => ['Sand and seal', 'Refinish worn area', 'Humidity control'],
            'Laminate' => ['Seal edges', 'Replace warped planks', 'Dry immediately'],
            'Carpet' => ['Deep clean carpet', 'Spot treat stains', 'Replace worn sections', 'Install stain-resistant pad'],
            'Subfloor' => ['Document level', 'Structural review', 'Level adjustment'],
            'Toilet' => ['Clear clog', 'Enzyme treatment', 'Replace wax ring', 'Secure toilet mounting'],
            'Vanity' => ['Seal vanity edges', 'Fix door alignment', 'Replace damaged panel'],
            'Shower' => ['Reseal shower joints', 'Replace damaged grout', 'Repair shower fixture'],
            'Tub' => ['Re-caulk around tub', 'Install drip tray', 'Repair chip/crack finish'],
            'Bathroom Ventilation' => ['Install fan timer', 'Improve airflow', 'Add dehumidifier'],
            'Cabinets' => ['Tighten/replace hinge', 'Alignment adjust', 'Add bump stops'],
            'Countertops' => ['Reseal countertop seams', 'Repair chipped edges', 'Add splash guard'],
            'Sink' => ['Seal sink edges', 'Fix drain leak', 'Replace damaged trap'],
            'Fixtures' => ['Replace worn fixtures', 'LED upgrade', 'Vapor-rated fixture'],
            'Backsplash' => ['Regrout backsplash', 'Seal joints', 'Replace loose tile'],
            'Baseboards' => ['Replace board', 'Fix moisture', 'Improve airflow'],
            'Corners' => ['Fill/caulk corner', 'Monitor humidity', 'Inspect settling'],
            'Casing' => ['Re-secure trim', 'Caulk top', 'Seal edges'],
            'Transitions' => ['Secure transition strip', 'Level transition edge', 'Replace damaged profile'],
            'Caulking' => ['Re-caulk', 'Disinfect', 'Ventilate'],
            'Expansion Joints' => ['Remove and reseal', 'Inspect joints', 'Moisture check'],
            'Moisture Seals' => ['Seal moisture points', 'Check vapor barrier', 'Apply mildew-resistant seal'],
            'Moulding' => ['Refix loose moulding', 'Fill and smooth joints', 'Repaint'],
            'Corner Joints' => ['Caulk corners', 'Seal trim gap', 'Monitor movement'],
            'Paint Finish' => ['Touch-up finish', 'Prime then repaint', 'Moisture-resistant top coat'],
            'Lighting' => ['Replace fixture', 'LED upgrade', 'Check wiring'],
            'Outlets' => ['Replace faulty outlet', 'Tighten terminal connections', 'Install protective cover'],
            'Switches' => ['Replace switch', 'Secure plate', 'Inspect heat marks'],
            'Panel' => ['Panel inspection', 'Label circuits', 'Tighten loose terminals'],
            'Wiring' => ['Inspect insulation wear', 'Replace damaged wiring', 'Confirm code compliance'],
            'Supply Lines' => ['Inspect for leaks', 'Replace worn connector', 'Pressure check'],
            'Drain Lines' => ['Clear drain obstruction', 'Jet line if needed', 'Inspect with camera scope'],
            'Venting' => ['Check vent blockage', 'Clear vent path', 'Improve venting'],
            'Exhaust Fans' => ['Clean fan housing', 'Replace weak motor', 'Improve extraction rate'],
            'Ducts' => ['Vacuum ducts', 'Seal duct joints', 'Inspect airflow drop'],
            'Air Vents' => ['Clean vents', 'Straighten louvers', 'Balance airflow'],
            'Filters' => ['Replace filters', 'Set replacement schedule', 'Upgrade filter type'],
            'Siding' => ['Repair cracked siding', 'Seal penetrations', 'Repaint exterior panel'],
            'Fascia' => ['Replace fascia', 'Prime and paint', 'Check roof edge runoff'],
            'Soffit' => ['Clean soffit vents', 'Screen install', 'Improve airflow'],
            'Exterior Paint' => ['Soft wash', 'Touch-up paint', 'Seal exposed gaps'],
            'Roof Covering' => ['Replace damaged shingles', 'Patch roof area', 'Inspect wear pattern'],
            'Flashing' => ['Repair flashing', 'Seal roof penetrations', 'Inspect joint line'],
            'Gutters' => ['Remove debris', 'Realign gutter slope', 'Seal leaking joints', 'Add guards'],
            'Downspouts' => ['Extend downspout', 'Install splash pad', 'Regrade discharge zone'],
            'Deck Boards' => ['Replace warped board', 'Sand rough edges', 'Apply weatherproof coating'],
            'Stairs' => ['Secure stair treads', 'Anti-slip strip', 'Replace damaged riser'],
            'Railings' => ['Tighten anchors', 'Replace bolts', 'Add support'],
            'Anchors' => ['Inspect anchor points', 'Retighten hardware', 'Upgrade anchor set'],
            'Trees' => ['Prune branches', 'Remove dead limbs', 'Annual trimming'],
            'Shrubs' => ['Trim shrubs', 'Maintain clearance', 'Mulch barrier'],
            'Perimeter' => ['Regrade perimeter', 'Improve runoff', 'Drainage correction'],
            'Drainage Grade' => ['Adjust slope away from structure', 'Add swale', 'Soil regrade'],
            'Entry Slope' => ['Add ramp', 'Adjust slope', 'Apply anti-slip finish'],
            'Handrails' => ['Install handrail', 'Secure mounting', 'Extend rail where required'],
            'Thresholds' => ['Threshold slope adjustment', 'Install transition strip', 'Reduce trip edge'],
            'Ramps' => ['Ramp compliance check', 'Add side guard', 'Improve traction surface'],
            'Floor' => ['Grind concrete', 'Fill cracks', 'Apply epoxy'],
            'Walls' => ['Install wall anchors', 'Tool rails', 'Seal wall surface'],
            'Storage' => ['Install shelving', 'Anchor heavy storage', 'Improve safety layout'],
            'Door System' => ['Adjust garage door tracks', 'Lubricate rollers', 'Check auto-reverse safety'],
            'Foundation Walls' => ['Seal wall cracks', 'Apply waterproof barrier', 'Monitor movement'],
            'Cracks' => ['Seal crack', 'Monitor annually', 'Structural review if widening'],
            'Sump Pump' => ['Test pump operation', 'Clean pit and intake', 'Install backup battery'],
            'Drainage' => ['Improve perimeter drainage', 'Install drain extension', 'Moisture monitoring'],
            'Basement Finishing' => ['Design plan', 'Moisture barrier', 'Install egress'],
            'Kitchen Renovation' => ['Cabinet layout planning', 'Costed upgrade proposal', 'Utility reroute review'],
            'Bathroom Remodel' => ['Fixture upgrade plan', 'Waterproofing scope', 'Accessibility enhancement options'],
            'Structural Modifications' => ['Structural engineer review', 'LVL beam', 'Permit and sequencing plan'],
        ];

        return $subsystemMap[$subsystemName] ?? $fallback;
    }
}
