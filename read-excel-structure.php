<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $excelFile = __DIR__ . '/ETOGO PRICING CALCULATOR FOR BOTH COMERCIAL AND RESIDENTIAL UNITS..xlsx';
    
    echo "Loading Excel file...\n";
    echo "File: {$excelFile}\n";
    echo str_repeat("=", 80) . "\n\n";
    
    // Load the spreadsheet
    $spreadsheet = IOFactory::load($excelFile);
    
    // Get all sheet names
    $sheetNames = $spreadsheet->getSheetNames();
    echo "Total Sheets: " . count($sheetNames) . "\n";
    echo "Sheet Names:\n";
    foreach ($sheetNames as $index => $name) {
        echo "  [" . ($index + 1) . "] {$name}\n";
    }
    echo "\n" . str_repeat("=", 80) . "\n\n";
    
    // Loop through each sheet and display structure
    foreach ($sheetNames as $sheetIndex => $sheetName) {
        echo "SHEET [" . ($sheetIndex + 1) . "]: {$sheetName}\n";
        echo str_repeat("-", 80) . "\n";
        
        $sheet = $spreadsheet->getSheetByName($sheetName);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        echo "Dimensions: {$highestColumn}{$highestRow} (Columns: {$highestColumnIndex}, Rows: {$highestRow})\n";
        echo "Data Range: A1:{$highestColumn}{$highestRow}\n\n";
        
        // Show first 20 rows or less
        $rowsToShow = min(20, $highestRow);
        echo "First {$rowsToShow} rows:\n\n";
        
        for ($row = 1; $row <= $rowsToShow; $row++) {
            $rowData = [];
            for ($col = 1; $col <= min(10, $highestColumnIndex); $col++) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $cellValue = $sheet->getCell($column . $row)->getValue();
                
                // Clean up the value for display
                if (is_string($cellValue)) {
                    $cellValue = trim($cellValue);
                    if (strlen($cellValue) > 30) {
                        $cellValue = substr($cellValue, 0, 27) . '...';
                    }
                } elseif (is_null($cellValue)) {
                    $cellValue = '';
                }
                
                $rowData[] = $cellValue;
            }
            
            // Only display rows that have some content
            if (array_filter($rowData, fn($v) => !empty($v))) {
                echo "Row {$row}: " . json_encode($rowData, JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
        
        if ($highestColumnIndex > 10) {
            echo "\n(Showing first 10 columns only. Total columns: {$highestColumnIndex})\n";
        }
        
        if ($highestRow > 20) {
            echo "\n(Showing first 20 rows only. Total rows: {$highestRow})\n";
        }
        
        echo "\n" . str_repeat("=", 80) . "\n\n";
    }
    
    echo "Analysis complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
