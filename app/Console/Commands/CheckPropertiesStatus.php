<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;

class CheckPropertiesStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'properties:check-status {--approve= : Approve property by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check properties status and optionally approve them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if we need to approve a property
        if ($propertyId = $this->option('approve')) {
            $property = Property::find($propertyId);
            
            if (!$property) {
                $this->error("Property with ID {$propertyId} not found!");
                return 1;
            }
            
            $property->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            
            $this->info("✅ Property {$property->property_code} ({$property->property_name}) has been APPROVED!");
            $this->line("The client can now schedule an inspection.");
            return 0;
        }
        
        // Display all properties
        $this->info("=== PROPERTIES STATUS CHECK ===");
        $this->newLine();
        
        $properties = Property::select('id', 'property_code', 'property_name', 'status', 'user_id')->get();
        
        if ($properties->isEmpty()) {
            $this->warn("No properties found in database.");
            return 0;
        }
        
        $this->info("Found {$properties->count()} properties:");
        $this->newLine();
        
        $tableData = [];
        foreach ($properties as $property) {
            $statusIcon = match($property->status) {
                'approved' => '✅',
                'pending_approval' => '⏳',
                'rejected' => '❌',
                'awaiting_inspection' => '🔍',
                default => '❓',
            };
            
            $tableData[] = [
                $property->id,
                $property->property_code,
                $property->property_name,
                $statusIcon . ' ' . $property->status,
                $property->user_id,
            ];
        }
        
        $this->table(
            ['ID', 'Code', 'Name', 'Status', 'User ID'],
            $tableData
        );
        
        $this->newLine();
        $this->info("Status Guide:");
        $this->line("  ⏳ pending_approval - Needs admin approval");
        $this->line("  ✅ approved - Can schedule inspection");
        $this->line("  ❌ rejected - Rejected by admin");
        $this->line("  🔍 awaiting_inspection - Inspection scheduled");
        
        $this->newLine();
        $this->comment("To approve a property, run:");
        $this->line("  php artisan properties:check-status --approve=[PROPERTY_ID]");
        
        return 0;
    }
}
