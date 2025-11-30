<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php';
include 'header.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<div style="text-align:center; margin-top:50px; font-family:Arial,sans-serif;">
            <h2 style="color:#d9534f;">Access Denied</h2>
            <p>You do not have permission to view this page.</p>
          </div>';
    exit;
}

// Fetch bank list and user list
$bankList = $pdo->query("SELECT DISTINCT bank_name FROM files WHERE type='unmatched'")->fetchAll(PDO::FETCH_COLUMN);
$userList = $pdo->query("SELECT id, username FROM users WHERE is_approved = 1 AND role != 'admin'")->fetchAll(PDO::FETCH_ASSOC);

// Get filters
$bankFilter = $_POST['bank_name'] ?? '';
$userFilter = $_POST['user_id'] ?? '';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
?>

<div style="width:90%; max-width:1200px; margin:30px auto; font-family:Arial,sans-serif;">
    <h2 style="text-align:center; color:#333; margin-bottom:20px;">Unmatched Files Overview</h2>

    <form method="post" style="text-align:center; margin-bottom:25px;">
        <label><b>Bank:</b></label>
        <select name="bank_name" style="padding:8px; border-radius:4px; border:1px solid #ccc;">
            <option value="">-- All Banks --</option>
            <?php foreach ($bankList as $bank): ?>
                <option value="<?= htmlspecialchars($bank) ?>" <?= $bank === $bankFilter ? 'selected' : '' ?>>
                    <?= htmlspecialchars($bank) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label><b>User:</b></label>
        <select name="user_id" style="padding:8px; border-radius:4px; border:1px solid #ccc;">
            <option value="">-- All Users --</option>
            <?php foreach ($userList as $user): ?>
                <option value="<?= $user['id'] ?>" <?= $userFilter == $user['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label><b>Date Range:</b></label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" style="padding:8px;">
        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" style="padding:8px;">

        <button type="submit" style="padding:8px 15px; margin-left:10px; background:#28a745; color:white; border:none; border-radius:4px;">Show</button>
    </form>

<?php
// Prepare SQL with filters
$sql = "SELECT f.*, u.username
        FROM files f
        JOIN users u ON f.user_id = u.id
        WHERE f.type = 'unmatched'";
$params = [];

if ($bankFilter !== '') { $sql .= " AND f.bank_name = ?"; $params[] = $bankFilter; }
if ($userFilter !== '') { $sql .= " AND f.user_id = ?"; $params[] = $userFilter; }
if ($startDate !== '' && $endDate !== '') { 
    $sql .= " AND DATE(f.created_at) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

$sql .= " ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fileRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total unmatched rows across all files
$totalRows = 0;
foreach ($fileRecords as $file) {
    $tmpFile = tempnam(sys_get_temp_dir(), 'xls_');
    file_put_contents($tmpFile, $file['file_data']);
    $spreadsheet = IOFactory::load($tmpFile);
    $sheet = $spreadsheet->getActiveSheet();
    $rowCount = $sheet->getHighestDataRow();
    if ($rowCount > 1) $totalRows += ($rowCount - 1);
    unlink($tmpFile);
}

if ($bankFilter || $userFilter || ($startDate && $endDate)) {
    echo '<h3 style="color:#007bff; text-align:center; margin-bottom:20px;">
            Total Unmatched Data (All Files): <b>' . number_format($totalRows) . '</b>
          </h3>';
}

if (empty($fileRecords)) {
    echo "<p style='text-align:center; color:#d9534f;'>No unmatched files found.</p>";
} else {
    foreach ($fileRecords as $file):
        $tmpFile = tempnam(sys_get_temp_dir(), 'xls_');
        file_put_contents($tmpFile, $file['file_data']);
        $spreadsheet = IOFactory::load($tmpFile);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
        $rowCount = $sheet->getHighestDataRow();
        $fileRowCount = ($rowCount > 1) ? ($rowCount - 1) : 0;

        // Column sets
        $dnccColumnsTL = [
            'docnumber' => 'TL No',
            'tranno' => 'Txn ID',
            'cdate' => 'Date',
            'totalamt' => 'Amount',
            'gateway' => 'Gateway',
            'PaymentType' => 'Payment Type'

        ];
        $dnccColumnsHolding = [
            'e-holding' => 'E-Holding',
            'transactio id' => 'Txn ID',
            'date' => 'Date',
            'paid amount' => 'Amount',
            'gateway' => 'Gateway'
        ];
        $dbblColumnsHolding = [
            'bill no' => 'Holding No / TL No',
            'txn id' => 'Txn ID',
            'territory code' => 'Date',
            'amount' => 'Amount'
        ];
        $dbblColumnsHoldingDue = [
            'bill no' => 'Holding No / TL No',
            'txn id' => 'Txn ID',
            'territory code' => 'Date',
            'amount' => 'Amount'
        ];  
        $dbblColumnsHoldingMFS = [
            'BILLER_REF_NO' => 'Holding No',
            'TRANSACTION_ID' => 'Txn ID',
            'TXN_DATE' => 'Date',
            'TXN_AMT' => 'Amount'
        ];  
        $dbblColumnsHoldingMFSDue = [
            'BILLER_REF_NO' => 'Holding No',
            'TRANSACTION_ID' => 'Txn ID',
            'TXN_DATE' => 'Date',
            'TXN_AMT' => 'Amount'
        ];  
        $ColumnsBkashHolding = [
            'Account Number' => 'Holding No / TL No',
            'bKash Transaction ID' => 'Txn ID',
            'Pay Date' => 'Date',
            'Total Amount' => 'Amount'
        ];         
        $ColumnsSonaliHolding = [
            'BENEFICIARYNAME' => 'Holding No',
            'BANKTRANID' => 'Txn ID',
            'TRANDATE' => 'Date',
            'REQAMOUNT' => 'Amount'
        ];         
        $ColumnsStandardHolding = [
            'E-Holding No' => 'Holding No',
            'Transactio ID' => 'Txn ID',
            'Payment Date' => 'Date',
            'Paid Amount' => 'Amount'
        ];         
        $ColumnsModhumotiHolding = [
            'Holding no' => 'Holding No',
            'Payment no' => 'Txn ID',
            'Date' => 'Date',
            'Total amount' => 'Amount'
        ];                 
        $ColumnsTAPHolding = [
            // 'Holding no' => 'Holding No',
            'Transaction Id' => 'Txn ID',
            'Transaction Date' => 'Date',
            'Amount' => 'Amount'
        ];         
        $ColumnsUPAYHolding = [
            'E-Holding Number' => 'Holding No',
            'Transaction ID (DNCC)' => 'Txn ID',
            'Date & Time' => 'Date',
            'Amount BDT' => 'Amount'
        ];         
        $ColumnsOKWalletHolding = [
            'Holding No' => 'Holding No',
            'TransactionNo' => 'Txn ID',
            'Transaction Date' => 'Date',
            'Amount' => 'Amount'
        ];

        // Decide which column set to use
        $columnsToUse = [$dnccColumnsTL, $dnccColumnsHolding, $dbblColumnsHolding, $dbblColumnsHoldingDue, $dbblColumnsHoldingMFS, $dbblColumnsHoldingMFSDue , $ColumnsBkashHolding, $ColumnsSonaliHolding, $ColumnsStandardHolding, $ColumnsModhumotiHolding, $ColumnsTAPHolding, $ColumnsUPAYHolding, $ColumnsOKWalletHolding];
        $selectedColumns = [];

        foreach ($columnsToUse as $colSet) {
            $allFound = true;
            foreach ($colSet as $key => $label) {
                if (array_search(strtolower($key), array_map('strtolower', $data[0])) === false) {
                    $allFound = false;
                    break;
                }
            }
            if ($allFound) {
                $selectedColumns = $colSet;
                break;
            }
        }

        $headers = array_map('strtolower', $data[0]);
        $displayHeaders = [];
        $displayIndexes = [];

        if (!empty($selectedColumns)) {
            foreach ($selectedColumns as $key => $label) {
                $index = array_search(strtolower($key), $headers);
                if ($index !== false) {
                    $displayHeaders[] = $label;
                    $displayIndexes[] = $index;
                }
            }
        } else {
            $displayHeaders = $data[0];
            $displayIndexes = range(0, count($data[0]) - 1);
        }

        // Calculate total amount if column exists
        $amountCol = null;
        foreach ($displayIndexes as $idx) {
            if (in_array($headers[$idx], ['totalamt','paid amount','amount', 'txn_amt', 'TXN_AMT', 'total amount', 'reqamount', 'amount bdt'])) {
                $amountCol = $idx;
                break;
            }
        }

        $totalAmount = 0;
        if ($amountCol !== null) {
            for ($i = 1; $i < count($data); $i++) {
                $val = preg_replace('/[^0-9.\-]/', '', $data[$i][$amountCol]);
                if (is_numeric($val)) $totalAmount += $val;
            }
        }

        unlink($tmpFile);
?>

        <div style="margin-bottom:30px;">
            <h3>File: <?= htmlspecialchars($file['filename']) ?>
                (Bank: <?= htmlspecialchars($file['bank_name']) ?>, User: <?= htmlspecialchars($file['username']) ?>)
            </h3>

            <p><b>Unmatched Rows in This File:</b> <?= number_format($fileRowCount) ?></p>
            <p><b>Total TK Amount:</b> <?= number_format($totalAmount, 2) ?> </p>

            <div style="overflow-x:auto; margin-top:10px;">
                <table style="width:100%; border-collapse:collapse; min-width:900px;">
                    <tr>
                        <?php foreach ($displayHeaders as $header): ?>
                            <th style="border:1px solid #ddd; padding:5px; background:#f5f5f5;">
                                <?= htmlspecialchars($header) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>

                    <?php for ($i = 1; $i < count($data); $i++): ?>
                        <tr>
                            <?php foreach ($displayIndexes as $colIndex): ?>
                                <?php 
                                    $cell = $data[$i][$colIndex];

                                    // Format only numeric amount columns
                                    if (in_array($headers[$colIndex], ['totalamt','paid amount','amount', 'txn_amt', 'total amount', 'reqamount', 'amount bdt']) && is_numeric($cell)) {
                                        $cell = number_format((float)$cell, 2, '.', '');
                                    }
                                ?>
                                <td style="border:1px solid #ddd; padding:5px;"><?= htmlspecialchars($cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                </table>
            </div>
        </div>

<?php
    endforeach;
}
?>
</div>
