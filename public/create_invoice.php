<?php
// public/create_invoice.php

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gather posted data
    $invoiceNumber  = $_POST['invoice_number']   ?? '';
    $invoiceType    = $_POST['invoice_type']     ?? 'SALE';
    $invoiceDate    = $_POST['invoice_date']     ?? date('Y-m-d');
    $customerName   = $_POST['customer_name']    ?? '';
    $customerGSTIN  = $_POST['customer_gstin']   ?? '';
    $commodity      = $_POST['commodity']        ?? '';  // New field
    $taxableAmount  = $_POST['taxable_amount']   ?? 0;
    $cgst           = $_POST['cgst']             ?? 0;
    $sgst           = $_POST['sgst']             ?? 0;
    $total          = $_POST['total']            ?? 0;

    // Convert numeric fields
    $taxableAmount = (float)$taxableAmount;
    $cgst          = (float)$cgst;
    $sgst          = (float)$sgst;
    $total         = (float)$total;

    // Insert into invoices
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
            ':commodity'      => $commodity,       // Insert commodity if PURCHASE
            ':taxable_amount' => $taxableAmount,
            ':cgst'           => $cgst,
            ':sgst'           => $sgst,
            ':total'          => $total
        ]);

        $newId = $pdo->lastInsertId();
        echo "<p>Invoice created successfully! ID: $newId, Type: $invoiceType</p>";
        echo '<p><a href="index.php">Go Back Home</a></p>';
        exit;
    } catch (PDOException $e) {
        echo "Error inserting invoice: " . $e->getMessage();
        exit;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Create Invoice</title>
    <script>
    function autoCalculate() {
        let taxableAmount = parseFloat(document.getElementById('taxable_amount').value) || 0;
        let taxRate       = parseFloat(document.getElementById('tax_rate').value)       || 0;

        let cgstField = document.getElementById('cgst');
        let sgstField = document.getElementById('sgst');

        let autoCgst = (taxableAmount * taxRate) / 100;
        let autoSgst = (taxableAmount * taxRate) / 100;

        let currentCgst = parseFloat(cgstField.value) || 0;
        let currentSgst = parseFloat(sgstField.value) || 0;

        // Overwrite if zero or still matching
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

    // NEW: Hide/show Commodity field
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
    <h1>Create Invoice</h1>
    <form method="POST" oninput="autoCalculate()">
        <p>
            <label for="invoice_number">Invoice Number:</label>
            <input type="text" id="invoice_number" name="invoice_number" required>
        </p>

        <p>
            <label for="invoice_type">Invoice Type:</label>
            <select id="invoice_type" name="invoice_type" onchange="toggleCommodity()">
                <option value="SALE">SALE</option>
                <option value="PURCHASE">PURCHASE</option>
            </select>
        </p>

        <!-- Only appears for PURCHASE type -->
        <p id="commodity_div" style="display:none;">
            <label for="commodity">Commodity:</label>
            <input type="text" id="commodity" name="commodity" placeholder="e.g., Stationery, Electronics">
        </p>

        <p>
            <label for="invoice_date">Invoice Date:</label>
            <input type="date" id="invoice_date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" required>
        </p>

        <p>
            <label for="customer_name">Customer Name:</label>
            <input type="text" id="customer_name" name="customer_name" required>
        </p>

        <p>
            <label for="customer_gstin">Customer GSTIN:</label>
            <input type="text" id="customer_gstin" name="customer_gstin" maxlength="15">
        </p>

        <p>
            <label for="taxable_amount">Taxable Amount:</label>
            <input type="number" step="0.01" id="taxable_amount" name="taxable_amount" value="0">
        </p>

        <p>
            <label for="tax_rate">GST Rate for Each (CGST & SGST) (%):</label>
            <input type="number" step="0.01" id="tax_rate" name="tax_rate" value="9">
        </p>

        <p>
            <label for="cgst">CGST (auto or editable):</label>
            <input type="number" step="0.01" id="cgst" name="cgst" value="0">
        </p>

        <p>
            <label for="sgst">SGST (auto or editable):</label>
            <input type="number" step="0.01" id="sgst" name="sgst" value="0">
        </p>

        <p>
            <label for="total">Total (read-only):</label>
            <input type="number" step="0.01" id="total" name="total" value="0" readonly>
        </p>

        <button type="submit">Save Invoice</button>
    </form>
</body>
</html>
