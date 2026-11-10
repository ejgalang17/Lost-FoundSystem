<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$title = 'Staff dashboard';
$counts = array_fill_keys(array_keys(STATUS), 0);
foreach (db()->query('SELECT status, COUNT(*) n FROM items GROUP BY status') as $r) $counts[$r['status']] = (int)$r['n'];
$users = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'resident'")->fetchColumn();
$recent = db()->query('SELECT a.action, a.created_at, a.item_id, u.full_name FROM activity_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 10')->fetchAll();
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="panel">
  <h2>Overview</h2>
  <div class="stats">
    <div class="stat"><b><?= $counts['pending'] ?></b><a href="review.php">Reports to review</a></div>
    <div class="stat"><b><?= $counts['claim_pending'] ?></b><a href="claims.php">Claims to verify</a></div>
    <div class="stat"><b><?= $counts['listed'] ?></b>Listed</div>
    <div class="stat"><b><?= $counts['claimed'] ?></b>Claimed</div>
    <div class="stat"><b><?= $counts['closed'] ?></b>Closed</div>
    <div class="stat"><b><?= $users ?></b>Residents</div>
  </div>
</section>
<section class="panel">
  <h2>Recent activity</h2>
  <?php if (!$recent): ?><div class="empty">No activity yet.</div><?php endif; ?>
  <div class="list">
  <?php foreach ($recent as $r): ?>
    <div class="entry"><span><?= e($r['full_name'] ?? 'System') ?>: <?= e($r['action']) ?><?= $r['item_id'] ? ' (item #' . (int)$r['item_id'] . ')' : '' ?></span><span class="meta"><?= e($r['created_at']) ?></span></div>
  <?php endforeach; ?>
  </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
