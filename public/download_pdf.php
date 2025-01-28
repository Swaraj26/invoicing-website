<?php
// public/download_pdf.php

require __DIR__ . '/../config/db.php';

// If you have a modern mPDF with Composer’s autoloader:
require __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;

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
    die("Error fetching invoices: " . $e->getMessage());
}

// 3) Build HTML
$monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));
$title = "Invoices for {$monthName} {$selectedYear}";

// We'll create separate HTML blocks for Sale and Purchase
function buildSaleTable($invoices) {
    if (count($invoices) === 0) {
        return "<h3>SALE Invoices</h3><p>No SALE invoices found for this month/year.</p>";
    }

    $sumTaxable = 0; 
    $sumCgst = 0; 
    $sumSgst = 0; 
    $sumTotal = 0;

    // Table header (bold row)
    $html = "<h3 style='margin-top:20px;'>SALE Invoices</h3>
    <table style='width:100%; border-collapse:collapse; margin-top:10px; font-size:9pt;'>
      <thead>
        <tr style='background:#e1e1e1; font-weight:bold;'>
          <th style='border:1px solid #000; padding:6px;'>ID</th>
          <th style='border:1px solid #000; padding:6px;'>Invoice No.</th>
          <th style='border:1px solid #000; padding:6px;'>Date</th>
          <th style='border:1px solid #000; padding:6px;'>Customer</th>
          <th style='border:1px solid #000; padding:6px;'>GSTIN</th>
          <th style='border:1px solid #000; padding:6px;'>Taxable</th>
          <th style='border:1px solid #000; padding:6px;'>CGST</th>
          <th style='border:1px solid #000; padding:6px;'>SGST</th>
          <th style='border:1px solid #000; padding:6px;'>Total</th>
        </tr>
      </thead>
      <tbody>
    ";

    foreach ($invoices as $inv) {
        $sumTaxable += $inv['taxable_amount'];
        $sumCgst    += $inv['cgst'];
        $sumSgst    += $inv['sgst'];
        $sumTotal   += $inv['total'];

        $html .= "<tr>
          <td style='border:1px solid #000; padding:6px;'>{$inv['id']}</td>
          <td style='border:1px solid #000; padding:6px;'>".htmlspecialchars($inv['invoice_number'])."</td>
          <td style='border:1px solid #000; padding:6px;'>{$inv['invoice_date']}</td>
          <td style='border:1px solid #000; padding:6px;'>".htmlspecialchars($inv['customer_name'])."</td>
          <td style='border:1px solid #000; padding:6px;'>".htmlspecialchars($inv['customer_gstin'])."</td>
          <td style='border:1px solid #000; padding:6px;'>{$inv['taxable_amount']}</td>
          <td style='border:1px solid #000; padding:6px;'>{$inv['cgst']}</td>
          <td style='border:1px solid #000; padding:6px;'>{$inv['sgst']}</td>
          <td style='border:1px solid #000; padding:6px;'>{$inv['total']}</td>
        </tr>";
    }

    // BOLD totals row
    $html .= "<tr style='font-weight:bold; background:#f9f9f9;'>
      <td colspan='5' style='border:1px solid #000; text-align:right; padding:6px;'>
        TOTAL:
      </td>
      <td style='border:1px solid #000; padding:6px;'>{$sumTaxable}</td>
      <td style='border:1px solid #000; padding:6px;'>{$sumCgst}</td>
      <td style='border:1px solid #000; padding:6px;'>{$sumSgst}</td>
      <td style='border:1px solid #000; padding:6px;'>{$sumTotal}</td>
    </tr>
    </tbody></table>";

    return $html;
}

