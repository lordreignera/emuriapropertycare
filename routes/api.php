<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Property;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Get property inspection details
Route::get('/properties/{property}/inspection-details', function (Property $property) {
    $inspection = $property->inspections()
        ->whereIn('status', ['scheduled', 'in_progress'])
        ->latest('id')
        ->first();
    
    if ($inspection) {
        return response()->json([
            'inspection' => [
                'scheduled_date' => optional($inspection->scheduled_date)->format('M d, Y \a\t g:i A') ?? 'Not scheduled yet',
                'fee_amount' => number_format((float) ($inspection->inspection_fee_amount ?? 0), 2),
                'notes' => $inspection->summary,
            ]
        ]);
    }
    
    return response()->json(['inspection' => null]);
});
