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
        ->where('inspection_fee_status', 'paid')
        ->where('status', 'scheduled')
        ->first();
    
    if ($inspection) {
        return response()->json([
            'inspection' => [
                'scheduled_date' => $inspection->scheduled_date->format('M d, Y \a\t g:i A'),
                'fee_amount' => number_format($inspection->inspection_fee_amount, 2),
                'notes' => $inspection->notes,
            ]
        ]);
    }
    
    return response()->json(['inspection' => null]);
});
