<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$title = 'All records';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $s = db()->prepare("UPDATE items SET status = 'closed' WHERE id = ? AND status = 'claimed'");
    $s->execute([$id]);
    if ($s->rowCount()) { log_action('Closed record', $id); flash('Record closed. The item was handed over.'); }
    redirect('admin/records.php');
}

$status = $_GET['status'] ?? '';
$sql = 'SELECT i.id, i.title, i.type, i.status, i.date_reported, u.full_name FROM items i JOIN users u ON u.id = i.reporter_id';
$p = [];
if (isset(STATUS[$status])) { $sql .= ' WHERE i.status = ?'; $p[] = $status; }
$st = db()->prepare($sql . ' ORDER BY i.created_at DESC LIMIT 200');
$st->execute($p);
$rows = $st->fetchAll();
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="panel">
  <h2>All records</h2>
  <form method="get" class="row">
    <select name="status" aria-label="Status filter">
      <option value="">All statuses</option>
      <?php foreach (STATUS as $k => $label): ?><option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select>
    <button class="btn alt" type="submit">Filter</button>
  </form>
  <div class="tablewrap"><table>
    <tr><th>Item</th><th>Type</th><th>Reported by</th><th>Date</th><th>Status</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= e(url('item.php?id=' . $r['id'])) ?>"><?= e($r['title']) ?></a></td>
      <td><?= e($r['type']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['date_reported']) ?></td><td><?= badge($r['status']) ?></td>
      <td><?php if ($r['status'] === 'claimed'): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn alt sm">Close record</button></form>
      <?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <?php if (!$rows): ?><div class="empty">No records with that status.</div><?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
