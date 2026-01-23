<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $excelFile = __DIR__ . '/ETOGO PRICING CALCULATOR FOR BOTH COMERCIAL AND RESIDENTIAL UNITS..xlsx';
    
    echo "DETAILED ANALYSIS OF ETOGO PRICING CALCULATOR\n";
    echo str_repeat("=", 100) . "\n\n";
    
    $spreadsheet = IOFactory::load($excelFile);
    
    // ==================== INPUTS SHEET ====================
    echo "SHEET 1: INPUTS\n";
    echo str_repeat("-", 100) . "\n";
    $inputs = $spreadsheet->getSheetByName('Inputs');
    
    for ($row = 1; $row <= 28; $row++) {
        $a = $inputs->getCell("A{$row}")->getValue();
        $b = $inputs->getCell("B{$row}")->getValue();
        $c = $inputs->getCell("C{$row}")->getValue();
        $d = $inputs->getCell("D{$row}")->getValue();
        if (!empty($a) || !empty($b)) {
            echo "Row {$row}: [{$a}] | [{$b}] | [{$c}] | [{$d}]\n";
        }
    }
    
    echo "\n" . str_repeat("=", 100) . "\n\n";
    
    // ==================== CPI SCORING SHEET ====================
    echo "SHEET 2: CPI_SCORING\n";
    echo str_repeat("-", 100) . "\n";
    $cpi = $spreadsheet->getSheetByName('CPI_Scoring');
    
    for ($row = 1; $row <= 46; $row++) {
        $a = $cpi->getCell("A{$row}")->getValue();
        $b = $cpi->getCell("B{$row}")->getValue();
        $c = $cpi->getCell("C{$row}")->getValue();
        $d = $cpi->getCell("D{$row}")->getValue();
        if (!empty($a) || !empty($b)) {
            echo "Row {$row}: [{$a}] | [{$b}] | [{$c}] | [{$d}]\n";
        }
    }
    
    echo "\n" . str_repeat("=", 100) . "\n\n";
    
    // ==================== CALCULATOR SHEET ====================
    echo "SHEET 3: CALCULATOR\n";
    echo str_repeat("-", 100) . "\n";
    $calc = $spreadsheet->getSheetByName('Calculator');
    
    for ($row = 1; $row <= 24; $row++) {
        $a = $calc->getCell("A{$row}")->getValue();
        $b = $calc->getCell("B{$row}")->getValue();
        $c = $calc->getCell("C{$row}")->getValue();
        if (!empty($a) || !empty($b)) {
            echo "Row {$row}: [{$a}] | [{$b}] | [{$c}]\n";
        }
    }
    
    echo "\n" . str_repeat("=", 100) . "\n\n";
    
    // ==================== LOOKUPS SHEET ====================
    echo "SHEET 4: LOOKUPS\n";
    echo str_repeat("-", 100) . "\n";
    $lookups = $spreadsheet->getSheetByName('Lookups');
    
    for ($row = 1; $row <= 34; $row++) {
        $a = $lookups->getCell("A{$row}")->getValue();
        $b = $lookups->getCell("B{$row}")->getValue();
        $c = $lookups->getCell("C{$row}")->getValue();
        $d = $lookups->getCell("D{$row}")->getValue();
        $e = $lookups->getCell("E{$row}")->getValue();
        if (!empty($a) || !empty($b) || !empty($d)) {
            echo "Row {$row}: [{$a}] | [{$b}] | [{$c}] | [{$d}] | [{$e}]\n";
        }
    }
    
    echo "\n" . str_repeat("=", 100) . "\n";
    echo "COMPLETE!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
