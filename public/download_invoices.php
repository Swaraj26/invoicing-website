<?php
// public/download_invoices.php
require_once __DIR__ . '/../config/db.php';

// Include Composer's autoloader for mPDF
require __DIR__ . '/../vendor/autoload.php'; 


use Mpdf\Mpdf;

// 1. Grab month/year from GET
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$selectedYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

// 2. Fetch invoices (similar to index.php logic)
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

// 3. Build HTML content for the PDF
// We'll reuse the same logic from index.php to display sale & purchase tables with totals

// Helper function to create an HTML table for SALE invoices
function buildSaleTable($saleInvoices) {
    $sumTaxable = 0; $sumCgst = 0; $sumSgst = 0; $sumTotal = 0;
    $html = '<h2>SALE Invoices</h2>';
    if (count($saleInvoices) > 0) {
        $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width:100%;">
                    <tr>
                        <th>ID</th>
                        <th>Invoice Number</th>
                        <th>Date</th>
                        <th>Customer Name</th>
                        <th>GSTIN</th>
                        <th>Taxable Amount</th>
                        <th>CGST</th>
                        <th>SGST</th>
                        <th>Total</th>
                    </tr>';

        foreach ($saleInvoices as $inv) {
            $sumTaxable += $inv['taxable_amount'];
            $sumCgst    += $inv['cgst'];
            $sumSgst    += $inv['sgst'];
            $sumTotal   += $inv['total'];

            $html .= '<tr>
                <td>'.$inv['id'].'</td>
                <td>'.htmlspecialchars($inv['invoice_number']).'</td>
                <td>'.$inv['invoice_date'].'</td>
                <td>'.htmlspecialchars($inv['customer_name']).'</td>
                <td>'.htmlspecialchars($inv['customer_gstin']).'</td>
                <td>'.$inv['taxable_amount'].'</td>
                <td>'.$inv['cgst'].'</td>
                <td>'.$inv['sgst'].'</td>
                <td>'.$inv['total'].'</td>
            </tr>';
        }
        
        // Totals row
        $html .= '<tr style="font-weight:bold;">
            <td colspan="5" align="right">TOTAL:</td>
            <td>'.number_format($sumTaxable, 2).'</td>
            <td>'.number_format($sumCgst, 2).'</td>
            <td>'.number_format($sumSgst, 2).'</td>
            <td>'.number_format($sumTotal, 2).'</td>
        </tr>';

        $html .= '</table>';
    } else {
        $html .= '<p>No SALE invoices found for this month/year.</p>';
    }
    return $html;
}

// Helper function to create an HTML table for PURCHASE invoices
function buildPurchaseTable($purchaseInvoices) {
    $sumTaxable = 0; $sumCgst = 0; $sumSgst = 0; $sumTotal = 0;
    $html = '<h2>PURCHASE Invoices</h2>';
    if (count($purchaseInvoices) > 0) {
        $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width:100%;">
                    <tr>
                        <th>ID</th>
                        <th>Invoice Number</th>
                        <th>Date</th>
                        <th>Customer Name</th>
                        <th>GSTIN</th>
                        <th>Commodity</th>
                        <th>Taxable Amount</th>
                        <th>CGST</th>
                        <th>SGST</th>
                        <th>Total</th>
                    </tr>';

        foreach ($purchaseInvoices as $inv) {
            $sumTaxable += $inv['taxable_amount'];
            $sumCgst    += $inv['cgst'];
            $sumSgst    += $inv['sgst'];
            $sumTotal   += $inv['total'];

            $html .= '<tr>
                <td>'.$inv['id'].'</td>
                <td>'.htmlspecialchars($inv['invoice_number']).'</td>
                <td>'.$inv['invoice_date'].'</td>
                <td>'.htmlspecialchars($inv['customer_name']).'</td>
                <td>'.htmlspecialchars($inv['customer_gstin']).'</td>
                <td>'.htmlspecialchars($inv['commodity']).'</td>
                <td>'.$inv['taxable_amount'].'</td>
                <td>'.$inv['cgst'].'</td>
                <td>'.$inv['sgst'].'</td>
                <td>'.$inv['total'].'</td>
            </tr>';
        }
        
        // Totals row
        $html .= '<tr style="font-weight:bold;">
            <td colspan="6" align="right">TOTAL:</td>
            <td>'.number_format($sumTaxable, 2).'</td>
            <td>'.number_format($sumCgst, 2).'</td>
            <td>'.number_format($sumSgst, 2).'</td>
            <td>'.number_format($sumTotal, 2).'</td>
        </tr>';

        $html .= '</table>';
    } else {
        $html .= '<p>No PURCHASE invoices found for this month/year.</p>';
    }
    return $html;
}

// Combine into one HTML string
$monthName = date('F', mktime(0,0,0, $selectedMonth, 1)); 
$heading = "<h1>Invoices for $monthName $selectedYear</h1>";

$htmlContent = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoices PDF</title>
</head>
<body>';

$htmlContent .= $heading;
$htmlContent .= buildSaleTable($saleInvoices);
$htmlContent .= '<hr style="margin:40px 0;">';
$htmlContent .= buildPurchaseTable($purchaseInvoices);

$htmlContent .= '</body></html>';

// 4. Generate PDF with mPDF
$mpdf = new Mpdf();
$mpdf->WriteHTML($htmlContent);

// 5. Output to browser as download
$fileName = "Invoices_$monthName-$selectedYear.pdf";
$mpdf->Output($fileName, 'D');  // 'D' = force download
