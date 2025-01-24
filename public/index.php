<?php
require_once __DIR__ . '/../config/db.php';

// Array mapping numeric month to month name
$months = [
    1 => "January",   2 => "February", 3 => "March",    4 => "April",
    5 => "May",       6 => "June",     7 => "July",     8 => "August",
    9 => "September", 10 => "October", 11 => "November", 12 => "December"
];

// Grab month/year from GET
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$selectedYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

// Query data
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
    echo "Error: " . $e->getMessage();
    exit;
}

// Build a PDF download URL if using FPDF or mPDF
$downloadUrl = "download_invoices_fpdf.php?month={$selectedMonth}&year={$selectedYear}";
$monthName = $months[$selectedMonth] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice Dashboard</title>
  <!-- Bootstrap CSS (CDN) -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        integrity="sha384-ENjdO4Dr2bkBIFxQpe67Z19qLMxk8t8R2DtacvoB2mqq1GcOAS+P1..."
        crossorigin="anonymous">
</head>
<body>

<div class="container my-4">
  <h1 class="mb-4">Invoicing Dashboard</h1>

  <!-- Row with "Create" button and Filter Form -->
  <div class="row">
    <div class="col-md-6 d-flex align-items-center">
      <a href="create_invoice.php" class="btn btn-primary me-3">
        Create New Invoice
      </a>
      <!-- PDF Download Button if you have it -->
      <a href="<?php echo $downloadUrl; ?>" class="btn btn-success">
        Download PDF (<?php echo $monthName . " " . $selectedYear; ?>)
      </a>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-body">
          <form method="GET" action="index.php" class="row g-2">
            <div class="col-auto">
              <label for="month" class="form-label">Month</label>
              <select name="month" id="month" class="form-select">
                <?php
                foreach ($months as $num => $name) {
                  $selected = ($num == $selectedMonth) ? 'selected' : '';
                  echo "<option value='$num' $selected>$name</option>";
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
      </div><!-- card -->
    </div><!-- col -->
  </div><!-- row -->

  <hr class="my-4">

  <!-- Display the selected month and year in a heading -->
  <h2>Invoices for <?php echo $monthName . " " . $selectedYear; ?></h2>

  <!-- SALE Invoices -->
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

  <!-- PURCHASE Invoices -->
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

<!-- Optional Bootstrap JS (for advanced components) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+CrUpAgiT9G..."
        crossorigin="anonymous"></script>
</body>
</html>
