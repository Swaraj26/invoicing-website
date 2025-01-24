<?php
// public/create_invoice.php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gather form data
    $invoiceNumber = $_POST['invoice_number']   ?? '';
    $invoiceType   = $_POST['invoice_type']     ?? 'SALE';
    $invoiceDate   = $_POST['invoice_date']     ?? date('Y-m-d');
    $customerName  = $_POST['customer_name']    ?? '';
    $customerGSTIN = $_POST['customer_gstin']   ?? '';
    $commodity     = $_POST['commodity']        ?? '';
    $taxableAmount = $_POST['taxable_amount']   ?? 0;
    $taxRate       = $_POST['tax_rate']         ?? 0;
    $cgst          = $_POST['cgst']             ?? 0;
    $sgst          = $_POST['sgst']             ?? 0;
    $total         = $_POST['total']            ?? 0;

    // Convert numeric
    $taxableAmount = (float)$taxableAmount;
    $taxRate       = (float)$taxRate;
    $cgst          = (float)$cgst;
    $sgst          = (float)$sgst;
    $total         = (float)$total;

    try {
        $sql = "INSERT INTO invoices (
                  invoice_number, invoice_type, invoice_date,
                  customer_name, customer_gstin, commodity,
                  taxable_amount, cgst, sgst, total
                ) VALUES (
                  :invoice_number, :invoice_type, :invoice_date,
                  :customer_name, :customer_gstin, :commodity,
                  :taxable_amount, :cgst, :sgst, :total
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':invoice_number' => $invoiceNumber,
            ':invoice_type'   => $invoiceType,
            ':invoice_date'   => $invoiceDate,
            ':customer_name'  => $customerName,
            ':customer_gstin' => $customerGSTIN,
            ':commodity'      => $commodity,
            ':taxable_amount' => $taxableAmount,
            ':cgst'           => $cgst,
            ':sgst'           => $sgst,
            ':total'          => $total
        ]);

        echo "<div class='alert alert-success m-3'>Invoice created successfully!</div>";
        echo '<p class="m-3"><a href="index.php" class="btn btn-secondary">Go Back Home</a></p>';
        exit;
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger m-3'>Error inserting invoice: " . $e->getMessage() . "</div>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Invoice</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        integrity="sha384-ENjdO4Dr2bkBIFxQpe67Z19qLMxk8t8R2DtacvoB2mqq1GcOAS+P1..."
        crossorigin="anonymous">
  <script>
    // Auto-calc CGST/SGST from taxRate
    function autoCalculate() {
      let taxableAmount = parseFloat(document.getElementById('taxable_amount').value) || 0;
      let taxRate       = parseFloat(document.getElementById('tax_rate').value) || 0;

      let cgstField = document.getElementById('cgst');
      let sgstField = document.getElementById('sgst');

      let autoCgst = (taxableAmount * taxRate) / 100;
      let autoSgst = (taxableAmount * taxRate) / 100;

      let currentCgst = parseFloat(cgstField.value) || 0;
      let currentSgst = parseFloat(sgstField.value) || 0;

      if (currentCgst === 0 || currentCgst === currentSgst) {
        cgstField.value = autoCgst.toFixed(2);
      }
      if (currentSgst === 0 || currentSgst === currentCgst) {
        sgstField.value = autoSgst.toFixed(2);
      }

      let finalCgst = parseFloat(cgstField.value) || 0;
      let finalSgst = parseFloat(sgstField.value) || 0;
      let total = taxableAmount + finalCgst + finalSgst;

      document.getElementById('total').value = total.toFixed(2);
    }

    // Show/hide Commodity for PURCHASE
    function toggleCommodity() {
      let typeSelect = document.getElementById('invoice_type');
      let commodityDiv = document.getElementById('commodity_div');
      if (typeSelect.value === 'PURCHASE') {
        commodityDiv.style.display = 'block';
      } else {
        commodityDiv.style.display = 'none';
      }
    }
  </script>
</head>
<body onload="toggleCommodity(); autoCalculate();">

<div class="container my-4">
  <h1 class="mb-4">Create Invoice</h1>

  <div class="card">
    <div class="card-body">
      <form method="POST" oninput="autoCalculate()">
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="invoice_number" class="form-label">Invoice Number</label>
            <input type="text" name="invoice_number" id="invoice_number"
                   class="form-control" required>
          </div>
          <div class="col-md-4">
            <label for="invoice_type" class="form-label">Invoice Type</label>
            <select name="invoice_type" id="invoice_type"
                    class="form-select" onchange="toggleCommodity()">
              <option value="SALE">SALE</option>
              <option value="PURCHASE">PURCHASE</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="invoice_date" class="form-label">Invoice Date</label>
            <input type="date" name="invoice_date" id="invoice_date"
                   class="form-control"
                   value="<?php echo date('Y-m-d'); ?>" required>
          </div>
        </div><!-- row -->

        <div class="row mb-3">
          <div class="col-md-6">
            <label for="customer_name" class="form-label">Customer Name</label>
            <input type="text" name="customer_name" id="customer_name"
                   class="form-control" required>
          </div>
          <div class="col-md-6">
            <label for="customer_gstin" class="form-label">Customer GSTIN</label>
            <input type="text" name="customer_gstin" id="customer_gstin"
                   class="form-control" maxlength="15">
          </div>
        </div><!-- row -->

        <!-- Commodity: only visible if PURCHASE -->
        <div class="row mb-3" id="commodity_div" style="display:none;">
          <div class="col-md-6">
            <label for="commodity" class="form-label">Commodity</label>
            <input type="text" name="commodity" id="commodity"
                   class="form-control"
                   placeholder="e.g. Stationery, Electronics">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-3">
            <label for="taxable_amount" class="form-label">Taxable Amount</label>
            <input type="number" step="0.01"
                   name="taxable_amount"
                   id="taxable_amount"
                   class="form-control"
                   value="0">
          </div>
          <div class="col-md-3">
            <label for="tax_rate" class="form-label">GST Rate (Each)</label>
            <input type="number" step="0.01"
                   name="tax_rate"
                   id="tax_rate"
                   class="form-control"
                   value="9">
            <small class="text-muted">e.g. enter 9 to get 9% CGST + 9% SGST = 18% total</small>
          </div>
          <div class="col-md-3">
            <label for="cgst" class="form-label">CGST</label>
            <input type="number" step="0.01"
                   name="cgst"
                   id="cgst"
                   class="form-control"
                   value="0">
          </div>
          <div class="col-md-3">
            <label for="sgst" class="form-label">SGST</label>
            <input type="number" step="0.01"
                   name="sgst"
                   id="sgst"
                   class="form-control"
                   value="0">
          </div>
        </div><!-- row -->

        <div class="row mb-3">
          <div class="col-md-3">
            <label for="total" class="form-label">Total Amount</label>
            <input type="number" step="0.01"
                   name="total"
                   id="total"
                   class="form-control"
                   value="0"
                   readonly>
          </div>
        </div><!-- row -->

        <button type="submit" class="btn btn-primary">Save Invoice</button>
        <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
      </form>
    </div><!-- card-body -->
  </div><!-- card -->

</div><!-- container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+CrUpAgiT9G..."
        crossorigin="anonymous"></script>
</body>
</html>
