<?php
require __DIR__ . '/includes/bootstrap.php';
$id = (int)($_GET['id'] ?? 0);

$st = db()->prepare('SELECT i.*, c.name AS category, u.full_name AS reporter
                     FROM items i JOIN categories c ON c.id = i.category_id JOIN users u ON u.id = i.reporter_id
                     WHERE i.id = ?');
$st->execute([$id]);
$item = $st->fetch();

$me = current_user();
$isOwner = $me && $item && (int)$item['reporter_id'] === (int)$me['id'];
if (!$item || (in_array($item['status'], ['pending', 'rejected'], true) && !$isOwner && !is_admin())) {
    http_response_code(404);
    exit('Item not found.');
}

$myClaim = null;
if ($me) {
    $s = db()->prepare('SELECT * FROM claims WHERE item_id = ? AND claimant_id = ?');
    $s->execute([$id, $me['id']]);
    $myClaim = $s->fetch() ?: null;
}
$participant = $me && ($isOwner || $myClaim || is_admin());
$live = !in_array($item['status'], ['pending', 'rejected'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require_login();
    $act = $_POST['action'] ?? '';

    if ($act === 'claim') {
        $proof = trim($_POST['proof'] ?? '');
        if (!in_array($item['status'], ['listed', 'claim_pending'], true) || $isOwner || is_admin() || $myClaim) {
            flash('You cannot send a claim request for this item.', 'err');
        } elseif (mb_strlen($proof) < 10 || mb_strlen($proof) > 500) {
            flash('Describe your proof in 10 to 500 characters.', 'err');
        } else {
            db()->prepare('INSERT INTO claims (item_id, claimant_id, proof) VALUES (?,?,?)')->execute([$id, $me['id'], $proof]);
            db()->prepare("UPDATE items SET status = 'claim_pending' WHERE id = ? AND status = 'listed'")->execute([$id]);
            log_action('Sent claim request', $id);
            notify_staff('claim', $id, 'New claim request for "' . $item['title'] . '" from ' . $me['full_name']);
            flash('Claim request sent. Staff will verify it and contact you.');
        }
    } elseif ($act === 'message') {
        $body = trim($_POST['body'] ?? '');
        if (!$participant || !$live) {
            flash('You cannot message on this item.', 'err');
        } elseif ($body === '' || mb_strlen($body) > 500) {
            flash('Write a message of up to 500 characters.', 'err');
        } else {
            db()->prepare('INSERT INTO messages (item_id, sender_id, body) VALUES (?,?,?)')->execute([$id, $me['id'], $body]);
        }
    }
    redirect('item.php?id=' . $id);
}

$msgs = [];
if ($participant && $live) {
    $s = db()->prepare('SELECT m.body, m.created_at, u.full_name, u.role FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.item_id = ? ORDER BY m.id');
    $s->execute([$id]);
    $msgs = $s->fetchAll();
}
$canClaim = $me && !$isOwner && !is_admin() && !$myClaim && in_array($item['status'], ['listed', 'claim_pending'], true);
$title = $item['title'];
require __DIR__ . '/includes/header.php';
?>
<section class="panel narrow">
  <div class="row"><span class="kind <?= e($item['type']) ?>"><?= $item['type'] === 'lost' ? 'LOST' : 'FOUND' ?></span> <?= badge($item['status']) ?></div>
  <?php if ($item['photo']): ?><img class="big" alt="Photo of <?= e($item['title']) ?>" src="<?= e(url('uploads/' . $item['photo'])) ?>"><?php endif; ?>
  <h2><?= e($item['title']) ?></h2>
  <p><?= nl2br(e($item['description'])) ?></p>
  <p class="meta"><?= e($item['category']) ?> · <?= e($item['location']) ?> · <?= e($item['date_reported']) ?> · reported by <?= e($item['reporter']) ?></p>
  <?php if ($item['status'] === 'rejected'): ?><div class="flash err">Rejected by staff: <?= e($item['reject_reason'] ?: 'No reason given.') ?></div><?php endif; ?>
  <?php if ($item['status'] === 'pending'): ?><div class="flash">This report is waiting for staff review and is not public yet.</div><?php endif; ?>
  <?php if ($item['status'] === 'claimed'): ?><p class="note">Claim approved. The owner collects the item at the barangay hall with a valid ID.</p><?php endif; ?>

  <?php if ($canClaim): ?>
    <h3>Is this yours?</h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="claim">
      <label><?= $item['type'] === 'found' ? 'Describe something only the owner would know' : 'Tell us where and when you found it' ?>
        <textarea name="proof" required minlength="10" maxlength="500" rows="3"></textarea></label>
      <button class="btn" type="submit">Send claim request</button>
    </form>
  <?php elseif (!$me && in_array($item['status'], ['listed', 'claim_pending'], true)): ?>
    <p><a class="btn" href="<?= e(url('login.php')) ?>">Log in to send a claim request</a></p>
  <?php elseif ($myClaim): ?>
    <div class="flash">Your claim request is <b><?= e($myClaim['status']) ?></b>.</div>
  <?php endif; ?>

  <?php if ($participant && $live): ?>
    <h3>Messages</h3>
    <div class="thread">
      <?php if (!$msgs): ?><span class="note">No messages yet.</span><?php endif; ?>
      <?php foreach ($msgs as $m): ?>
        <div class="msg <?= $m['role'] === 'admin' ? 'staff' : '' ?>"><b><?= e($m['full_name']) ?><?= $m['role'] === 'admin' ? ' (staff)' : '' ?>:</b> <?= e($m['body']) ?>
          <span class="meta"><?= e($m['created_at']) ?></span></div>
      <?php endforeach; ?>
    </div>
    <form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="message">
      <input name="body" required maxlength="500" placeholder="Write a message" aria-label="Message" style="flex:1">
      <button class="btn" type="submit">Send</button>
    </form>
    <p class="note">Barangay staff can read every message on this item. Do not share passwords or payment details.</p>
  <?php elseif ($live && $me): ?>
    <p class="note">Messaging opens for the reporter and people who sent a claim request.</p>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
