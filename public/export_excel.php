<?php
// public/export_excel.php

require __DIR__ . '/../config/db.php';

// Include Composer autoloader for PhpSpreadsheet
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// 1) Grab month/year from GET
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$selectedYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

// 2) Query DB for SALE & PURCHASE
$saleInvoices = [];
$purchaseInvoices = [];

try {
    $stmtSale = $pdo->prepare("
        SELECT *
          FROM invoices
         WHERE invoice_type = 'SALE'
           AND MONTH(invoice_date) = :m
           AND YEAR(invoice_date)  = :y
         ORDER BY invoice_date ASC
    ");
    $stmtSale->execute([':m' => $selectedMonth, ':y' => $selectedYear]);
    $saleInvoices = $stmtSale->fetchAll(PDO::FETCH_ASSOC);

    $stmtPurchase = $pdo->prepare("
        SELECT *
          FROM invoices
         WHERE invoice_type = 'PURCHASE'
           AND MONTH(invoice_date) = :m
           AND YEAR(invoice_date)  = :y
         ORDER BY invoice_date ASC
    ");
    $stmtPurchase->execute([':m' => $selectedMonth, ':y' => $selectedYear]);
    $purchaseInvoices = $stmtPurchase->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}

// 3) Create Spreadsheet object
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
            ->setCreator("My Invoicing App")
            ->setTitle("Invoices Export");

// ----- SALE SHEET -----
$saleSheet = $spreadsheet->setActiveSheetIndex(0);
$saleSheet->setTitle('Sale Invoices');

// Define headers for SALE
$headersSale = [
    'ID', 'Invoice Number', 'Invoice Date', 'Customer Name',
    'GSTIN', 'Taxable Amount', 'CGST', 'SGST', 'Total'
];

// Write SALE headers in row 1
$rowNum = 1;
$colNum = 1;
foreach ($headersSale as $header) {
    $colLetter = Coordinate::stringFromColumnIndex($colNum);
    $saleSheet->setCellValue($colLetter.$rowNum, $header);
    $colNum++;
}

// Write SALE data starting row 2
$rowNum = 2;
foreach ($saleInvoices as $inv) {
    // Reset column to 1 for each row
    $colNum = 1;

    // 1) ID
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['id']);

    // 2) Invoice Number
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['invoice_number']);

    // 3) Invoice Date
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['invoice_date']);

    // 4) Customer Name
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['customer_name']);

    // 5) GSTIN
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['customer_gstin']);

    // 6) Taxable Amount
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['taxable_amount']);

    // 7) CGST
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['cgst']);

    // 8) SGST
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['sgst']);

    // 9) Total
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['total']);

    $rowNum++;
}

// ----- PURCHASE SHEET -----
$purchaseSheet = $spreadsheet->createSheet();
$purchaseSheet->setTitle('Purchase Invoices');

// Define headers for PURCHASE
$headersPur = [
    'ID', 'Invoice Number', 'Invoice Date', 'Customer Name',
    'GSTIN', 'Commodity', 'Taxable Amount', 'CGST', 'SGST', 'Total'
];

// Write PURCHASE headers
$rowNum = 1;
$colNum = 1;
foreach ($headersPur as $header) {
    $colLetter = Coordinate::stringFromColumnIndex($colNum);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $header);
    $colNum++;
}

// Write PURCHASE data
$rowNum = 2;
foreach ($purchaseInvoices as $inv) {
    $colNum = 1;

    // 1) ID
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['id']);

    // 2) Invoice Number
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['invoice_number']);

    // 3) Invoice Date
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['invoice_date']);

    // 4) Customer Name
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['customer_name']);

    // 5) GSTIN
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['customer_gstin']);

    // 6) Commodity
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['commodity']);

    // 7) Taxable Amount
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['taxable_amount']);

    // 8) CGST
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['cgst']);

    // 9) SGST
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['sgst']);

    // 10) Total
    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['total']);

    $rowNum++;
}

// Optional: auto-size columns for each sheet
foreach ([$saleSheet, $purchaseSheet] as $sheet) {
    $highestCol = $sheet->getHighestColumn();
    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

    for ($col = 1; $col <= $highestColIndex; $col++) {
        $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
    }
}

// 4) Output as XLSX
$monthName = date('F', mktime(0,0,0, $selectedMonth, 1));
$fileName = "Invoices_{$monthName}-{$selectedYear}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$fileName\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
