<?php
require 'vendor/autoload.php';
include 'db.php';
include 'session.php';
include 'header.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$userId = $_SESSION['user_id'];

// Fetch all distinct bank names for unmatched files
$stmt = $pdo->prepare("SELECT DISTINCT bank_name FROM files WHERE user_id=? AND type='unmatched'");
$stmt->execute([$userId]);
$banks = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get selected bank from POST
$selectedBank = $_POST['bank_name'] ?? '';
?>

<div style="width:90%; max-width:1200px; margin:30px auto; font-family:Arial, sans-serif;">
    <h2 style="text-align:center; color:#333; margin-bottom:20px;">View Unmatched Data</h2>

    <!-- Bank filter form -->
    <form method="post" style="margin-bottom:20px; text-align:center;">
        <label for="bank_name" style="font-weight:bold; margin-right:10px;">Select Bank/Portal:</label>
        <select name="bank_name" id="bank_name" style="padding:8px; border-radius:4px; border:1px solid #ccc;">
            <option value="">-- Select Bank --</option>
            <?php foreach ($banks as $bank): ?>
                <option value="<?= htmlspecialchars($bank) ?>" <?= $bank === $selectedBank ? 'selected' : '' ?>>
                    <?= htmlspecialchars($bank) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" style="padding:8px 15px; margin-left:10px; background:#28a745; color:white; border:none; border-radius:4px;">Show</button>
    </form>

    <?php if ($selectedBank): // Only show results after filtering ?>
        <?php
        // Fetch unmatched files for selected bank
        $stmt = $pdo->prepare("SELECT * FROM files WHERE user_id=? AND type='unmatched' AND bank_name=? ORDER BY created_at DESC");
        $stmt->execute([$userId, $selectedBank]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if (empty($files)): ?>
            <p style="text-align:center; color:#d9534f;">No unmatched files found for this bank.</p>
        <?php else: ?>
            <?php foreach ($files as $file): ?>
                <?php
                // Create temporary file to read Excel data
                $tempPath = sys_get_temp_dir() . '/' . uniqid('xls_', true) . '_' . basename($file['filename']);
                file_put_contents($tempPath, $file['file_data']);
                $spreadsheet = IOFactory::load($tempPath);
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray();
                unlink($tempPath);
                ?>
                <div style="margin-bottom:30px;">
                    <h3 style="margin-top:20px;">File: <?= htmlspecialchars($file['filename']) ?> (Bank: <?= htmlspecialchars($file['bank_name']) ?>)</h3>
                    <a href="download.php?id=<?= $file['id'] ?>" style="padding:6px 12px; background:#4CAF50; color:white; border-radius:4px; text-decoration:none;">Download</a>

                    <!-- Scrollable table container -->
                    <div style="overflow-x:auto; margin-top:10px;">
                        <table style="width:100%; border-collapse:collapse; min-width:900px;">
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?>
                                        <td style="border:1px solid #ddd; padding:5px;"><?= htmlspecialchars($cell) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
