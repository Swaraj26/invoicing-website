<?php
// public/index.php
require_once __DIR__ . '/../config/db.php';

// 1. Build a map of month number => month name
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

// 2. Grab month/year from query params (default to current month/year)
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$selectedYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

// Queries for SALE and PURCHASE
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
    $stmtSale->execute([
        ':m' => $selectedMonth,
        ':y' => $selectedYear
    ]);
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
    $stmtPurchase->execute([
        ':m' => $selectedMonth,
        ':y' => $selectedYear
    ]);
    $purchaseInvoices = $stmtPurchase->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error fetching invoices: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoicing Home</title>
</head>
<body>
    <h1>Invoicing Home</h1>

    <p><a href="create_invoice.php">Create New Invoice</a></p>

    <!-- Form to select month/year -->
    <form method="GET" action="index.php">
        <label for="month">Month:</label>
        <select name="month" id="month">
            <?php
            foreach ($months as $num => $name) {
                $selected = ($num == $selectedMonth) ? 'selected' : '';
                echo "<option value='$num' $selected>$name</option>";
            }
            ?>
        </select>

        <label for="year">Year:</label>
        <input type="number" name="year" id="year" value="<?php echo $selectedYear; ?>">

        <button type="submit">Show Invoices</button>
    </form>

    <hr>

    <!-- SALE Invoices -->
    <h2>SALE Invoices for <?php echo $months[$selectedMonth] . " " . $selectedYear; ?></h2>
    <?php if (!empty($saleInvoices)): ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>ID</th>
                <th>Invoice Number</th>
                <th>Invoice Date</th>
                <th>Customer Name</th>
                <th>GSTIN</th>
                <th>Taxable Amount</th>
                <th>CGST</th>
                <th>SGST</th>
                <th>Total</th>
            </tr>

            <?php
            // Initialize sum variables
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
                    <td><?php echo $inv['taxable_amount']; ?></td>
                    <td><?php echo $inv['cgst']; ?></td>
                    <td><?php echo $inv['sgst']; ?></td>
                    <td><?php echo $inv['total']; ?></td>
                </tr>
            <?php endforeach; ?>

            <!-- TOTAL row for SALE -->
            <tr style="font-weight:bold;">
                <td colspan="5" align="right">TOTAL:</td>
                <td><?php echo number_format($sumTaxableSale, 2); ?></td>
                <td><?php echo number_format($sumCgstSale, 2); ?></td>
                <td><?php echo number_format($sumSgstSale, 2); ?></td>
                <td><?php echo number_format($sumTotalSale, 2); ?></td>
            </tr>
        </table>
    <?php else: ?>
        <p>No SALE invoices found for this month/year.</p>
    <?php endif; ?>

    <hr>

    <!-- PURCHASE Invoices -->
    <h2>PURCHASE Invoices for <?php echo $months[$selectedMonth] . " " . $selectedYear; ?></h2>
    <?php if (!empty($purchaseInvoices)): ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>ID</th>
                <th>Invoice Number</th>
                <th>Invoice Date</th>
                <th>Customer Name</th>
                <th>GSTIN</th>
                <th>Commodity</th>
                <th>Taxable Amount</th>
                <th>CGST</th>
                <th>SGST</th>
                <th>Total</th>
            </tr>

            <?php
            // Initialize sum variables
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
                    <td><?php echo htmlspecialchars($inv['commodity']); ?></td> <!-- commodity only for PURCHASE -->
                    <td><?php echo $inv['taxable_amount']; ?></td>
                    <td><?php echo $inv['cgst']; ?></td>
                    <td><?php echo $inv['sgst']; ?></td>
                    <td><?php echo $inv['total']; ?></td>
                </tr>
            <?php endforeach; ?>

            <!-- TOTAL row for PURCHASE -->
            <tr style="font-weight:bold;">
                <td colspan="6" align="right">TOTAL:</td>
                <td><?php echo number_format($sumTaxablePur, 2); ?></td>
                <td><?php echo number_format($sumCgstPur, 2); ?></td>
                <td><?php echo number_format($sumSgstPur, 2); ?></td>
                <td><?php echo number_format($sumTotalPur, 2); ?></td>
            </tr>
        </table>
    <?php else: ?>
        <p>No PURCHASE invoices found for this month/year.</p>
    <?php endif; ?>

</body>
</html>
