<?php
// public/download_pdf.php

require __DIR__ . '/../config/db.php';

// Include Composer's autoloader for mPDF
require __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;

// 1) Grab month/year from GET
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$selectedYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

// 2) Query the DB
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
    die("Error fetching invoices: " . $e->getMessage());
}

// 3) Build the HTML content
// We'll keep it simple with inline CSS to ensure everything fits nicely.
$monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));
$title = "Invoices for {$monthName} {$selectedYear}";

// We'll create a function to build a table for each invoice type
function buildTable($title, $invoices, $isPurchase = false) {
    // Decide columns based on purchase or sale
    $commodityCol = $isPurchase ? "<th style='width:120px;'>Commodity</th>" : "";
    $commodityCell = $isPurchase ? "<td style='border:1px solid #000; padding:6px;'>{COMMODITY}</td>" : "";

    // Start HTML
    $html = "<h3 style='margin-top:20px;'>{$title}</h3>";
    if (count($invoices) === 0) {
        $html .= "<p>No {$title} found for this month/year.</p>";
        return $html;
    }

    // Table header
    $html .= "<table style='width:100%; border-collapse:collapse; margin-top:10px;'>
                <thead>
                  <tr style='background:#f0f0f0;'>
                    <th style='border:1px solid #000; padding:6px;'>ID</th>
                    <th style='border:1px solid #000; padding:6px;'>Invoice No.</th>
                    <th style='border:1px solid #000; padding:6px;'>Date</th>
                    <th style='border:1px solid #000; padding:6px;'>Customer</th>
                    <th style='border:1px solid #000; padding:6px;'>GSTIN</th>
                    {$commodityCol}
                    <th style='border:1px solid #000; padding:6px;'>Taxable</th>
                    <th style='border:1px solid #000; padding:6px;'>CGST</th>
                    <th style='border:1px solid #000; padding:6px;'>SGST</th>
                    <th style='border:1px solid #000; padding:6px;'>Total</th>
                  </tr>
                </thead>
                <tbody>
            ";

    // Summation
    $sumTaxable = 0;
    $sumCgst = 0;
    $sumSgst = 0;
    $sumTotal = 0;

    // Table rows
    foreach ($invoices as $inv) {
        $sumTaxable += $inv['taxable_amount'];
        $sumCgst    += $inv['cgst'];
        $sumSgst    += $inv['sgst'];
        $sumTotal   += $inv['total'];

        $commodityCellReplaced = $isPurchase
            ? str_replace('{COMMODITY}', htmlspecialchars($inv['commodity']), $commodityCell)
            : "";

        $html .= "<tr>
                    <td style='border:1px solid #000; padding:6px;'>{$inv['id']}</td>
                    <td style='border:1px solid #000; padding:6px;'>".htmlspecialchars($inv['invoice_number'])."</td>
                    <td style='border:1px solid #000; padding:6px;'>{$inv['invoice_date']}</td>
                    <td style='border:1px solid #000; padding:6px;'>".htmlspecialchars($inv['customer_name'])."</td>
                    <td style='border:1px solid #000; padding:6px;'>".htmlspecialchars($inv['customer_gstin'])."</td>
                    {$commodityCellReplaced}
                    <td style='border:1px solid #000; padding:6px;'>{$inv['taxable_amount']}</td>
                    <td style='border:1px solid #000; padding:6px;'>{$inv['cgst']}</td>
                    <td style='border:1px solid #000; padding:6px;'>{$inv['sgst']}</td>
                    <td style='border:1px solid #000; padding:6px;'>{$inv['total']}</td>
                  </tr>";
    }

    // Totals row
    $colspan = $isPurchase ? 5 : 5; // ID, Invoice, Date, Customer, GSTIN => +1 if commodity
    if ($isPurchase) $colspan++;    // We add 1 more for commodity column
    $html .= "<tr style='font-weight:bold; background:#f9f9f9;'>
                <td colspan='{$colspan}' style='border:1px solid #000; text-align:right; padding:6px;'>
                  TOTAL:
                </td>
                <td style='border:1px solid #000; padding:6px;'>{$sumTaxable}</td>
                <td style='border:1px solid #000; padding:6px;'>{$sumCgst}</td>
                <td style='border:1px solid #000; padding:6px;'>{$sumSgst}</td>
                <td style='border:1px solid #000; padding:6px;'>{$sumTotal}</td>
              </tr>";

    $html .= "</tbody></table>";
    return $html;
}

// Combine everything
$html = "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9pt;
    margin:0;
    padding:0;
  }
  h2, h3 {
    margin:0;
    padding:0;
  }
</style>
</head>
<body>
  <h2>{$title}</h2>
  ";

// Build SALE table
$html .= buildTable("SALE Invoices", $saleInvoices, false);
// Build PURCHASE table
$html .= buildTable("PURCHASE Invoices", $purchaseInvoices, true);

$html .= "
</body>
</html>
";

// 4) Generate PDF with mPDF
try {
    // Create the Mpdf object
    $mpdf = new Mpdf([
        'format' => 'A4',
        'orientation' => 'P',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10
    ]);

    // Optional: shrink large tables to fit
    $mpdf->shrink_tables_to_fit = 1;

    // Write the HTML
    $mpdf->WriteHTML($html);

    // Output as download
    $fileName = "Invoices_{$monthName}-{$selectedYear}.pdf";
    $mpdf->Output($fileName, 'D'); // 'D' forces download; use 'I' to open inline
} catch (\Mpdf\MpdfException $e) {
    echo "Error generating PDF: " . $e->getMessage();
    exit;
}
