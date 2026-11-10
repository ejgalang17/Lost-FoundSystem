<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$title = 'Notifications';
$rows = null;

try {
    // Clicking a notification marks it read and opens the right staff page.
    $go = (int)($_GET['go'] ?? 0);
    if ($go) {
        $s = db()->prepare('SELECT type FROM notifications WHERE id = ?');
        $s->execute([$go]);
        if ($n = $s->fetch()) {
            db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([$go]);
            redirect($n['type'] === 'claim' ? 'admin/claims.php' : 'admin/review.php');
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        db()->query('UPDATE notifications SET is_read = 1 WHERE is_read = 0');
        flash('All notifications marked as read.');
        redirect('admin/notifications.php');
    }
    $rows = db()->query('SELECT * FROM notifications ORDER BY id DESC LIMIT 50')->fetchAll();
} catch (PDOException $ex) {
    $rows = null;
}
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="panel">
  <div class="row"><h2 style="margin:0;flex:1">Notifications</h2>
    <?php if ($rows && $unread): ?>
    <form method="post"><?= csrf_field() ?><button class="btn alt sm" type="submit">Mark all as read</button></form>
    <?php endif; ?>
  </div>
  <?php if ($rows === null): ?>
    <div class="flash err">The notifications table is missing. Import <code>database/migration_notifications.sql</code> in phpMyAdmin, then refresh.</div>
  <?php elseif (!$rows): ?>
    <div class="empty">No notifications yet. You will be alerted here when a resident posts a report or sends a claim request.</div>
  <?php endif; ?>
  <div class="list">
  <?php foreach ($rows ?: [] as $n): ?>
    <a class="entry notif-row <?= $n['is_read'] ? '' : 'unread' ?>" href="?go=<?= (int)$n['id'] ?>">
      <span class="row"><span class="badge <?= $n['type'] === 'claim' ? 's-claimed' : 's-pending' ?>"><?= $n['type'] === 'claim' ? 'Claim request' : 'New report' ?></span>
        <?php if (!$n['is_read']): ?><span class="dot" title="Unread"></span><?php endif; ?></span>
      <span><?= e($n['message']) ?></span>
      <span class="meta"><?= e($n['created_at']) ?></span>
    </a>
  <?php endforeach; ?>
  </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
