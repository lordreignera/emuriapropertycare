<?php
/**
 * Debug script to check tool stock calculations
 * Run: php artisan tinker < test-tool-stock-debug.php
 * Or: php test-tool-stock-debug.php in Laravel environment
 */

use App\Models\ToolSetting;
use App\Models\InspectionToolAssignment;

echo "=== TOOL STOCK DEBUG ===\n\n";

// Get all tools with assignments
$tools = ToolSetting::with('assignments')->get();

foreach ($tools as $tool) {
    echo "Tool: {$tool->tool_name}\n";
    echo "  Total Quantity: {$tool->quantity}\n";
    
    // Check deployments
    $deployed = InspectionToolAssignment::where('tool_setting_id', $tool->id)
        ->whereNull('returned_at')
        ->where('quantity', '>', 0)
        ->get();
    
    echo "  Unreturned Assignments: " . count($deployed) . "\n";
    
    $totalDeployed = $deployed->sum('quantity');
    echo "  Total Deployed: {$totalDeployed}\n";
    
    // Check returned
    $returned = InspectionToolAssignment::where('tool_setting_id', $tool->id)
        ->whereNotNull('returned_at')
        ->get();
    
    echo "  Returned Assignments: " . count($returned) . "\n";
    
    // Check using model method
    $calculatedDeployed = $tool->deployedQuantity();
    $calculatedRemaining = $tool->remainingQuantity();
    
    echo "  Model deployedQuantity(): {$calculatedDeployed}\n";
    echo "  Model remainingQuantity(): {$calculatedRemaining}\n";
    
    // Show assignments
    if (count($deployed) > 0) {
        echo "  Unreturned assignments:\n";
        foreach ($deployed as $assign) {
            echo "    - ID: {$assign->id}, Qty: {$assign->quantity}, tool_setting_id: {$assign->tool_setting_id}\n";
        }
    }
    
    if (count($returned) > 0) {
        echo "  Returned assignments:\n";
        foreach ($returned as $assign) {
            echo "    - ID: {$assign->id}, Qty: {$assign->quantity}, tool_setting_id: {$assign->tool_setting_id}, returned_at: {$assign->returned_at}\n";
        }
    }
    
    echo "\n";
}

echo "=== END DEBUG ===\n";
