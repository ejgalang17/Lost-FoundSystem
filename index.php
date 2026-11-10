<?php
require __DIR__ . '/includes/bootstrap.php';
$title = 'Browse items';

$q    = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
$cat  = (int)($_GET['cat'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 12;

$w = ["i.status IN ('listed','claim_pending','claimed')"];
$p = [];
if ($q !== '') {
    $w[] = '(i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ?)';
    $like = '%' . $q . '%';
    array_push($p, $like, $like, $like);
}
if (in_array($type, ['lost', 'found'], true)) { $w[] = 'i.type = ?'; $p[] = $type; }
if ($cat) { $w[] = 'i.category_id = ?'; $p[] = $cat; }
$where = implode(' AND ', $w);
$filtered = ($q !== '' || $type !== '' || $cat);

$st = db()->prepare("SELECT COUNT(*) FROM items i WHERE $where");
$st->execute($p);
$total = (int)$st->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page  = min($page, $pages);
$offset = ($page - 1) * $per;

$st = db()->prepare("SELECT i.* FROM items i WHERE $where ORDER BY i.created_at DESC LIMIT $per OFFSET $offset");
$st->execute($p);
$items = $st->fetchAll();

$waiting = (int)db()->query("SELECT COUNT(*) FROM items WHERE status IN ('listed','claim_pending')")->fetchColumn();
$me = current_user();
$first = $me ? explode(' ', trim($me['full_name']))[0] : '';

require __DIR__ . '/includes/header.php';
?>
<section class="home">
  <div class="pill"><?= $waiting ?> item<?= $waiting === 1 ? '' : 's' ?> waiting to be claimed <i>·</i> <a href="<?= e(url('report.php')) ?>">Report an item</a></div>
  <h1 class="greet"><?= e(greeting()) ?><?= $first ? ', ' . e($first) : '' ?></h1>

  <form class="search" method="get" action="<?= e(url('index.php')) ?>" id="search">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by item, place, or description" aria-label="Search">
    <div class="bar2">
      <select name="type" aria-label="Type">
        <option value="">Lost and found</option>
        <option value="lost" <?= $type === 'lost' ? 'selected' : '' ?>>Lost only</option>
        <option value="found" <?= $type === 'found' ? 'selected' : '' ?>>Found only</option>
      </select>
      <select name="cat" aria-label="Category">
        <option value="0">All categories</option>
        <?php foreach (categories() as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="go" type="submit" aria-label="Search"><svg class="ic"><use href="#i-up"/></svg></button>
    </div>
  </form>

  <div class="cap2">Quick start</div>
  <div class="qs">
    <a class="qc" href="<?= e(url('report.php?type=lost')) ?>"><span class="ico"><svg class="ic"><use href="#i-tag"/></svg></span>
      <span><b>Report a lost item</b><span>Describe what you lost and where. Staff will review it before it is listed.</span></span></a>
    <a class="qc" href="<?= e(url('report.php?type=found')) ?>"><span class="ico"><svg class="ic"><use href="#i-box"/></svg></span>
      <span><b>Report a found item</b><span>Turned something in? Add a photo and where you found it.</span></span></a>
    <?php if (is_admin()): ?>
    <a class="qc" href="<?= e(url('admin/review.php')) ?>"><span class="ico"><svg class="ic"><use href="#i-check"/></svg></span>
      <span><b>Review reports</b><span>Approve or reject new reports before they go public.</span></span></a>
    <a class="qc" href="<?= e(url('admin/claims.php')) ?>"><span class="ico"><svg class="ic"><use href="#i-inbox"/></svg></span>
      <span><b>Verify claims</b><span>Check proof and confirm the owner in person.</span></span></a>
    <?php else: ?>
    <a class="qc" href="<?= e(url('index.php?type=found')) ?>"><span class="ico"><svg class="ic"><use href="#i-search"/></svg></span>
      <span><b>Look for my item</b><span>Browse items others have turned in and send a claim request.</span></span></a>
    <a class="qc" href="<?= e(url('my.php')) ?>"><span class="ico"><svg class="ic"><use href="#i-user"/></svg></span>
      <span><b>My activity</b><span>Track your reports and claim requests.</span></span></a>
    <?php endif; ?>
  </div>

  <div class="cap2"><?= $filtered ? 'Results (' . $total . ')' : 'Recently listed' ?></div>
</section>

<div class="grid">
  <?php if (!$items): ?>
    <div class="empty">No listed items match your search. Try fewer words, or report the item so staff can match it later.</div>
  <?php endif; ?>
  <?php foreach ($items as $i) echo item_card($i); ?>
</div>

<?php if ($pages > 1): ?>
<nav class="pager" aria-label="Pages">
  <?php for ($n = 1; $n <= $pages; $n++): ?>
    <a class="<?= $n === $page ? 'on' : '' ?>" href="?<?= e(http_build_query(array_merge($_GET, ['page' => $n]))) ?>"><?= $n ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
