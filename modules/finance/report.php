<?php
/**
 * Financial Reports – Daily/Monthly income and expenses.
 */
require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle  = 'Financial Reports';
$activePage = 'report';
$basePath   = '../../';

$pdo = getDBConnection();

// Filter
$period = $_GET['period'] ?? 'daily';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

if ($period === 'monthly') {
    $groupBy = "DATE_FORMAT(created_at, '%Y-%m')";
    $dateLabel = 'Month';
} else {
    $groupBy = 'DATE(created_at)';
    $dateLabel = 'Date';
}

// Summary data
$sql = "SELECT
            $groupBy AS period_label,
            SUM(CASE WHEN type='inflow' THEN total_amount ELSE 0 END) AS income,
            SUM(CASE WHEN type='outflow' THEN total_amount ELSE 0 END) AS expense,
            COUNT(*) AS txn_count
        FROM transactions
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY period_label
        ORDER BY period_label DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$dateFrom, $dateTo]);
$rows = $stmt->fetchAll();

// Totals
$totalIncome  = array_sum(array_column($rows, 'income'));
$totalExpense = array_sum(array_column($rows, 'expense'));
$netProfit    = $totalIncome - $totalExpense;

// Detailed transactions
$detailSql = "SELECT t.transaction_id, t.type, t.quantity, t.unit_price, t.total_amount, t.description, t.created_at,
                     m.name AS medicine_name
              FROM transactions t
              LEFT JOIN medicines m ON t.medicine_id = m.id
              WHERE DATE(t.created_at) BETWEEN ? AND ?
              ORDER BY t.created_at DESC
              LIMIT 100";
$detStmt = $pdo->prepare($detailSql);
$detStmt->execute([$dateFrom, $dateTo]);
$details = $detStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Filters -->
<div class="glass" style="padding:1rem;margin-bottom:1rem;">
  <form method="GET" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;">
    <div class="form-group" style="margin:0;">
      <label class="form-label">Period</label>
      <select name="period" class="form-select" style="width:auto;">
        <option value="daily" <?php echo $period==='daily'?'selected':''; ?>>Daily</option>
        <option value="monthly" <?php echo $period==='monthly'?'selected':''; ?>>Monthly</option>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label">From</label>
      <input type="date" name="date_from" class="form-input" value="<?php echo e($dateFrom); ?>" style="width:auto;">
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label">To</label>
      <input type="date" name="date_to" class="form-input" value="<?php echo e($dateTo); ?>" style="width:auto;">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
  </form>
</div>

<!-- Summary Cards -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
  <div class="stat-card glass">
    <div class="stat-icon green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg></div>
    <div class="stat-info"><h3>Total Income</h3><p style="color:var(--success);"><?php echo formatCurrency($totalIncome); ?></p></div>
  </div>
  <div class="stat-card glass">
    <div class="stat-icon rose"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181"/></svg></div>
    <div class="stat-info"><h3>Total Expenses</h3><p style="color:var(--danger);"><?php echo formatCurrency($totalExpense); ?></p></div>
  </div>
  <div class="stat-card glass">
    <div class="stat-icon <?php echo $netProfit >= 0 ? 'blue' : 'amber'; ?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg></div>
    <div class="stat-info"><h3>Net Profit</h3><p style="color:<?php echo $netProfit>=0?'var(--info)':'var(--danger)'; ?>;"><?php echo formatCurrency($netProfit); ?></p></div>
  </div>
</div>

<!-- Period Summary Table -->
<div class="glass" style="padding:1.25rem;margin-bottom:1rem;">
  <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;"><?php echo $dateLabel; ?> Summary</h3>
  <?php if (empty($rows)): ?>
    <div class="empty-state"><p>No transactions found for the selected period.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th><?php echo $dateLabel; ?></th><th>Transactions</th><th>Income</th><th>Expenses</th><th>Net</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r):
            $net = $r['income'] - $r['expense'];
          ?>
          <tr>
            <td style="font-weight:600;"><?php echo e($r['period_label']); ?></td>
            <td><?php echo (int)$r['txn_count']; ?></td>
            <td style="color:var(--success);"><?php echo formatCurrency($r['income']); ?></td>
            <td style="color:var(--danger);"><?php echo formatCurrency($r['expense']); ?></td>
            <td style="font-weight:700;color:<?php echo $net>=0?'var(--info)':'var(--danger)'; ?>;"><?php echo formatCurrency($net); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Detailed Transactions -->
<div class="glass" style="padding:1.25rem;">
  <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">Transaction Details</h3>
  <?php if (empty($details)): ?>
    <div class="empty-state"><p>No transactions to display.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>ID</th><th>Type</th><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($details as $d): ?>
          <tr>
            <td style="font-family:monospace;font-size:.75rem;"><?php echo e($d['transaction_id']); ?></td>
            <td><?php echo $d['type']==='inflow'?'<span class="badge badge-success">Sale</span>':'<span class="badge badge-danger">Expense</span>'; ?></td>
            <td><?php echo e($d['medicine_name'] ?? $d['description'] ?? '—'); ?></td>
            <td><?php echo (int)$d['quantity']; ?></td>
            <td><?php echo formatCurrency($d['unit_price']); ?></td>
            <td style="font-weight:600;"><?php echo formatCurrency($d['total_amount']); ?></td>
            <td style="font-size:.8rem;color:var(--text-secondary);"><?php echo date('d M Y H:i', strtotime($d['created_at'])); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