function buildPurchaseTable($invoices) {
    if (count($invoices) === 0) {
        return "<h3>PURCHASE Invoices</h3><p>No PURCHASE invoices found for this month/year.</p>";
    }

    $sumTaxable = 0; 
    $sumCgst = 0; 
    $sumSgst = 0; 
    $sumTotal = 0;

    $html = "<h3 style='margin-top:20px;'>PURCHASE Invoices</h3>
    <table style='width:100%; border-collapse:collapse; margin-top:10px; font-size:9pt;'>
      <thead>
        <tr style='background:#e1e1e1; font-weight:bold;'>
          <th style='border:1px solid #000; padding:6px;'>ID</th>
          <th style='border:1px solid #000; padding:6px;'>Invoice No.</th>
          <th style='border:1px solid #000; padding:6px;'>Date</th>
          <th style='border:1px solid #000; padding:6px;'>Customer</th>
          <th style='border:1px solid #000; padding:6px;'>GSTIN</th>
          <th style='border:1px solid #000; padding:6px;'>Commodity</th>
          <th style='border:1px solid #000; padding:6px;'>Taxable</th>
          <th style='border:1px solid #000; padding:6px;'>CGST</th>
          <th style='border:1px solid #000; padding:6px;'>SGST</th>
          <th style='border:1px solid #000; padding:6px;'>Total</th>
        </tr>
      </thead>
      <tbody>
    ";

    foreach ($invoices as $inv) {
        $sumTaxable += $inv['taxable_amount'];
        $sumCgst    += $inv['cgst'];
        $sumSgst    += $inv['sgst'];
        $sumTotal   += $inv['total'];

        $html .= "<tr>
          <td style='border:1px solid #000; padding:6px;'>{$inv['id']}</td>
          <td style='border:1px solid #000; padding:6px;'>".htmlspecialchars($inv['invoice_number'])."</td>
          <td style='border:1px solid #000; padding:6px;'>{$inv['invoice_date']}</td>
          <td style='border:1px solid #000; padding:6px;'>".htmlspecialchars($inv['customer_name'])."</td>
          <td style='border:1px solid #000; padding:6px;'>".htmlspecialchars($inv['customer_gstin'])."</td>
          <td style='border:1px solid #000; padding:6px;'>".htmlspecialchars($inv['commodity'])."</td>
          <td style='border:1px solid #000; padding:6px;'>{$inv['taxable_amount']}</td>
          <td style='border:1px solid #000; padding:6px;'>{$inv['cgst']}</td>
          <td style='border:1px solid #000; padding:6px;'>{$inv['sgst']}</td>
          <td style='border:1px solid #000; padding:6px;'>{$inv['total']}</td>
        </tr>";
    }

    // BOLD totals row
    $html .= "<tr style='font-weight:bold; background:#f9f9f9;'>
      <td colspan='6' style='border:1px solid #000; text-align:right; padding:6px;'>
        TOTAL:
      </td>
      <td style='border:1px solid #000; padding:6px;'>{$sumTaxable}</td>
      <td style='border:1px solid #000; padding:6px;'>{$sumCgst}</td>
      <td style='border:1px solid #000; padding:6px;'>{$sumSgst}</td>
      <td style='border:1px solid #000; padding:6px;'>{$sumTotal}</td>
    </tr>
    </tbody></table>";

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
    margin:0; padding:0;
    font-size: 10pt;
  }
  h2, h3 {
    margin: 0;
    padding: 0;
  }
</style>
</head>
<body>
  <h2 style='margin-bottom:10px;'>{$title}</h2>
  ";

// Sale table
$html .= buildSaleTable($saleInvoices);
// Purchase table
$html .= buildPurchaseTable($purchaseInvoices);

$html .= "
</body>
</html>
";

// 4) Output with mPDF
try {
    // If you have a modern environment with mpdf 7+:
    $mpdf = new Mpdf([
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10
    ]);
    $mpdf->shrink_tables_to_fit = 1;
    $mpdf->WriteHTML($html);

    $fileName = "Invoices_{$monthName}-{$selectedYear}.pdf";
    $mpdf->Output($fileName, 'D');  // Force download
    exit;
} catch (\Mpdf\MpdfException $e) {
    echo "Error generating PDF: " . $e->getMessage();
    exit;
}
