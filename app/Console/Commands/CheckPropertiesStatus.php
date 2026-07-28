<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;

class CheckPropertiesStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'properties:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check property lifecycle status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== PROPERTIES STATUS CHECK ===');
        $this->newLine();

        $properties = Property::select('id', 'property_code', 'property_name', 'status', 'user_id')->get();

        if ($properties->isEmpty()) {
            $this->warn('No properties found in database.');
            return self::SUCCESS;
        }

        $this->info("Found {$properties->count()} properties:");
        $this->newLine();

        $tableData = [];
        foreach ($properties as $property) {
            $statusIcon = match ($property->status) {
                'registered' => '[registered]',
                'awaiting_inspection' => '[awaiting]',
                'in_assessment' => '[assessment]',
                'assessed' => '[assessed]',
                'archived' => '[archived]',
                default => '[unknown]',
            };

            $tableData[] = [
                $property->id,
                $property->property_code,
                $property->property_name,
                $statusIcon . ' ' . str_replace('_', ' ', (string) $property->status),
                $property->user_id,
            ];
        }

        $this->table(
            ['ID', 'Code', 'Name', 'Status', 'User ID'],
            $tableData
        );

        $this->newLine();
        $this->info('Status Guide:');
        $this->line('  registered - Client added the property and can schedule inspection');
        $this->line('  awaiting_inspection - Inspection is scheduled and paid');
        $this->line('  in_assessment - Inspector is assessing the property');
        $this->line('  assessed - PHAR assessment/report journey is complete');
        $this->line('  archived - Property is no longer active');

        return self::SUCCESS;
    }
}
