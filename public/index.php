<?php
// public/index.php

require_once __DIR__ . '/../config/db.php';

// A simple mapping of numeric month => month name
$months = [
    1 => "January",
    2 => "February",
    3 => "March",
    4 => "April",
    5 => "May",
    6 => "June",
    7 => "July",
    8 => "August",
    9 => "September",
    10 => "October",
    11 => "November",
    12 => "December"
];

// Determine the selected month/year (default to current if not provided)
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$selectedYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

// Prepare arrays for SALE and PURCHASE
$saleInvoices = [];
$purchaseInvoices = [];

try {
    // Query SALE invoices for the selected month/year
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

    // Query PURCHASE invoices for the selected month/year
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
    echo "Error fetching invoices: " . $e->getMessage();
    exit;
}

// Build the export URL for Excel
$exportUrl = "export_excel.php?month={$selectedMonth}&year={$selectedYear}";

// Build the download URL for PDF
$pdfUrl = "download_pdf.php?month={$selectedMonth}&year={$selectedYear}";

// For display
$monthName = isset($months[$selectedMonth]) ? $months[$selectedMonth] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoicing Dashboard</title>
  <!-- Bootstrap (CDN) for styling -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        integrity="sha384-ENjdO4Dr2bkBIFxQpe67Z19qLMxk8t8R2DtacvoB2mqq1GcOAS+P1..."
        crossorigin="anonymous">
</head>
<body>
<div class="container my-4">
  <h1 class="mb-4">Invoicing Dashboard</h1>

  <!-- Row with "Create New Invoice" + Export Buttons + Filter Form -->
  <div class="row mb-3">
    <div class="col-md-6 d-flex align-items-center">
      <!-- Button: Create New Invoice -->
      <a href="create_invoice.php" class="btn btn-primary me-3">
        Create New Invoice
      </a>

      <!-- Button: Export as Excel -->
      <a href="<?php echo $exportUrl; ?>" class="btn btn-success me-3">
        Export as Excel (<?php echo $monthName . " " . $selectedYear; ?>)
      </a>

      <!-- Button: Download PDF -->
      <a href="<?php echo $pdfUrl; ?>" class="btn btn-danger">
        Download PDF (<?php echo $monthName . " " . $selectedYear; ?>)
      </a>
    </div>

    <div class="col-md-6">
      <div class="card">
        <div class="card-body">
          <!-- Filter Form (Month / Year) -->
          <form method="GET" action="index.php" class="row g-2">
            <div class="col-auto">
              <label for="month" class="form-label">Month</label>
              <select name="month" id="month" class="form-select">
                <?php
                foreach ($months as $num => $mName) {
                    $selected = ($num == $selectedMonth) ? 'selected' : '';
                    echo "<option value='$num' $selected>$mName</option>";
                }
                ?>
              </select>
            </div>
            <div class="col-auto">
              <label for="year" class="form-label">Year</label>
              <input type="number" name="year" id="year"
                     value="<?php echo $selectedYear; ?>"
                     class="form-control">
            </div>
            <div class="col-auto pt-4">
              <button type="submit" class="btn btn-info mt-1">
                Show Invoices
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div><!-- row -->

  <hr class="my-4">

  <h2>Invoices for <?php echo $monthName . " " . $selectedYear; ?></h2>

  <!-- SALE Invoices Section -->
  <div class="mt-4">
    <h4>SALE Invoices</h4>
    <?php if (!empty($saleInvoices)): ?>
      <table class="table table-bordered table-hover table-sm align-middle">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Invoice Number</th>
            <th>Invoice Date</th>
            <th>Customer</th>
            <th>GSTIN</th>
            <th>Taxable</th>
            <th>CGST</th>
            <th>SGST</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sumTaxableSale = 0;
        $sumCgstSale    = 0;
        $sumSgstSale    = 0;
        $sumTotalSale   = 0;

        foreach ($saleInvoices as $inv):
          $sumTaxableSale += $inv['taxable_amount'];
          $sumCgstSale    += $inv['cgst'];
          $sumSgstSale    += $inv['sgst'];
          $sumTotalSale   += $inv['total'];
        ?>
          <tr>
            <td><?php echo $inv['id']; ?></td>
            <td><?php echo htmlspecialchars($inv['invoice_number']); ?></td>
            <td><?php echo $inv['invoice_date']; ?></td>
            <td><?php echo htmlspecialchars($inv['customer_name']); ?></td>
            <td><?php echo htmlspecialchars($inv['customer_gstin']); ?></td>
            <td><?php echo number_format($inv['taxable_amount'], 2); ?></td>
            <td><?php echo number_format($inv['cgst'], 2); ?></td>
            <td><?php echo number_format($inv['sgst'], 2); ?></td>
            <td><?php echo number_format($inv['total'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
          <!-- Totals row -->
          <tr class="fw-bold table-secondary">
            <td colspan="5" class="text-end">TOTAL:</td>
            <td><?php echo number_format($sumTaxableSale, 2); ?></td>
            <td><?php echo number_format($sumCgstSale, 2); ?></td>
            <td><?php echo number_format($sumSgstSale, 2); ?></td>
            <td><?php echo number_format($sumTotalSale, 2); ?></td>
          </tr>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted">No SALE invoices found for this month/year.</p>
    <?php endif; ?>
  </div>

  <!-- PURCHASE Invoices Section -->
  <div class="mt-4">
    <h4>PURCHASE Invoices</h4>
    <?php if (!empty($purchaseInvoices)): ?>
      <table class="table table-bordered table-hover table-sm align-middle">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Invoice Number</th>
            <th>Invoice Date</th>
            <th>Customer</th>
            <th>GSTIN</th>
            <th>Commodity</th>
            <th>Taxable</th>
            <th>CGST</th>
            <th>SGST</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sumTaxablePur = 0;
        $sumCgstPur    = 0;
        $sumSgstPur    = 0;
        $sumTotalPur   = 0;

        foreach ($purchaseInvoices as $inv):
          $sumTaxablePur += $inv['taxable_amount'];
          $sumCgstPur    += $inv['cgst'];
          $sumSgstPur    += $inv['sgst'];
          $sumTotalPur   += $inv['total'];
        ?>
          <tr>
            <td><?php echo $inv['id']; ?></td>
            <td><?php echo htmlspecialchars($inv['invoice_number']); ?></td>
            <td><?php echo $inv['invoice_date']; ?></td>
            <td><?php echo htmlspecialchars($inv['customer_name']); ?></td>
            <td><?php echo htmlspecialchars($inv['customer_gstin']); ?></td>
            <td><?php echo htmlspecialchars($inv['commodity']); ?></td>
            <td><?php echo number_format($inv['taxable_amount'], 2); ?></td>
            <td><?php echo number_format($inv['cgst'], 2); ?></td>
            <td><?php echo number_format($inv['sgst'], 2); ?></td>
            <td><?php echo number_format($inv['total'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
          <!-- Totals row -->
          <tr class="fw-bold table-secondary">
            <td colspan="6" class="text-end">TOTAL:</td>
            <td><?php echo number_format($sumTaxablePur, 2); ?></td>
            <td><?php echo number_format($sumCgstPur, 2); ?></td>
            <td><?php echo number_format($sumSgstPur, 2); ?></td>
            <td><?php echo number_format($sumTotalPur, 2); ?></td>
          </tr>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted">No PURCHASE invoices found for this month/year.</p>
    <?php endif; ?>
  </div>

</div><!-- container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+CrUpAgiT9G..."
        crossorigin="anonymous"></script>
</body>
</html>
