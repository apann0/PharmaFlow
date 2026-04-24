<?php
/**
 * Dashboard – Overview of pharmacy stats and recent transactions.
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$pdo = getDBConnection();

// ─── Stats ───────────────────────────────────────────────
$totalMedicines = $pdo->query('SELECT COUNT(*) FROM medicines')->fetchColumn();
$totalStock     = $pdo->query('SELECT COALESCE(SUM(stock), 0) FROM medicines')->fetchColumn();
$lowStock       = $pdo->query('SELECT COUNT(*) FROM medicines WHERE stock <= 10 AND stock > 0')->fetchColumn();
$expiringSoon   = $pdo->query("SELECT COUNT(*) FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();

// Today's income & expenses
$todayIncome = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM transactions WHERE type='inflow' AND DATE(created_at)=CURDATE()")->fetchColumn();
$todayExpense = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM transactions WHERE type='outflow' AND DATE(created_at)=CURDATE()")->fetchColumn();

// This month totals
$monthIncome = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM transactions WHERE type='inflow' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn();
$monthExpense = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM transactions WHERE type='outflow' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn();

// Recent transactions (last 10)
$recentTxns = $pdo->query("
    SELECT t.transaction_id, t.type, t.quantity, t.total_amount, t.description, t.created_at,
           m.name AS medicine_name
    FROM transactions t
    LEFT JOIN medicines m ON t.medicine_id = m.id
    ORDER BY t.created_at DESC
    LIMIT 10
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Stats Grid -->
<div class="stat-grid">
  <div class="stat-card glass">
    <div class="stat-icon purple">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
    </div>
    <div class="stat-info">
      <h3>Total Medicines</h3>
      <p><?php echo (int)$totalMedicines; ?></p>
      <small>Total stock: <?php echo number_format((int)$totalStock); ?> units</small>
    </div>
  </div>

  <div class="stat-card glass">
    <div class="stat-icon green">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>
    </div>
    <div class="stat-info">
      <h3>Today's Income</h3>
      <p><?php echo formatCurrency($todayIncome); ?></p>
      <small>This month: <?php echo formatCurrency($monthIncome); ?></small>
    </div>
  </div>

  <div class="stat-card glass">
    <div class="stat-icon rose">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
    </div>
    <div class="stat-info">
      <h3>Today's Expenses</h3>
      <p><?php echo formatCurrency($todayExpense); ?></p>
      <small>This month: <?php echo formatCurrency($monthExpense); ?></small>
    </div>
  </div>

  <div class="stat-card glass">
    <div class="stat-icon amber">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z"/></svg>
    </div>
    <div class="stat-info">
      <h3>Alerts</h3>
      <p><?php echo (int)$lowStock + (int)$expiringSoon; ?></p>
      <small><?php echo (int)$lowStock; ?> low stock · <?php echo (int)$expiringSoon; ?> expiring</small>
    </div>
  </div>
</div>

<!-- Recent Transactions -->
<div class="glass" style="padding:1.25rem;">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
    <h3 style="font-size:1rem;font-weight:700;">Recent Transactions</h3>
    <a href="modules/finance/report.php" class="btn btn-sm btn-secondary">View All</a>
  </div>

  <?php if (empty($recentTxns)): ?>
    <div class="empty-state">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
      <p>No transactions yet. Start by recording a sale or expense.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Transaction ID</th>
            <th>Type</th>
            <th>Medicine</th>
            <th>Qty</th>
            <th>Amount</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentTxns as $txn): ?>
          <tr>
            <td style="font-family:monospace;font-size:.78rem;"><?php echo e($txn['transaction_id']); ?></td>
            <td>
              <?php if ($txn['type'] === 'inflow'): ?>
                <span class="badge badge-success">↑ Sale</span>
              <?php else: ?>
                <span class="badge badge-danger">↓ Expense</span>
              <?php endif; ?>
            </td>
            <td><?php echo e($txn['medicine_name'] ?? $txn['description'] ?? '—'); ?></td>
            <td><?php echo (int)$txn['quantity']; ?></td>
            <td style="font-weight:600;"><?php echo formatCurrency($txn['total_amount']); ?></td>
            <td style="font-size:.8rem;color:var(--text-secondary);"><?php echo date('d M Y H:i', strtotime($txn['created_at'])); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
