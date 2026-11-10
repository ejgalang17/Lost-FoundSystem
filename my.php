<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();
$title = 'My activity';
$me = current_user();

$s = db()->prepare('SELECT * FROM items WHERE reporter_id = ? ORDER BY created_at DESC');
$s->execute([$me['id']]);
$mine = $s->fetchAll();

$s = db()->prepare('SELECT c.status AS claim_status, c.proof, i.id, i.title FROM claims c JOIN items i ON i.id = c.item_id WHERE c.claimant_id = ? ORDER BY c.created_at DESC');
$s->execute([$me['id']]);
$claims = $s->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
  <h2>My reports</h2>
  <?php if (!$mine): ?><div class="empty">You have not reported anything yet. <a href="<?= e(url('report.php')) ?>">Report an item</a>.</div><?php endif; ?>
  <div class="list">
  <?php foreach ($mine as $i): ?>
    <div class="entry"><b><a href="<?= e(url('item.php?id=' . $i['id'])) ?>"><?= e($i['title']) ?></a></b>
      <span class="meta"><?= $i['type'] === 'lost' ? 'Lost' : 'Found' ?> · <?= e($i['location']) ?> · <?= e($i['date_reported']) ?></span>
      <div><?= badge($i['status']) ?></div>
      <?php if ($i['status'] === 'rejected'): ?><span class="note">Reason: <?= e($i['reject_reason'] ?: 'No reason given.') ?></span><?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
</section>
<section class="panel">
  <h2>My claim requests</h2>
  <?php if (!$claims): ?><div class="empty">No claim requests yet. Find your item in <a href="<?= e(url('index.php')) ?>">Browse items</a>.</div><?php endif; ?>
  <div class="list">
  <?php foreach ($claims as $c): ?>
    <div class="entry"><b><a href="<?= e(url('item.php?id=' . $c['id'])) ?>"><?= e($c['title']) ?></a></b>
      <span class="meta">Your proof: <?= e($c['proof']) ?></span>
      <div><span class="badge s-<?= e($c['claim_status'] === 'approved' ? 'claimed' : ($c['claim_status'] === 'rejected' ? 'rejected' : 'pending')) ?>">Claim <?= e($c['claim_status']) ?></span></div>
    </div>
  <?php endforeach; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
