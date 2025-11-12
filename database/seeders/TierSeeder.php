<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Tier 1',
                'slug' => 'basic-care',
                'icon' => '🌿',
                'experience' => 'Preventive Essentials & Peace-of-Mind Protection',
                'description' => 'Essential home care for new homes, low-use properties, and peace-of-mind starters',
                'features' => json_encode([
                    'Annual whole-home inspection',
                    'Digital home health report',
                    'Safety & compliance check (smoke/CO, GFCI, shut-off locations)',
                    'Filter replacements (HVAC/hood/bath fan — client provided)',
                    'Battery replacement for smoke/CO devices',
                    'Minor lubrication & tightening (hinges, door handles, faucets)',
                    'Minor caulking touch-ups (bathroom/kitchen spots)',
                    'Spot painting/touch-ups (up to 30 min)',
                    'Basic cleaning checklist (vents, drains, door tracks)',
                    'Seasonal gutter check (1×) - does not include cleaning',
                    'Basic exterior visual check (roofline, grading, drainage)',
                    'Hotline access (business hours)',
                    'No emergency call-outs included',
                ]),
                'monthly_price' => 199.00,
                'annual_price' => 1990.00, // Save ~17% annually
                'coverage_limit' => 500.00,
                'designed_for' => 'New homes, low-use properties, peace-of-mind starters',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Tier 2',
                'slug' => 'essential-care',
                'icon' => '🧰',
                'experience' => 'Cosmetic, Comfort & Minor Wear Restoration',
                'description' => 'All Tier 1 services plus cosmetic repairs and minor wear restoration',
                'features' => json_encode([
                    'All Tier 1 services PLUS:',
                    'Wall repairs: dents, scrapes, nail holes',
                    'Trim & baseboard repairs',
                    'Door adjustment & minor hardware repair',
                    'Minor drywall patching',
                    'Paint touch-ups (spot areas)',
                    'Caulking refresh (kitchen, bath, windows)',
                    'Minor grout repairs',
                    'Light fixture troubleshooting (bulbs/ballasts)',
                    'Cabinet hinge/handle tightening & alignment',
                    'Faucet aerator cleaning & low-flow correction',
                    'Drain clearing (non-severe)',
                    'Weather-strip & door seal repair',
                    'One small handyman task per visit (hour)',
                    'Gutter cleaning (1×/year)',
                    'Pressure-wash one small area (e.g., steps/patio)',
                    'Yard tidy (light garden maintenance & pruning small shrubs 1×/year)',
                ]),
                'monthly_price' => 349.00,
                'annual_price' => 3490.00,
                'coverage_limit' => 1500.00,
                'designed_for' => 'Busy homeowners & light-wear properties',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Tier 3',
                'slug' => 'enhanced',
                'icon' => '⚙️',
                'experience' => 'Systems, Surfaces & Appliance Support',
                'description' => 'All Tier 1 & 2 services plus appliance maintenance and system support',
                'features' => json_encode([
                    'All Tier 1 & 2 services PLUS:',
                    'Appliance maintenance support (non-warranty):',
                    '• Dishwasher filter clear',
                    '• Washer hose check',
                    '• Dryer vent cleaning (1×/year)',
                    '• Fridge coil clean',
                    'Light plumbing fixes:',
                    '• Slow drains',
                    '• Toilet flapper & seals',
                    '• Faucet cartridge replacements',
                    'Fixture replacements (lights, taps, showerheads, handles)',
                    'Minor flooring fixes (replace single boards, level transitions)',
                    'Tile/grout repair & refresh',
                    'Minor leak sealing & moisture barrier touch-ups',
                    'Window & door seal repair',
                    'Basic pest-proofing (mice gaps & entry seals)',
                    'Repair 2 small household issues per visit',
                    'Gutter cleaning twice yearly',
                    'Pressure-wash driveway/walkway 1×/year',
                    'Garden & lawn maintenance monthly seasonal',
                    'Tree/bush pruning (small trees only)',
                ]),
                'monthly_price' => 549.00,
                'annual_price' => 5490.00,
                'coverage_limit' => 3000.00,
                'designed_for' => 'Families, active homes, rental homes, older homes',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Tier 4',
                'slug' => 'premium-protection',
                'icon' => '🏛',
                'experience' => 'Structural & Mechanical Support',
                'description' => 'All Tier 1-3 services plus structural repairs and mechanical support',
                'features' => json_encode([
                    'All Tier 1–3 services PLUS:',
                    'Minor drywall rebuilds, deeper cracks & settlement repair',
                    'Subfloor levelling (small sections)',
                    'Flooring repairs & partial replacements',
                    'Water intrusion assessment & preventative sealing',
                    'Exhaust fan upgrade/replacement',
                    'Humidity & ventilation balancing',
                    'Electrical support:',
                    '• Switch & outlet replacement',
                    '• Panel labeling & reporting',
                    'HVAC support:',
                    '• Filter program',
                    '• Thermostat programming',
                    '• Furnace performance check (non-diagnostic)',
                    'Frequent handyman tasks (up to 2 hrs per visit)',
                    'Gutter cleaning 2×/year + emergency clearance',
                    'Pressure-wash exterior surfaces annually',
                    'Garden & pruning program — quarterly',
                    'Snow/ice walkway salting (if applicable region)',
                ]),
                'monthly_price' => 849.00,
                'annual_price' => 8490.00,
                'coverage_limit' => 6000.00,
                'designed_for' => 'High-value homes, older homes, estate rentals, busy landlords',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Tier 5',
                'slug' => 'elite-estate-care',
                'icon' => '👑',
                'experience' => 'White-Glove Home Stewardship',
                'description' => 'All services in Tiers 1-4 plus white-glove concierge and renovation support',
                'features' => json_encode([
                    'ALL services in Tiers 1–4 PLUS:',
                    'Full home concierge',
                    'Priority scheduling (48-hr guaranteed)',
                    'Emergency call-out coverage',
                    'Annual deep home reset',
                    '• Full deep clean interior',
                    '• Exterior washing (house + walks + patio)',
                    'Full garden + landscape maintenance plan',
                    'Tree pruning & storm response',
                    'Water mitigation/response visits',
                    'Renovation consulting + planning',
                    'Project management support',
                    'Renovation Credits:',
                    '• Floor replacement credit',
                    '• Bathroom refresh credit',
                    '• Wall/room reconfiguration consultation',
                    'Annual maintenance plan & capital forecast',
                    'Preventive infrastructure scan (thermal + moisture check)',
                    'Home Upgrade Concierge:',
                    '• Basement development feasibility check',
                    '• Room addition consult',
                    '• Kitchen redesign planning',
                    '• Permit + plan support',
                    '• Preferred contractor network access',
                    'Luxury Conveniences:',
                    '• Annual handyman bucket list (8 hours)',
                    '• Seasonal prep (fall/spring full service)',
                    '• Home readiness check before travel',
                ]),
                'monthly_price' => 1499.00,
                'annual_price' => 14990.00,
                'coverage_limit' => 15000.00,
                'designed_for' => 'VIP properties, estates, luxury homes, investment portfolios, executive homeowners',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($tiers as $tier) {
            DB::table('tiers')->updateOrInsert(
                ['slug' => $tier['slug']],
                array_merge($tier, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ All 5 tiers created successfully!');
        $this->command->info('');
        $this->command->info('Tier 1 - Basic Care: $199/mo or $1,990/yr (Coverage: $500)');
        $this->command->info('Tier 2 - Essential Care: $349/mo or $3,490/yr (Coverage: $1,500)');
        $this->command->info('Tier 3 - Enhanced: $549/mo or $5,490/yr (Coverage: $3,000)');
        $this->command->info('Tier 4 - Premium Protection: $849/mo or $8,490/yr (Coverage: $6,000)');
        $this->command->info('Tier 5 - Elite Estate Care: $1,499/mo or $14,990/yr (Coverage: $15,000)');
    }
}
