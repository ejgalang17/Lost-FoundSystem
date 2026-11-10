<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$title = 'Review reports';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    $me = current_user();
    if ($act === 'approve') { $new = 'listed'; $reason = null; }
    elseif ($act === 'reject') { $new = 'rejected'; $reason = mb_substr(trim($_POST['reason'] ?? ''), 0, 200) ?: null; }
    else { $new = null; }
    if ($new) {
        $s = db()->prepare("UPDATE items SET status = ?, reject_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND status = 'pending'");
        $s->execute([$new, $reason, $me['id'], $id]);
        if ($s->rowCount()) {
            log_action($act === 'approve' ? 'Approved report' : 'Rejected report', $id);
            flash($act === 'approve' ? 'Report approved and listed.' : 'Report rejected.');
        }
    }
    redirect('admin/review.php');
}

$items = db()->query("SELECT i.*, c.name AS category, u.full_name AS reporter, u.address FROM items i
  JOIN categories c ON c.id = i.category_id JOIN users u ON u.id = i.reporter_id
  WHERE i.status = 'pending' ORDER BY i.created_at")->fetchAll();
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="panel">
  <h2>Reports waiting for review (<?= count($items) ?>)</h2>
  <?php if (!$items): ?><div class="empty">All caught up. New reports will show here.</div><?php endif; ?>
  <div class="list">
  <?php foreach ($items as $i): ?>
    <div class="entry">
      <b><?= e($i['title']) ?></b>
      <span class="meta"><?= $i['type'] === 'lost' ? 'Lost' : 'Found' ?> · <?= e($i['category']) ?> · <?= e($i['location']) ?> · <?= e($i['date_reported']) ?> · by <?= e($i['reporter']) ?> (<?= e($i['address']) ?>)</span>
      <div><?= nl2br(e($i['description'])) ?></div>
      <?php if ($i['photo']): ?><img class="big" alt="" src="<?= e(url('uploads/' . $i['photo'])) ?>"><?php endif; ?>
      <form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
        <input name="reason" maxlength="200" placeholder="Reason (needed only if rejecting)" aria-label="Rejection reason" style="flex:1">
        <button class="btn go" name="action" value="approve">Approve and list</button>
        <button class="btn no" name="action" value="reject">Reject</button>
      </form>
    </div>
  <?php endforeach; ?>
  </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
