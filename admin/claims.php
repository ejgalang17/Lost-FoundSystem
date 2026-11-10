<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$title = 'Claim requests';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cid = (int)($_POST['claim_id'] ?? 0);
    $act = $_POST['action'] ?? '';
    $me = current_user();
    $pdo = db();
    $s = $pdo->prepare("SELECT * FROM claims WHERE id = ? AND status = 'pending'");
    $s->execute([$cid]);
    $claim = $s->fetch();
    if ($claim && in_array($act, ['approve', 'reject'], true)) {
        $itemId = (int)$claim['item_id'];
        $pdo->beginTransaction();
        try {
            if ($act === 'approve') {
                $pdo->prepare("UPDATE claims SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")->execute([$me['id'], $cid]);
                $pdo->prepare("UPDATE claims SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() WHERE item_id = ? AND id <> ? AND status = 'pending'")->execute([$me['id'], $itemId, $cid]);
                $pdo->prepare("UPDATE items SET status = 'claimed' WHERE id = ?")->execute([$itemId]);
                $pdo->prepare('INSERT INTO messages (item_id, sender_id, body) VALUES (?,?,?)')
                    ->execute([$itemId, $me['id'], 'Claim approved. Please bring a valid ID to the barangay hall to collect the item.']);
            } else {
                $pdo->prepare("UPDATE claims SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")->execute([$me['id'], $cid]);
                $left = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE item_id = ? AND status = 'pending'");
                $left->execute([$itemId]);
                if (!(int)$left->fetchColumn()) $pdo->prepare("UPDATE items SET status = 'listed' WHERE id = ? AND status = 'claim_pending'")->execute([$itemId]);
            }
            $pdo->commit();
            log_action($act === 'approve' ? 'Approved claim' : 'Rejected claim', $itemId);
            flash($act === 'approve' ? 'Claim approved. Item marked as claimed.' : 'Claim rejected.');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            flash('Could not save that change. Please try again.', 'err');
        }
    }
    redirect('admin/claims.php');
}

$claims = db()->query("SELECT c.id, c.proof, c.created_at, i.id AS item_id, i.title, i.type, u.full_name, u.address, u.email
  FROM claims c JOIN items i ON i.id = c.item_id JOIN users u ON u.id = c.claimant_id
  WHERE c.status = 'pending' ORDER BY c.created_at")->fetchAll();
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="panel">
  <h2>Claims to verify (<?= count($claims) ?>)</h2>
  <p class="note">Check the claimant's valid ID in person and compare their proof with the item before approving.</p>
  <?php if (!$claims): ?><div class="empty">No claim requests to verify.</div><?php endif; ?>
  <div class="list">
  <?php foreach ($claims as $c): ?>
    <div class="entry">
      <b><a href="<?= e(url('item.php?id=' . $c['item_id'])) ?>"><?= e($c['title']) ?></a> <span class="meta">(<?= e($c['type']) ?>)</span></b>
      <span class="meta">Claimant: <?= e($c['full_name']) ?> · <?= e($c['address']) ?> · <?= e($c['email']) ?> · <?= e($c['created_at']) ?></span>
      <div>Proof given: <?= e($c['proof']) ?></div>
      <form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="claim_id" value="<?= (int)$c['id'] ?>">
        <button class="btn go" name="action" value="approve">Approve, mark claimed</button>
        <button class="btn no" name="action" value="reject">Reject claim</button>
      </form>
    </div>
  <?php endforeach; ?>
  </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
