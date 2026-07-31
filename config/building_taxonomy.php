<?php

use Illuminate\Support\Str;

$components = static function (string $raw, string $defaultTrade = null, array $aliases = []): array {
    return collect(preg_split('/\r\n|\r|\n/', trim($raw)))
        ->filter()
        ->values()
        ->map(function (string $line, int $index) use ($defaultTrade, $aliases) {
            [$code, $name] = preg_split('/\s{2,}/', trim($line), 2);

            return [
                'code' => $code,
                'name' => $name,
                'slug' => Str::slug($name),
                'sort_order' => ($index + 1) * 10,
                'default_trade' => $defaultTrade,
                'aliases' => $aliases[$code] ?? [],
            ];
        })
        ->all();
};

$subsystem = static function (string $code, string $name, int $sortOrder, array $components, ?string $description = null): array {
    return [
        'code' => $code,
        'name' => $name,
        'slug' => Str::slug($name),
        'description' => $description,
        'sort_order' => $sortOrder,
        'components' => $components,
    ];
};

return [
    [
        'code' => 'ENV',
        'name' => 'Building Envelope',
        'slug' => 'building-envelope',
        'description' => 'Assemblies and components separating the conditioned building interior from exterior or unconditioned environments, including roofing, exterior walls, fenestration, exterior doors and environmental control layers.',
        'sort_order' => 10,
        'is_core' => true,
        'subsystems' => [
            $subsystem('ENV-ROOF', 'Roofing', 10, $components(<<<'TXT'
ENV-ROOF-001  Asphalt Shingle Roofing
ENV-ROOF-002  Wood Shingle or Shake Roofing
ENV-ROOF-003  Metal Roofing
ENV-ROOF-004  Standing-Seam Metal Roofing
ENV-ROOF-005  Clay or Concrete Roof Tiles
ENV-ROOF-006  Slate Roofing
ENV-ROOF-007  Built-Up Roofing
ENV-ROOF-008  Modified-Bitumen Roofing
ENV-ROOF-009  EPDM Roofing Membrane
ENV-ROOF-010  TPO Roofing Membrane
ENV-ROOF-011  PVC Roofing Membrane
ENV-ROOF-012  Liquid-Applied Roofing Membrane
ENV-ROOF-013  Green Roof Assembly
ENV-ROOF-014  Roof Underlayment
ENV-ROOF-015  Roof Insulation
ENV-ROOF-016  Roof Vapour Retarder
ENV-ROOF-017  Roof Cover Board
ENV-ROOF-018  Roof Flashing
ENV-ROOF-019  Valley Flashing
ENV-ROOF-020  Chimney Flashing
ENV-ROOF-021  Plumbing-Vent Flashing
ENV-ROOF-022  Parapet Cap or Coping
ENV-ROOF-023  Ridge Cap
ENV-ROOF-024  Drip Edge
ENV-ROOF-025  Eaves
ENV-ROOF-026  Gutters
ENV-ROOF-027  Gutter Outlets
ENV-ROOF-028  Gutter Hangers
ENV-ROOF-029  Downspouts
ENV-ROOF-030  Leader Heads
ENV-ROOF-031  Scuppers
ENV-ROOF-032  Splash Blocks
ENV-ROOF-033  Fascia
ENV-ROOF-034  Soffit
ENV-ROOF-035  Roof Hatch
ENV-ROOF-036  Roof Curb
ENV-ROOF-037  Roof-Mounted Equipment Flashing
ENV-ROOF-038  Snow Guards
ENV-ROOF-039  Ice and Water Shield
TXT, 'Roofing', [
                'ENV-ROOF-026' => ['Eavestrough'],
                'ENV-ROOF-029' => ['Rain Leader'],
            ])),
            $subsystem('ENV-WALL', 'Exterior Wall Assemblies', 20, $components(<<<'TXT'
ENV-WALL-001  Brick Veneer
ENV-WALL-002  Stone Veneer
ENV-WALL-003  Vinyl Siding
ENV-WALL-004  Wood Siding
ENV-WALL-005  Fibre-Cement Siding
ENV-WALL-006  Metal Wall Panels
ENV-WALL-007  Stucco
ENV-WALL-008  Exterior Insulation and Finish System
ENV-WALL-009  Precast Concrete Wall Panels
ENV-WALL-010  Cast-in-Place Exterior Concrete
ENV-WALL-011  Concrete Masonry Exterior Wall
ENV-WALL-012  Curtain Wall Assembly
ENV-WALL-013  Rainscreen Assembly
ENV-WALL-014  Exterior Wall Sheathing
ENV-WALL-015  Cladding Support and Fasteners
ENV-WALL-016  Wall Flashing
ENV-WALL-017  Through-Wall Flashing
ENV-WALL-018  Weep Openings
ENV-WALL-019  Exterior Expansion Joint
ENV-WALL-020  Exterior Sealant Joint
ENV-WALL-021  Exterior Trim
ENV-WALL-022  Exterior Louvers
ENV-WALL-023  Exterior Wall Penetrations
TXT, 'Building Envelope')),
            $subsystem('ENV-FEN', 'Windows and Skylights', 30, $components(<<<'TXT'
ENV-FEN-001  Fixed Window
ENV-FEN-002  Casement Window
ENV-FEN-003  Awning Window
ENV-FEN-004  Sliding Window
ENV-FEN-005  Single-Hung Window
ENV-FEN-006  Double-Hung Window
ENV-FEN-007  Bay or Bow Window
ENV-FEN-008  Basement Window
ENV-FEN-009  Window Frame
ENV-FEN-010  Window Sash
ENV-FEN-011  Insulating Glass Unit
ENV-FEN-012  Window Hardware
ENV-FEN-013  Window Weatherstripping
ENV-FEN-014  Window Screen
ENV-FEN-015  Window Sill
ENV-FEN-016  Window Head Flashing
ENV-FEN-017  Window Perimeter Sealant
ENV-FEN-018  Skylight
ENV-FEN-019  Roof Window
ENV-FEN-020  Glazed Curtain-Wall Opening
TXT, 'Glazing and Fenestration')),
            $subsystem('ENV-DOOR', 'Exterior Doors and Openings', 40, $components(<<<'TXT'
ENV-DOOR-001  Exterior Entrance Door
ENV-DOOR-002  Exterior Service Door
ENV-DOOR-003  Patio Door
ENV-DOOR-004  Sliding Glazed Door
ENV-DOOR-005  French Exterior Door
ENV-DOOR-006  Overhead Garage Door
ENV-DOOR-007  Exterior Door Frame
ENV-DOOR-008  Exterior Door Threshold
ENV-DOOR-009  Exterior Door Hardware
ENV-DOOR-010  Exterior Door Closer
ENV-DOOR-011  Exterior Door Weatherstripping
ENV-DOOR-012  Exterior Door Sweep
ENV-DOOR-013  Exterior Door Perimeter Sealant
ENV-DOOR-014  Exterior Security Grille
ENV-DOOR-015  Loading-Dock Door
TXT, 'Building Envelope')),
            $subsystem('ENV-CTRL', 'Thermal, Air, Vapour and Water Control Layers', 50, $components(<<<'TXT'
ENV-CTRL-001  Exterior Wall Insulation
ENV-CTRL-002  Roof Insulation
ENV-CTRL-003  Below-Grade Insulation
ENV-CTRL-004  Air-Barrier Membrane
ENV-CTRL-005  Air-Barrier Transition
ENV-CTRL-006  Vapour Retarder
ENV-CTRL-007  Vapour Barrier
ENV-CTRL-008  Water-Resistive Barrier
ENV-CTRL-009  Waterproofing Membrane
ENV-CTRL-010  Dampproofing
ENV-CTRL-011  Joint Sealant
ENV-CTRL-012  Penetration Seal
ENV-CTRL-013  Expansion-Joint Assembly
ENV-CTRL-014  Thermal-Break Component
ENV-CTRL-015  Fire-Resistant Exterior Joint Seal
TXT, 'Building Envelope')),
            $subsystem('ENV-BGE', 'Below-Grade Enclosure', 60, $components(<<<'TXT'
ENV-BGE-001  Foundation-Wall Waterproofing
ENV-BGE-002  Foundation-Wall Dampproofing
ENV-BGE-003  Below-Grade Drainage Membrane
ENV-BGE-004  Below-Grade Insulation
ENV-BGE-005  Foundation-Wall Joint
ENV-BGE-006  Foundation Penetration Seal
ENV-BGE-007  Window Well
ENV-BGE-008  Window-Well Cover
ENV-BGE-009  Foundation Drainage Board
ENV-BGE-010  Basement Entrance Enclosure
TXT, 'Building Envelope')),
            $subsystem('ENV-APP', 'Exterior Appurtenances', 70, $components(<<<'TXT'
ENV-APP-001  Canopy
ENV-APP-002  Awning
ENV-APP-003  Porch Enclosure
ENV-APP-004  Balcony Enclosure
ENV-APP-005  Exterior Deck Surface
ENV-APP-006  Exterior Guard
ENV-APP-007  Exterior Handrail
ENV-APP-008  Exterior Stair Finish
ENV-APP-009  Sunshade
ENV-APP-010  Exterior Screen
TXT, 'Building Envelope')),
        ],
    ],
    [
        'code' => 'INT',
        'name' => 'Interiors',
        'slug' => 'interiors',
        'description' => 'Interior partitions, linings, finishes, doors, trim, millwork, stairs and specialties.',
        'sort_order' => 20,
        'is_core' => true,
        'subsystems' => [
            $subsystem('INT-PART', 'Interior Partitions and Linings', 10, $components(<<<'TXT'
INT-PART-001  Wood-Stud Partition
INT-PART-002  Metal-Stud Partition
INT-PART-003  Gypsum-Board Partition
INT-PART-004  Masonry Partition
INT-PART-005  Glass Partition
INT-PART-006  Demountable Partition
INT-PART-007  Acoustic Partition
INT-PART-008  Interior Wall Insulation
INT-PART-009  Interior Wall Lining
INT-PART-010  Interior Access Panel
TXT, 'Interiors')),
            $subsystem('INT-DOOR', 'Interior Doors, Frames and Hardware', 20, $components(<<<'TXT'
INT-DOOR-001  Hinged Interior Door
INT-DOOR-002  Sliding Interior Door
INT-DOOR-003  Pocket Door
INT-DOOR-004  Bifold Door
INT-DOOR-005  Interior Glazed Door
INT-DOOR-006  Fire-Rated Interior Door
INT-DOOR-007  Interior Door Frame
INT-DOOR-008  Interior Door Hardware
INT-DOOR-009  Door Closer
INT-DOOR-010  Door Stop
INT-DOOR-011  Door Seal
INT-DOOR-012  Interior Security Grille
TXT, 'Interiors')),
            $subsystem('INT-CEIL', 'Ceilings', 30, $components(<<<'TXT'
INT-CEIL-001  Gypsum-Board Ceiling
INT-CEIL-002  Suspended Acoustic Ceiling
INT-CEIL-003  Acoustic Ceiling Tile
INT-CEIL-004  Suspended-Ceiling Grid
INT-CEIL-005  Wood Ceiling
INT-CEIL-006  Metal Ceiling
INT-CEIL-007  Plaster Ceiling
INT-CEIL-008  Decorative Ceiling
INT-CEIL-009  Bulkhead
INT-CEIL-010  Ceiling Access Panel
INT-CEIL-011  Ceiling Trim
TXT, 'Interiors')),
            $subsystem('INT-WFIN', 'Wall Finishes', 40, $components(<<<'TXT'
INT-WFIN-001  Interior Paint
INT-WFIN-002  Wallpaper
INT-WFIN-003  Ceramic Wall Tile
INT-WFIN-004  Stone Wall Finish
INT-WFIN-005  Interior Plaster
INT-WFIN-006  Gypsum-Board Finish
INT-WFIN-007  Wood Wall Panelling
INT-WFIN-008  Decorative Wall Panel
INT-WFIN-009  Acoustic Wall Panel
INT-WFIN-010  Protective Wall Covering
TXT, 'Interiors')),
            $subsystem('INT-FFIN', 'Floor Finishes', 50, $components(<<<'TXT'
INT-FFIN-001  Hardwood Flooring
INT-FFIN-002  Engineered-Wood Flooring
INT-FFIN-003  Laminate Flooring
INT-FFIN-004  Carpet
INT-FFIN-005  Carpet Tile
INT-FFIN-006  Sheet-Vinyl Flooring
INT-FFIN-007  Luxury Vinyl Tile or Plank
INT-FFIN-008  Resilient Tile Flooring
INT-FFIN-009  Ceramic Floor Tile
INT-FFIN-010  Porcelain Floor Tile
INT-FFIN-011  Stone Flooring
INT-FFIN-012  Polished Concrete
INT-FFIN-013  Epoxy Floor Coating
INT-FFIN-014  Floor Underlayment
INT-FFIN-015  Floor Transition Strip
INT-FFIN-016  Floor Drain Cover
TXT, 'Interiors', ['INT-FFIN-007' => ['LVT', 'LVP']])),
            $subsystem('INT-TRIM', 'Interior Trim and Millwork', 60, $components(<<<'TXT'
INT-TRIM-001  Baseboard
INT-TRIM-002  Door Casing
INT-TRIM-003  Window Casing
INT-TRIM-004  Crown Moulding
INT-TRIM-005  Chair Rail
INT-TRIM-006  Interior Moulding
INT-TRIM-007  Window Stool or Interior Sill
INT-TRIM-008  Built-In Shelving
INT-TRIM-009  Closet Fittings
INT-TRIM-010  Decorative Woodwork
TXT, 'Interiors')),
            $subsystem('INT-CAB', 'Cabinetry and Countertops', 70, $components(<<<'TXT'
INT-CAB-001  Kitchen Base Cabinet
INT-CAB-002  Kitchen Wall Cabinet
INT-CAB-003  Bathroom Vanity
INT-CAB-004  Built-In Cabinet
INT-CAB-005  Laminate Countertop
INT-CAB-006  Solid-Surface Countertop
INT-CAB-007  Stone Countertop
INT-CAB-008  Wood Countertop
INT-CAB-009  Countertop Backsplash
INT-CAB-010  Cabinet Hardware
TXT, 'Interiors')),
            $subsystem('INT-STAIR', 'Interior Stairs, Guards and Railings', 80, $components(<<<'TXT'
INT-STAIR-001  Stair Tread Finish
INT-STAIR-002  Stair Riser Finish
INT-STAIR-003  Stair Nosing
INT-STAIR-004  Interior Handrail
INT-STAIR-005  Interior Guard
INT-STAIR-006  Balustrade
INT-STAIR-007  Stair Landing Finish
TXT, 'Interiors')),
            $subsystem('INT-SPEC', 'Interior Specialties', 90, $components(<<<'TXT'
INT-SPEC-001  Mirror
INT-SPEC-002  Washroom Accessory
INT-SPEC-003  Toilet Partition
INT-SPEC-004  Interior Signage
INT-SPEC-005  Locker
INT-SPEC-006  Mailbox
INT-SPEC-007  Corner Guard
INT-SPEC-008  Wall Protection
INT-SPEC-009  Entrance Mat and Grille
INT-SPEC-010  Interior Window Covering
TXT, 'Interiors')),
        ],
    ],
    [
        'code' => 'PLB',
        'name' => 'Plumbing',
        'slug' => 'plumbing',
        'description' => 'Water service, distribution, fixtures, drainage, venting, storm drainage, sump and water treatment systems.',
        'sort_order' => 30,
        'is_core' => true,
        'subsystems' => [
            $subsystem('PLB-WATER', 'Water Service and Distribution', 10, $components(<<<'TXT'
PLB-WATER-001  Building Water Service
PLB-WATER-002  Water Meter
PLB-WATER-003  Main Water Shut-Off Valve
PLB-WATER-004  Cold-Water Piping
PLB-WATER-005  Hot-Water Piping
PLB-WATER-006  Hot-Water Recirculation Piping
PLB-WATER-007  Isolation Valve
PLB-WATER-008  Pressure-Reducing Valve
PLB-WATER-009  Backflow Preventer
PLB-WATER-010  Water-Hammer Arrestor
PLB-WATER-011  Expansion Tank
PLB-WATER-012  Hose Bibb
PLB-WATER-013  Frost-Free Hose Bibb
PLB-WATER-014  Water-Supply Manifold
TXT, 'Plumbing', ['PLB-WATER-012' => ['Exterior Faucet', 'Sillcock']])),
            $subsystem('PLB-DHW', 'Domestic Hot Water', 20, $components(<<<'TXT'
PLB-DHW-001  Storage-Tank Water Heater
PLB-DHW-002  Tankless Water Heater
PLB-DHW-003  Electric Water Heater
PLB-DHW-004  Gas-Fired Water Heater
PLB-DHW-005  Heat-Pump Water Heater
PLB-DHW-006  Domestic-Hot-Water Storage Tank
PLB-DHW-007  Mixing Valve
PLB-DHW-008  Domestic-Hot-Water Circulation Pump
PLB-DHW-009  Water-Heater Drain Pan
PLB-DHW-010  Water-Heater Temperature and Pressure Relief Valve
TXT, 'Plumbing')),
            $subsystem('PLB-FIX', 'Plumbing Fixtures and Faucets', 30, $components(<<<'TXT'
PLB-FIX-001  Water Closet
PLB-FIX-002  Urinal
PLB-FIX-003  Lavatory
PLB-FIX-004  Kitchen Sink
PLB-FIX-005  Utility Sink
PLB-FIX-006  Laundry Sink
PLB-FIX-007  Bathtub
PLB-FIX-008  Shower Base
PLB-FIX-009  Shower Enclosure
PLB-FIX-010  Shower Valve
PLB-FIX-011  Faucet
PLB-FIX-012  Drinking Fountain
PLB-FIX-013  Floor Drain
PLB-FIX-014  Laundry Connection
PLB-FIX-015  Dishwasher Connection
PLB-FIX-016  Refrigerator Water Connection
TXT, 'Plumbing', ['PLB-FIX-001' => ['Toilet']])),
            $subsystem('PLB-SAN', 'Sanitary Drainage, Waste and Vent', 40, $components(<<<'TXT'
PLB-SAN-001  Sanitary Building Drain
PLB-SAN-002  Sanitary Building Sewer
PLB-SAN-003  Soil Stack
PLB-SAN-004  Waste Stack
PLB-SAN-005  Vent Stack
PLB-SAN-006  Branch Drain
PLB-SAN-007  Plumbing Trap
PLB-SAN-008  Cleanout
PLB-SAN-009  Backwater Valve
PLB-SAN-010  Sewage Ejector
PLB-SAN-011  Sewage-Ejector Pit
PLB-SAN-012  Septic Tank
PLB-SAN-013  Septic Distribution System
TXT, 'Plumbing')),
            $subsystem('PLB-STORM', 'Storm Drainage and Rainwater Piping', 50, $components(<<<'TXT'
PLB-STORM-001  Internal Roof Drain
PLB-STORM-002  Overflow Roof Drain
PLB-STORM-003  Storm Leader
PLB-STORM-004  Internal Rainwater Piping
PLB-STORM-005  Storm Building Drain
PLB-STORM-006  Storm Building Sewer
PLB-STORM-007  Drainage Cleanout
TXT, 'Plumbing')),
            $subsystem('PLB-SUMP', 'Sump, Sewage and Foundation Drainage', 60, $components(<<<'TXT'
PLB-SUMP-001  Sump Pit
PLB-SUMP-002  Sump Pump
PLB-SUMP-003  Backup Sump Pump
PLB-SUMP-004  Sump Discharge Piping
PLB-SUMP-005  Foundation Drain
PLB-SUMP-006  Drainage Check Valve
PLB-SUMP-007  Sewage Pump
PLB-SUMP-008  High-Water Alarm
TXT, 'Plumbing')),
            $subsystem('PLB-TREAT', 'Water Treatment and Well Systems', 70, $components(<<<'TXT'
PLB-TREAT-001  Well
PLB-TREAT-002  Well Pump
PLB-TREAT-003  Pressure Tank
PLB-TREAT-004  Water Softener
PLB-TREAT-005  Sediment Filter
PLB-TREAT-006  Carbon Filter
PLB-TREAT-007  Reverse-Osmosis System
PLB-TREAT-008  Ultraviolet Water-Treatment System
PLB-TREAT-009  Chemical Water-Treatment System
TXT, 'Plumbing')),
        ],
    ],
    [
        'code' => 'MEC',
        'name' => 'HVAC and Mechanical',
        'slug' => 'hvac-and-mechanical',
        'description' => 'Heating, cooling, ventilation, air distribution, hydronic, fuel, flue and mechanical control systems.',
        'sort_order' => 40,
        'is_core' => true,
        'subsystems' => [
            $subsystem('MEC-HEAT', 'Heating Systems', 10, $components(<<<'TXT'
MEC-HEAT-001  Gas-Fired Furnace
MEC-HEAT-002  Oil-Fired Furnace
MEC-HEAT-003  Electric Furnace
MEC-HEAT-004  Gas-Fired Boiler
MEC-HEAT-005  Oil-Fired Boiler
MEC-HEAT-006  Electric Boiler
MEC-HEAT-007  Air-Source Heat Pump
MEC-HEAT-008  Ground-Source Heat Pump
MEC-HEAT-009  Ductless Mini-Split Heat Pump
MEC-HEAT-010  Electric Baseboard Heater
MEC-HEAT-011  Hydronic Baseboard Heater
MEC-HEAT-012  Radiator
MEC-HEAT-013  Unit Heater
MEC-HEAT-014  Radiant Floor Heating
MEC-HEAT-015  Fireplace
MEC-HEAT-016  Wood-Burning Stove
TXT, 'HVAC')),
            $subsystem('MEC-COOL', 'Cooling Systems', 20, $components(<<<'TXT'
MEC-COOL-001  Central Air-Conditioning System
MEC-COOL-002  Outdoor Condensing Unit
MEC-COOL-003  Indoor Evaporator Coil
MEC-COOL-004  Chiller
MEC-COOL-005  Cooling Tower
MEC-COOL-006  Fan-Coil Unit
MEC-COOL-007  Packaged Terminal Air Conditioner
MEC-COOL-008  Ductless Cooling Unit
MEC-COOL-009  Refrigerant Piping
MEC-COOL-010  Condensate Drain
TXT, 'HVAC')),
            $subsystem('MEC-VENT', 'Ventilation and Air Handling', 30, $components(<<<'TXT'
MEC-VENT-001  Air-Handling Unit
MEC-VENT-002  Heat-Recovery Ventilator
MEC-VENT-003  Energy-Recovery Ventilator
MEC-VENT-004  Make-Up-Air Unit
MEC-VENT-005  Fresh-Air Intake
MEC-VENT-006  Exhaust-Air Outlet
MEC-VENT-007  Supply Fan
MEC-VENT-008  Return Fan
MEC-VENT-009  Exhaust Fan
MEC-VENT-010  Bathroom Exhaust Fan
MEC-VENT-011  Kitchen Exhaust Hood
MEC-VENT-012  Range-Hood Exhaust Duct
MEC-VENT-013  Dryer Exhaust
MEC-VENT-014  Garage Ventilation
MEC-VENT-015  Mechanical Louver
MEC-VENT-016  Fire Damper
MEC-VENT-017  Smoke Damper
MEC-VENT-018  Backdraft Damper
TXT, 'HVAC', [
                'MEC-VENT-002' => ['HRV'],
                'MEC-VENT-003' => ['ERV'],
            ])),
            $subsystem('MEC-AIR', 'Air Distribution', 40, $components(<<<'TXT'
MEC-AIR-001  Supply-Air Ductwork
MEC-AIR-002  Return-Air Ductwork
MEC-AIR-003  Exhaust-Air Ductwork
MEC-AIR-004  Flexible Duct
MEC-AIR-005  Duct Insulation
MEC-AIR-006  Supply-Air Diffuser
MEC-AIR-007  Supply-Air Register
MEC-AIR-008  Return-Air Grille
MEC-AIR-009  Exhaust-Air Grille
MEC-AIR-010  Volume-Control Damper
MEC-AIR-011  Air Filter
MEC-AIR-012  Air-Cleaning Device
TXT, 'HVAC')),
            $subsystem('MEC-HYD', 'Hydronic Distribution', 50, $components(<<<'TXT'
MEC-HYD-001  Heating-Water Piping
MEC-HYD-002  Chilled-Water Piping
MEC-HYD-003  Circulation Pump
MEC-HYD-004  Hydronic Expansion Tank
MEC-HYD-005  Air Separator
MEC-HYD-006  Hydronic Control Valve
MEC-HYD-007  Zone Valve
MEC-HYD-008  Hydronic Manifold
MEC-HYD-009  Radiant-Floor Heating Loop
MEC-HYD-010  Pipe Insulation
TXT, 'HVAC')),
            $subsystem('MEC-FUEL', 'Fuel Gas and Oil Systems', 60, $components(<<<'TXT'
MEC-FUEL-001  Natural-Gas Service
MEC-FUEL-002  Gas Meter
MEC-FUEL-003  Gas Regulator
MEC-FUEL-004  Fuel-Gas Piping
MEC-FUEL-005  Gas Shut-Off Valve
MEC-FUEL-006  Propane Storage Tank
MEC-FUEL-007  Propane Piping
MEC-FUEL-008  Fuel-Oil Storage Tank
MEC-FUEL-009  Fuel-Oil Piping
MEC-FUEL-010  Fuel Leak-Detection Device
TXT, 'Mechanical')),
            $subsystem('MEC-FLUE', 'Chimneys, Flues and Combustion Air', 70, $components(<<<'TXT'
MEC-FLUE-001  Masonry Chimney
MEC-FLUE-002  Factory-Built Chimney
MEC-FLUE-003  Appliance Flue
MEC-FLUE-004  Vent Connector
MEC-FLUE-005  Chimney Liner
MEC-FLUE-006  Chimney Cap
MEC-FLUE-007  Combustion-Air Intake
MEC-FLUE-008  Draft Regulator
TXT, 'Mechanical')),
            $subsystem('MEC-CTRL', 'Mechanical Controls', 80, $components(<<<'TXT'
MEC-CTRL-001  Thermostat
MEC-CTRL-002  Zone Thermostat
MEC-CTRL-003  Temperature Sensor
MEC-CTRL-004  Humidity Sensor
MEC-CTRL-005  Carbon-Dioxide Sensor
MEC-CTRL-006  Mechanical Control Panel
MEC-CTRL-007  Motorized Damper Actuator
MEC-CTRL-008  Variable-Frequency Drive
MEC-CTRL-009  HVAC Equipment Controller
TXT, 'Controls')),
        ],
    ],
    [
        'code' => 'ELE',
        'name' => 'Electrical',
        'slug' => 'electrical',
        'description' => 'Electrical service, distribution, branch wiring, lighting, grounding, standby power, renewable energy and EV charging systems.',
        'sort_order' => 50,
        'is_core' => true,
        'subsystems' => [
            $subsystem('ELE-SERV', 'Utility Service and Metering', 10, $components(<<<'TXT'
ELE-SERV-001  Overhead Electrical Service
ELE-SERV-002  Underground Electrical Service
ELE-SERV-003  Service-Entrance Conductors
ELE-SERV-004  Electrical Meter
ELE-SERV-005  Meter Base
ELE-SERV-006  Main Service Disconnect
ELE-SERV-007  Service Grounding Electrode
TXT, 'Electrical')),
            $subsystem('ELE-DIST', 'Electrical Distribution', 20, $components(<<<'TXT'
ELE-DIST-001  Main Distribution Panel
ELE-DIST-002  Electrical Panelboard
ELE-DIST-003  Subpanel
ELE-DIST-004  Switchboard
ELE-DIST-005  Transformer
ELE-DIST-006  Circuit Breaker
ELE-DIST-007  Fuse
ELE-DIST-008  Feeder Conductors
ELE-DIST-009  Busway
ELE-DIST-010  Disconnect Switch
TXT, 'Electrical')),
            $subsystem('ELE-BRCH', 'Branch Wiring and Devices', 30, $components(<<<'TXT'
ELE-BRCH-001  Branch-Circuit Wiring
ELE-BRCH-002  Electrical Receptacle
ELE-BRCH-003  Ground-Fault Circuit-Interrupter Receptacle
ELE-BRCH-004  Arc-Fault Circuit-Interrupter Protection
ELE-BRCH-005  Electrical Switch
ELE-BRCH-006  Dimmer Switch
ELE-BRCH-007  Junction Box
ELE-BRCH-008  Appliance Connection
ELE-BRCH-009  Dedicated Circuit
ELE-BRCH-010  Exterior Receptacle
ELE-BRCH-011  Floor Receptacle
ELE-BRCH-012  Raceway or Conduit
TXT, 'Electrical', ['ELE-BRCH-002' => ['Outlet', 'Socket', 'Plug Point']])),
            $subsystem('ELE-LIGHT', 'Lighting and Controls', 40, $components(<<<'TXT'
ELE-LIGHT-001  Interior Light Fixture
ELE-LIGHT-002  Exterior Building Light Fixture
ELE-LIGHT-003  Recessed Light Fixture
ELE-LIGHT-004  Emergency Light Fixture
ELE-LIGHT-005  Lighting-Control Panel
ELE-LIGHT-006  Occupancy Sensor
ELE-LIGHT-007  Daylight Sensor
ELE-LIGHT-008  Photocell
ELE-LIGHT-009  Lighting Timer
ELE-LIGHT-010  Exit Sign
TXT, 'Electrical')),
            $subsystem('ELE-GRND', 'Grounding, Bonding and Surge Protection', 50, $components(<<<'TXT'
ELE-GRND-001  Grounding Electrode
ELE-GRND-002  Grounding-Electrode Conductor
ELE-GRND-003  Bonding Conductor
ELE-GRND-004  Equipment Grounding
ELE-GRND-005  Ground Bar
ELE-GRND-006  Surge-Protective Device
ELE-GRND-007  Lightning-Protection System
TXT, 'Electrical')),
            $subsystem('ELE-STBY', 'Emergency and Standby Power', 60, $components(<<<'TXT'
ELE-STBY-001  Standby Generator
ELE-STBY-002  Emergency Generator
ELE-STBY-003  Automatic Transfer Switch
ELE-STBY-004  Manual Transfer Switch
ELE-STBY-005  Uninterruptible Power Supply
ELE-STBY-006  Emergency Battery Unit
ELE-STBY-007  Generator Fuel System
TXT, 'Electrical')),
            $subsystem('ELE-REN', 'Renewable Energy and Storage', 70, $components(<<<'TXT'
ELE-REN-001  Solar Photovoltaic Module
ELE-REN-002  Solar-PV Mounting System
ELE-REN-003  Solar Inverter
ELE-REN-004  Solar Disconnect
ELE-REN-005  Battery-Energy Storage System
ELE-REN-006  Battery Controller
ELE-REN-007  Energy-Monitoring Meter
TXT, 'Electrical')),
            $subsystem('ELE-EV', 'Electric-Vehicle Charging', 80, $components(<<<'TXT'
ELE-EV-001  Electric-Vehicle Charging Station
ELE-EV-002  EV-Charger Circuit
ELE-EV-003  EV-Charger Disconnect
ELE-EV-004  EV-Charging Cable and Connector
TXT, 'Electrical')),
        ],
    ],
    [
        'code' => 'STR',
        'name' => 'Structure and Substructure',
        'slug' => 'structure-and-substructure',
        'description' => 'Foundations, structural frame, floor structure, roof structure, structural stairs, balconies, decks and movement joints.',
        'sort_order' => 60,
        'is_core' => false,
        'subsystems' => [
            $subsystem('STR-FND', 'Foundations and Footings', 10, $components(<<<'TXT'
STR-FND-001  Strip Footing
STR-FND-002  Pad or Spread Footing
STR-FND-003  Mat or Raft Foundation
STR-FND-004  Pile Foundation
STR-FND-005  Caisson
STR-FND-006  Pile Cap
STR-FND-007  Grade Beam
STR-FND-008  Foundation Wall
STR-FND-009  Foundation Pier
STR-FND-010  Foundation Anchor
STR-FND-011  Underpinning
TXT, 'Structural')),
            $subsystem('STR-SLAB', 'Slabs and Below-Grade Structure', 20, $components(<<<'TXT'
STR-SLAB-001  Slab-on-Grade
STR-SLAB-002  Structural Basement Slab
STR-SLAB-003  Suspended Concrete Slab
STR-SLAB-004  Equipment Pad
STR-SLAB-005  Structural Pit
STR-SLAB-006  Below-Grade Retaining Wall
TXT, 'Structural')),
            $subsystem('STR-FRAME', 'Structural Frame', 30, $components(<<<'TXT'
STR-FRAME-001  Load-Bearing Wall
STR-FRAME-002  Structural Column
STR-FRAME-003  Structural Beam
STR-FRAME-004  Girder
STR-FRAME-005  Lintel
STR-FRAME-006  Structural Bracing
STR-FRAME-007  Shear Wall
STR-FRAME-008  Structural Connection
STR-FRAME-009  Structural Steel Frame
STR-FRAME-010  Structural Wood Frame
STR-FRAME-011  Structural Concrete Frame
TXT, 'Structural')),
            $subsystem('STR-FLOOR', 'Floor Structure', 40, $components(<<<'TXT'
STR-FLOOR-001  Wood Floor Joist
STR-FLOOR-002  Engineered-Wood Floor Joist
STR-FLOOR-003  Floor Truss
STR-FLOOR-004  Structural Floor Beam
STR-FLOOR-005  Floor Subfloor
STR-FLOOR-006  Structural Floor Deck
STR-FLOOR-007  Concrete Floor Slab
STR-FLOOR-008  Floor Bridging or Blocking
TXT, 'Structural')),
            $subsystem('STR-ROOF', 'Roof Structure', 50, $components(<<<'TXT'
STR-ROOF-001  Roof Rafter
STR-ROOF-002  Roof Truss
STR-ROOF-003  Roof Beam
STR-ROOF-004  Roof Joist
STR-ROOF-005  Roof Purlin
STR-ROOF-006  Structural Roof Deck
STR-ROOF-007  Roof Sheathing
STR-ROOF-008  Roof Bracing
STR-ROOF-009  Structural Roof Post
TXT, 'Structural')),
            $subsystem('STR-EXT', 'Structural Stairs, Balconies and Decks', 60, $components(<<<'TXT'
STR-EXT-001  Structural Stair Stringer
STR-EXT-002  Structural Stair Landing
STR-EXT-003  Balcony Slab
STR-EXT-004  Balcony Framing
STR-EXT-005  Deck Beam
STR-EXT-006  Deck Joist
STR-EXT-007  Deck Post
STR-EXT-008  Deck Footing
STR-EXT-009  Porch Structure
TXT, 'Structural')),
            $subsystem('STR-JOINT', 'Structural and Movement Joints', 70, $components(<<<'TXT'
STR-JOINT-001  Structural Expansion Joint
STR-JOINT-002  Seismic Joint
STR-JOINT-003  Concrete Control Joint
STR-JOINT-004  Structural Isolation Joint
TXT, 'Structural')),
        ],
    ],
    [
        'code' => 'FLS',
        'name' => 'Fire and Life Safety',
        'slug' => 'fire-and-life-safety',
        'description' => 'Fire alarm, suppression, standpipe, portable fire protection, passive fire protection and egress systems.',
        'sort_order' => 70,
        'is_core' => false,
        'subsystems' => [
            $subsystem('FLS-ALARM', 'Fire Alarm and Detection', 10, $components(<<<'TXT'
FLS-ALARM-001  Fire-Alarm Control Panel
FLS-ALARM-002  Smoke Detector
FLS-ALARM-003  Heat Detector
FLS-ALARM-004  Manual Pull Station
FLS-ALARM-005  Fire-Alarm Horn
FLS-ALARM-006  Fire-Alarm Strobe
FLS-ALARM-007  Horn-and-Strobe Device
FLS-ALARM-008  Fire-Alarm Annunciator
FLS-ALARM-009  Fire-Alarm Monitoring Connection
FLS-ALARM-010  Carbon-Monoxide Alarm
TXT, 'Fire Protection')),
            $subsystem('FLS-SPR', 'Automatic Fire Suppression', 20, $components(<<<'TXT'
FLS-SPR-001  Sprinkler Head
FLS-SPR-002  Sprinkler Piping
FLS-SPR-003  Sprinkler Control Valve
FLS-SPR-004  Alarm Check Valve
FLS-SPR-005  Fire Pump
FLS-SPR-006  Fire-Pump Controller
FLS-SPR-007  Fire-Department Connection
FLS-SPR-008  Sprinkler Flow Switch
FLS-SPR-009  Sprinkler Tamper Switch
TXT, 'Fire Protection')),
            $subsystem('FLS-STAND', 'Standpipe and Hose Systems', 30, $components(<<<'TXT'
FLS-STAND-001  Standpipe
FLS-STAND-002  Fire-Hose Cabinet
FLS-STAND-003  Fire Hose
FLS-STAND-004  Hose Valve
FLS-STAND-005  Standpipe Pressure-Regulating Valve
TXT, 'Fire Protection')),
            $subsystem('FLS-PORT', 'Portable Fire Protection', 40, $components(<<<'TXT'
FLS-PORT-001  Portable Fire Extinguisher
FLS-PORT-002  Fire-Extinguisher Cabinet
FLS-PORT-003  Fire Blanket
TXT, 'Fire Protection')),
            $subsystem('FLS-PASS', 'Passive Fire Protection', 50, $components(<<<'TXT'
FLS-PASS-001  Fire-Rated Wall Assembly
FLS-PASS-002  Fire-Rated Floor Assembly
FLS-PASS-003  Fire-Rated Ceiling Assembly
FLS-PASS-004  Fire-Rated Door Assembly
FLS-PASS-005  Fire-Rated Glazing
FLS-PASS-006  Firestopping
FLS-PASS-007  Fire-Resistant Joint System
FLS-PASS-008  Fire Damper
FLS-PASS-009  Smoke Damper
FLS-PASS-010  Fire Separation
TXT, 'Fire Protection')),
            $subsystem('FLS-EGR', 'Means of Egress and Emergency Systems', 60, $components(<<<'TXT'
FLS-EGR-001  Exit Sign
FLS-EGR-002  Emergency Lighting
FLS-EGR-003  Exit Door
FLS-EGR-004  Exit-Door Hardware
FLS-EGR-005  Panic Hardware
FLS-EGR-006  Exit Stair
FLS-EGR-007  Egress Corridor
FLS-EGR-008  Area-of-Refuge Equipment
TXT, 'Fire Protection')),
        ],
    ],
    [
        'code' => 'CSC',
        'name' => 'Communications, Security and Controls',
        'slug' => 'communications-security-and-controls',
        'description' => 'Data, telecommunications, intercom, AV, access control, intrusion detection, CCTV and building automation systems.',
        'sort_order' => 80,
        'is_core' => false,
        'subsystems' => [
            $subsystem('CSC-DATA', 'Data and Telecommunications', 10, $components(<<<'TXT'
CSC-DATA-001  Data Cabling
CSC-DATA-002  Telecommunications Cabling
CSC-DATA-003  Data Outlet
CSC-DATA-004  Telecommunications Outlet
CSC-DATA-005  Network Rack
CSC-DATA-006  Patch Panel
CSC-DATA-007  Network Switch
CSC-DATA-008  Wireless Access Point
CSC-DATA-009  Fibre-Optic Cabling
CSC-DATA-010  Service-Provider Demarcation
TXT, 'Communications')),
            $subsystem('CSC-COM', 'Intercom, Public Address and Audio-Visual', 20, $components(<<<'TXT'
CSC-COM-001  Intercom Station
CSC-COM-002  Video Intercom
CSC-COM-003  Public-Address Speaker
CSC-COM-004  Public-Address Amplifier
CSC-COM-005  Doorbell
CSC-COM-006  Television Distribution
CSC-COM-007  Audio-Visual Outlet
CSC-COM-008  Display Screen
TXT, 'Communications')),
            $subsystem('CSC-ACCESS', 'Access Control', 30, $components(<<<'TXT'
CSC-ACCESS-001  Access-Control Panel
CSC-ACCESS-002  Card Reader
CSC-ACCESS-003  Keypad
CSC-ACCESS-004  Electronic Lock
CSC-ACCESS-005  Electric Strike
CSC-ACCESS-006  Magnetic Lock
CSC-ACCESS-007  Request-to-Exit Device
CSC-ACCESS-008  Door-Position Contact
CSC-ACCESS-009  Credential
TXT, 'Security')),
            $subsystem('CSC-INTR', 'Intrusion Detection', 40, $components(<<<'TXT'
CSC-INTR-001  Intrusion-Alarm Panel
CSC-INTR-002  Motion Detector
CSC-INTR-003  Door Contact
CSC-INTR-004  Window Contact
CSC-INTR-005  Glass-Break Detector
CSC-INTR-006  Security Siren
CSC-INTR-007  Security Keypad
TXT, 'Security')),
            $subsystem('CSC-CCTV', 'Video Surveillance', 50, $components(<<<'TXT'
CSC-CCTV-001  Interior Security Camera
CSC-CCTV-002  Exterior Security Camera
CSC-CCTV-003  Video Recorder
CSC-CCTV-004  Camera Power Supply
CSC-CCTV-005  Camera Mount
CSC-CCTV-006  Video-Monitoring Workstation
TXT, 'Security')),
            $subsystem('CSC-BAS', 'Building Automation and Monitoring', 60, $components(<<<'TXT'
CSC-BAS-001  Building-Automation Controller
CSC-BAS-002  Building-Automation Workstation
CSC-BAS-003  Environmental Sensor
CSC-BAS-004  Water-Leak Sensor
CSC-BAS-005  Freeze-Protection Sensor
CSC-BAS-006  Energy Meter
CSC-BAS-007  Smart Thermostat
CSC-BAS-008  Remote-Monitoring Gateway
CSC-BAS-009  Equipment-Control Interface
TXT, 'Controls')),
        ],
    ],
    [
        'code' => 'CON',
        'name' => 'Conveying',
        'slug' => 'conveying',
        'description' => 'Elevators, lifts, material conveying, escalators and moving walks.',
        'sort_order' => 90,
        'is_core' => false,
        'subsystems' => [
            $subsystem('CON-ELEV', 'Elevators', 10, $components(<<<'TXT'
CON-ELEV-001  Passenger Elevator
CON-ELEV-002  Freight Elevator
CON-ELEV-003  Service Elevator
CON-ELEV-004  Elevator Cab
CON-ELEV-005  Elevator Door
CON-ELEV-006  Elevator Controller
CON-ELEV-007  Elevator Machine
CON-ELEV-008  Elevator Hydraulic Unit
CON-ELEV-009  Elevator Shaft
CON-ELEV-010  Elevator Pit
TXT, 'Elevator')),
            $subsystem('CON-LIFT', 'Accessibility and Platform Lifts', 20, $components(<<<'TXT'
CON-LIFT-001  Vertical Platform Lift
CON-LIFT-002  Inclined Platform Lift
CON-LIFT-003  Stair Lift
CON-LIFT-004  Wheelchair Lift
TXT, 'Elevator')),
            $subsystem('CON-MAT', 'Material Conveying', 30, $components(<<<'TXT'
CON-MAT-001  Dumbwaiter
CON-MAT-002  Material Lift
CON-MAT-003  Service Hoist
TXT, 'Elevator')),
            $subsystem('CON-ESC', 'Escalators and Moving Walks', 40, $components(<<<'TXT'
CON-ESC-001  Escalator
CON-ESC-002  Moving Walk
CON-ESC-003  Escalator Handrail
CON-ESC-004  Escalator Comb Plate
TXT, 'Elevator')),
        ],
    ],
    [
        'code' => 'EQF',
        'name' => 'Equipment and Furnishings',
        'slug' => 'equipment-and-furnishings',
        'description' => 'Residential appliances, fixed furnishings, window coverings and commercial or institutional equipment.',
        'sort_order' => 100,
        'is_core' => false,
        'subsystems' => [
            $subsystem('EQF-RES', 'Residential Appliances', 10, $components(<<<'TXT'
EQF-RES-001  Refrigerator
EQF-RES-002  Freezer
EQF-RES-003  Cooking Range
EQF-RES-004  Cooktop
EQF-RES-005  Wall Oven
EQF-RES-006  Microwave Oven
EQF-RES-007  Dishwasher
EQF-RES-008  Clothes Washer
EQF-RES-009  Clothes Dryer
EQF-RES-010  Range Hood
TXT, 'Appliance')),
            $subsystem('EQF-FIX', 'Fixed Furnishings', 20, $components(<<<'TXT'
EQF-FIX-001  Fixed Seating
EQF-FIX-002  Fixed Table
EQF-FIX-003  Fixed Workstation
EQF-FIX-004  Built-In Storage
EQF-FIX-005  Fixed Shelving
EQF-FIX-006  Reception Counter
TXT, 'Millwork')),
            $subsystem('EQF-WIN', 'Window Coverings', 30, $components(<<<'TXT'
EQF-WIN-001  Roller Shade
EQF-WIN-002  Venetian Blind
EQF-WIN-003  Vertical Blind
EQF-WIN-004  Curtain Track
EQF-WIN-005  Interior Shutter
TXT, 'Interiors')),
            $subsystem('EQF-COM', 'Commercial and Institutional Equipment', 40, $components(<<<'TXT'
EQF-COM-001  Commercial Kitchen Equipment
EQF-COM-002  Commercial Laundry Equipment
EQF-COM-003  Laboratory Equipment
EQF-COM-004  Medical Equipment
EQF-COM-005  Workshop Equipment
EQF-COM-006  Waste-Handling Equipment
TXT, 'Equipment')),
        ],
    ],
    [
        'code' => 'SITE',
        'name' => 'Site and Civil Works',
        'slug' => 'site-and-civil-works',
        'description' => 'Site grading, drainage, paving, landscaping, fences, site structures and exterior site utilities.',
        'sort_order' => 110,
        'is_core' => false,
        'subsystems' => [
            $subsystem('SITE-GRADE', 'Site Grading and Earthwork', 10, $components(<<<'TXT'
SITE-GRADE-001  Site Grading
SITE-GRADE-002  Excavation
SITE-GRADE-003  Fill
SITE-GRADE-004  Slope
SITE-GRADE-005  Embankment
SITE-GRADE-006  Erosion-Control Measure
TXT, 'Civil')),
            $subsystem('SITE-DRAIN', 'Site Drainage', 20, $components(<<<'TXT'
SITE-DRAIN-001  Drainage Swale
SITE-DRAIN-002  Catch Basin
SITE-DRAIN-003  Area Drain
SITE-DRAIN-004  Culvert
SITE-DRAIN-005  French Drain
SITE-DRAIN-006  Storm Manhole
SITE-DRAIN-007  Retention or Detention Area
SITE-DRAIN-008  Site Sump
SITE-DRAIN-009  Drainage Ditch
TXT, 'Civil')),
            $subsystem('SITE-ROAD', 'Roads, Driveways and Parking', 30, $components(<<<'TXT'
SITE-ROAD-001  Asphalt Driveway
SITE-ROAD-002  Concrete Driveway
SITE-ROAD-003  Gravel Driveway
SITE-ROAD-004  Asphalt Parking Area
SITE-ROAD-005  Concrete Parking Area
SITE-ROAD-006  Parking Curb
SITE-ROAD-007  Wheel Stop
SITE-ROAD-008  Roadway
SITE-ROAD-009  Loading Area
SITE-ROAD-010  Parking-Lot Marking
TXT, 'Civil')),
            $subsystem('SITE-WALK', 'Walkways, Patios and Exterior Paving', 40, $components(<<<'TXT'
SITE-WALK-001  Concrete Walkway
SITE-WALK-002  Asphalt Walkway
SITE-WALK-003  Unit-Paver Walkway
SITE-WALK-004  Exterior Ramp
SITE-WALK-005  Exterior Site Stair
SITE-WALK-006  Patio
SITE-WALK-007  Pedestrian Plaza
SITE-WALK-008  Curb Ramp
TXT, 'Civil')),
            $subsystem('SITE-LAND', 'Landscaping and Irrigation', 50, $components(<<<'TXT'
SITE-LAND-001  Lawn
SITE-LAND-002  Tree
SITE-LAND-003  Shrub
SITE-LAND-004  Planting Bed
SITE-LAND-005  Topsoil
SITE-LAND-006  Mulch
SITE-LAND-007  Irrigation System
SITE-LAND-008  Irrigation Controller
SITE-LAND-009  Irrigation Sprinkler
SITE-LAND-010  Landscape Edging
TXT, 'Landscaping')),
            $subsystem('SITE-BOUND', 'Fences, Gates and Retaining Walls', 60, $components(<<<'TXT'
SITE-BOUND-001  Wood Fence
SITE-BOUND-002  Chain-Link Fence
SITE-BOUND-003  Metal Fence
SITE-BOUND-004  Masonry Site Wall
SITE-BOUND-005  Pedestrian Gate
SITE-BOUND-006  Vehicle Gate
SITE-BOUND-007  Automatic Gate Operator
SITE-BOUND-008  Retaining Wall
SITE-BOUND-009  Guardrail
TXT, 'Civil')),
            $subsystem('SITE-STRUCT', 'Site Structures and Amenities', 70, $components(<<<'TXT'
SITE-STRUCT-001  Detached Garage
SITE-STRUCT-002  Storage Shed
SITE-STRUCT-003  Gazebo
SITE-STRUCT-004  Pergola
SITE-STRUCT-005  Waste Enclosure
SITE-STRUCT-006  Bicycle Rack
SITE-STRUCT-007  Site Bench
SITE-STRUCT-008  Site Signage
SITE-STRUCT-009  Playground Equipment
SITE-STRUCT-010  Mail Shelter
TXT, 'General Contractor')),
            $subsystem('SITE-WATER', 'Site Water Utilities', 80, $components(<<<'TXT'
SITE-WATER-001  Site Water-Service Piping
SITE-WATER-002  Water-Service Valve
SITE-WATER-003  Fire Hydrant
SITE-WATER-004  Site Water Meter
SITE-WATER-005  Site Backflow Preventer
TXT, 'Civil')),
            $subsystem('SITE-SAN', 'Site Sanitary Sewerage', 90, $components(<<<'TXT'
SITE-SAN-001  Site Sanitary-Sewer Piping
SITE-SAN-002  Sanitary Manhole
SITE-SAN-003  Sewage Lift Station
SITE-SAN-004  Septic Field
SITE-SAN-005  Sewer Cleanout
TXT, 'Civil')),
            $subsystem('SITE-STORM', 'Site Stormwater Utilities', 100, $components(<<<'TXT'
SITE-STORM-001  Site Storm-Sewer Piping
SITE-STORM-002  Storm Manhole
SITE-STORM-003  Catch-Basin Connection
SITE-STORM-004  Stormwater Tank
SITE-STORM-005  Stormwater Pump
TXT, 'Civil')),
            $subsystem('SITE-ELE', 'Site Electrical and Lighting', 110, $components(<<<'TXT'
SITE-ELE-001  Site Electrical Distribution
SITE-ELE-002  Underground Electrical Duct Bank
SITE-ELE-003  Site Transformer
SITE-ELE-004  Parking-Lot Light
SITE-ELE-005  Walkway Light
SITE-ELE-006  Landscape Light
SITE-ELE-007  Site Electrical Pedestal
TXT, 'Electrical')),
            $subsystem('SITE-COM', 'Site Communications and Security', 120, $components(<<<'TXT'
SITE-COM-001  Site Communications Cabling
SITE-COM-002  Gate Intercom
SITE-COM-003  Site Security Camera
SITE-COM-004  Site Access-Control Device
SITE-COM-005  Site Emergency Call Station
TXT, 'Communications')),
            $subsystem('SITE-WASTE', 'Site Waste Management', 130, $components(<<<'TXT'
SITE-WASTE-001  Waste Bin
SITE-WASTE-002  Recycling Bin
SITE-WASTE-003  Waste Compactor
SITE-WASTE-004  Waste-Enclosure Gate
SITE-WASTE-005  Organic-Waste Container
TXT, 'Waste Management')),
        ],
    ],
    [
        'code' => 'SPC',
        'name' => 'Special Construction and Amenities',
        'slug' => 'special-construction-and-amenities',
        'description' => 'Pools, spas, wellness facilities, athletic facilities, recreational facilities and special structures.',
        'sort_order' => 120,
        'is_core' => false,
        'subsystems' => [
            $subsystem('SPC-POOL', 'Swimming Pools and Spas', 10, $components(<<<'TXT'
SPC-POOL-001  Swimming-Pool Structure
SPC-POOL-002  Swimming-Pool Finish
SPC-POOL-003  Pool Circulation Pump
SPC-POOL-004  Pool Filter
SPC-POOL-005  Pool Heater
SPC-POOL-006  Pool Drain
SPC-POOL-007  Pool Skimmer
SPC-POOL-008  Pool-Chemical System
SPC-POOL-009  Hot Tub or Spa
SPC-POOL-010  Pool Safety Cover
TXT, 'Pool')),
            $subsystem('SPC-WELL', 'Wellness Facilities', 20, $components(<<<'TXT'
SPC-WELL-001  Sauna
SPC-WELL-002  Steam Room
SPC-WELL-003  Steam Generator
SPC-WELL-004  Therapy Pool
TXT, 'Specialty')),
            $subsystem('SPC-REC', 'Athletic and Recreational Facilities', 30, $components(<<<'TXT'
SPC-REC-001  Gymnasium Flooring
SPC-REC-002  Sports-Court Surface
SPC-REC-003  Bleachers
SPC-REC-004  Athletic Equipment
SPC-REC-005  Indoor Play Equipment
TXT, 'Specialty')),
            $subsystem('SPC-MOD', 'Modular and Special Structures', 40, $components(<<<'TXT'
SPC-MOD-001  Modular Building Unit
SPC-MOD-002  Pre-Engineered Building
SPC-MOD-003  Greenhouse
SPC-MOD-004  Solarium
SPC-MOD-005  Temporary Structure
TXT, 'General Contractor')),
        ],
    ],
];
