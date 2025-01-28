<?php
// public/export_excel.php

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../vendor/autoload.php'; // PhpSpreadsheet autoloader

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// 1) Grab month/year
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$selectedYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

// 2) Query DB
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

// 3) Create a new Spreadsheet
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
            ->setCreator("Invoicing App")
            ->setTitle("Monthly Invoices Export");

// ========== SALE SHEET ==========
$saleSheet = $spreadsheet->setActiveSheetIndex(0);
$saleSheet->setTitle('Sale Invoices');

$headersSale = [
    'ID', 'Invoice No.', 'Date', 'Customer', 'GSTIN',
    'Taxable', 'CGST', 'SGST', 'Total'
];

$rowNum = 1;
$colNum = 1;

// Write the header row
foreach ($headersSale as $header) {
    $colLetter = Coordinate::stringFromColumnIndex($colNum);
    $saleSheet->setCellValue($colLetter.$rowNum, $header);
    $colNum++;
}

// Make the header row bold
$headerRange = "A1:I1"; // 9 columns: A through I
$saleSheet->getStyle($headerRange)->getFont()->setBold(true);

// Write data + compute totals
$sumTaxable = 0;
$sumCgst    = 0;
$sumSgst    = 0;
$sumTotal   = 0;

$rowNum = 2; // data starts on row 2
foreach ($saleInvoices as $inv) {
    $colNum = 1;

    $sumTaxable += $inv['taxable_amount'];
    $sumCgst    += $inv['cgst'];
    $sumSgst    += $inv['sgst'];
    $sumTotal   += $inv['total'];

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['id']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['invoice_number']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['invoice_date']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['customer_name']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['customer_gstin']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['taxable_amount']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['cgst']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['sgst']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $saleSheet->setCellValue($colLetter.$rowNum, $inv['total']);

    $rowNum++;
}

// Totals row (bold)
$colLetter = "E"; // for example, we want "TOTAL:" to appear in col E
$saleSheet->setCellValue("{$colLetter}{$rowNum}", "TOTAL:");
$saleSheet->getStyle("{$colLetter}{$rowNum}")->getFont()->setBold(true);
$saleSheet->getStyle("{$colLetter}{$rowNum}")->getAlignment()->setHorizontal('right');

// Now the numeric totals go in columns F, G, H, I
$saleSheet->setCellValue("F{$rowNum}", $sumTaxable);
$saleSheet->setCellValue("G{$rowNum}", $sumCgst);
$saleSheet->setCellValue("H{$rowNum}", $sumSgst);
$saleSheet->setCellValue("I{$rowNum}", $sumTotal);

// Make the numeric cells bold
$saleSheet->getStyle("F{$rowNum}:I{$rowNum}")->getFont()->setBold(true);

// ========== PURCHASE SHEET ==========
$purchaseSheet = $spreadsheet->createSheet();
$purchaseSheet->setTitle('Purchase Invoices');

$headersPur = [
    'ID', 'Invoice No.', 'Date', 'Customer', 'GSTIN',
    'Commodity', 'Taxable', 'CGST', 'SGST', 'Total'
];

$rowNum = 1;
$colNum = 1;
foreach ($headersPur as $header) {
    $colLetter = Coordinate::stringFromColumnIndex($colNum);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $header);
    $colNum++;
}

// Bold the header row
$purchaseSheet->getStyle("A1:J1")->getFont()->setBold(true);

$sumTaxablePur = 0;
$sumCgstPur = 0;
$sumSgstPur = 0;
$sumTotalPur = 0;

$rowNum = 2;
foreach ($purchaseInvoices as $inv) {
    $colNum = 1;

    $sumTaxablePur += $inv['taxable_amount'];
    $sumCgstPur    += $inv['cgst'];
    $sumSgstPur    += $inv['sgst'];
    $sumTotalPur   += $inv['total'];

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['id']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['invoice_number']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['invoice_date']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['customer_name']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['customer_gstin']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['commodity']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['taxable_amount']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['cgst']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['sgst']);

    $colLetter = Coordinate::stringFromColumnIndex($colNum++);
    $purchaseSheet->setCellValue($colLetter.$rowNum, $inv['total']);

    $rowNum++;
}

// Totals row
$colLetter = "F";  // "TOTAL:" label appears in column F (just an example)
$purchaseSheet->setCellValue("{$colLetter}{$rowNum}", "TOTAL:");
$purchaseSheet->getStyle("{$colLetter}{$rowNum}")->getFont()->setBold(true);
$purchaseSheet->getStyle("{$colLetter}{$rowNum}")->getAlignment()->setHorizontal('right');

$purchaseSheet->setCellValue("G{$rowNum}", $sumTaxablePur);
$purchaseSheet->setCellValue("H{$rowNum}", $sumCgstPur);
$purchaseSheet->setCellValue("I{$rowNum}", $sumSgstPur);
$purchaseSheet->setCellValue("J{$rowNum}", $sumTotalPur);

// Make them bold
$purchaseSheet->getStyle("G{$rowNum}:J{$rowNum}")->getFont()->setBold(true);

// Optional auto-size columns for each sheet
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
