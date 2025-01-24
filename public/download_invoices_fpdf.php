<?php
// public/download_invoices_fpdf.php

require __DIR__ . '/../config/db.php';

// 1) Include FPDF's main file
require __DIR__ . '/../fpdf/fpdf.php';  
// (Adjust path if your folder is different)

// 2) Get month/year from GET
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$selectedYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

// 3) Query DB for SALE and PURCHASE (similar to your index code)
$saleInvoices = [];
$purchaseInvoices = [];

try {
    // SALE
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

    // PURCHASE
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
    die("Error fetching invoices: " . $e->getMessage());
}

// 4) Prepare data for the PDF
$months = [
    1 => "January",   2 => "February", 3 => "March",
    4 => "April",     5 => "May",      6 => "June",
    7 => "July",      8 => "August",   9 => "September",
    10 => "October",  11 => "November",12 => "December"
];
$monthName = isset($months[$selectedMonth]) ? $months[$selectedMonth] : $selectedMonth;
$pdfTitle  = "Invoices for $monthName $selectedYear";

// 5) Create PDF using FPDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Title
$pdf->Cell(0, 10, $pdfTitle, 0, 1, 'C');  // full-width cell, center-aligned
$pdf->Ln(5);  // new line (5 pts of space)

// ==================== SALE INVOICES ====================
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, "SALE Invoices", 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);

if (count($saleInvoices) > 0) {
    // Table header
    // We'll define some column widths so data lines up
    $pdf->SetFillColor(200,200,200); // light gray background for header
    $pdf->Cell(15, 8, "ID", 1, 0, 'C', true);
    $pdf->Cell(40, 8, "Inv. Number", 1, 0, 'C', true);
    $pdf->Cell(28, 8, "Date", 1, 0, 'C', true);
    $pdf->Cell(38, 8, "Customer", 1, 0, 'C', true);
    $pdf->Cell(28, 8, "Taxable", 1, 0, 'C', true);
    $pdf->Cell(20, 8, "CGST", 1, 0, 'C', true);
    $pdf->Cell(20, 8, "SGST", 1, 0, 'C', true);
    $pdf->Cell(20, 8, "Total", 1, 1, 'C', true);

    // Summation vars
    $sumTaxableSale = 0;
    $sumCgstSale = 0;
    $sumSgstSale = 0;
    $sumTotalSale = 0;

    foreach ($saleInvoices as $inv) {
        $sumTaxableSale += $inv['taxable_amount'];
        $sumCgstSale    += $inv['cgst'];
        $sumSgstSale    += $inv['sgst'];
        $sumTotalSale   += $inv['total'];

        // Each row
        $pdf->Cell(15, 8, $inv['id'], 1, 0, 'C');
        $pdf->Cell(40, 8, $inv['invoice_number'], 1, 0, 'C');
        $pdf->Cell(28, 8, $inv['invoice_date'], 1, 0, 'C');
        $pdf->Cell(38, 8, $inv['customer_name'], 1, 0, 'C');
        $pdf->Cell(28, 8, $inv['taxable_amount'], 1, 0, 'C');
        $pdf->Cell(20, 8, $inv['cgst'], 1, 0, 'C');
        $pdf->Cell(20, 8, $inv['sgst'], 1, 0, 'C');
        $pdf->Cell(20, 8, $inv['total'], 1, 1, 'C');
    }

    // Totals row
    $pdf->Cell(15+40+28+38, 8, "TOTAL:", 1, 0, 'R');
    $pdf->Cell(28, 8, number_format($sumTaxableSale, 2), 1, 0, 'C');
    $pdf->Cell(20, 8, number_format($sumCgstSale, 2), 1, 0, 'C');
    $pdf->Cell(20, 8, number_format($sumSgstSale, 2), 1, 0, 'C');
    $pdf->Cell(20, 8, number_format($sumTotalSale, 2), 1, 1, 'C');

    $pdf->Ln(5);  // extra space before next section
} else {
    $pdf->Cell(0, 8, "No SALE invoices found for this month/year.", 0, 1);
    $pdf->Ln(5);
}

// ==================== PURCHASE INVOICES ====================
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, "PURCHASE Invoices", 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);

if (count($purchaseInvoices) > 0) {
    // Table header
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(15, 8, "ID", 1, 0, 'C', true);
    $pdf->Cell(30, 8, "Inv. Number", 1, 0, 'C', true);
    $pdf->Cell(25, 8, "Date", 1, 0, 'C', true);
    $pdf->Cell(35, 8, "Customer", 1, 0, 'C', true);
    $pdf->Cell(40, 8, "Commodity", 1, 0, 'C', true);
    $pdf->Cell(20, 8, "CGST", 1, 0, 'C', true);
    $pdf->Cell(20, 8, "SGST", 1, 0, 'C', true);
    $pdf->Cell(20, 8, "Total", 1, 1, 'C', true);

    $sumTaxablePur = 0;
    $sumCgstPur    = 0;
    $sumSgstPur    = 0;
    $sumTotalPur   = 0;

    foreach ($purchaseInvoices as $inv) {
        $sumTaxablePur += $inv['taxable_amount'];
        $sumCgstPur    += $inv['cgst'];
        $sumSgstPur    += $inv['sgst'];
        $sumTotalPur   += $inv['total'];

        $pdf->Cell(15, 8, $inv['id'], 1, 0, 'C');
        $pdf->Cell(30, 8, $inv['invoice_number'], 1, 0, 'C');
        $pdf->Cell(25, 8, $inv['invoice_date'], 1, 0, 'C');
        $pdf->Cell(35, 8, $inv['customer_name'], 1, 0, 'C');
        // Commodity can be big, but for example’s sake, we’ll do 40 width
        $pdf->Cell(40, 8, $inv['commodity'], 1, 0, 'C');
        $pdf->Cell(20, 8, $inv['cgst'], 1, 0, 'C');
        $pdf->Cell(20, 8, $inv['sgst'], 1, 0, 'C');
        $pdf->Cell(20, 8, $inv['total'], 1, 1, 'C');
    }

    // Totals row
    $pdf->Cell(15+30+25+35+40, 8, "TOTAL:", 1, 0, 'R');
    // We did not display 'taxable_amount' in each row above. 
    // If you want to show it, you'd add more columns.
    // For simplicity, let's skip the direct 'taxable_amount' sum for purchase 
    // or store it in sumTaxablePur (whatever your logic needs).

    $pdf->Cell(20, 8, number_format($sumCgstPur, 2), 1, 0, 'C');
    $pdf->Cell(20, 8, number_format($sumSgstPur, 2), 1, 0, 'C');
    $pdf->Cell(20, 8, number_format($sumTotalPur, 2), 1, 1, 'C');

    $pdf->Ln(5);
} else {
    $pdf->Cell(0, 8, "No PURCHASE invoices found for this month/year.", 0, 1);
}

// 6) Output the PDF as a download
$fileName = "Invoices_{$monthName}-{$selectedYear}.pdf";
$pdf->Output('D', $fileName);
// 'D' forces download, 
// or use 'I' to display inline in the browser
