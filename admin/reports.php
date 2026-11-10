<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$title = 'Reports';

$valid = fn($d) => ($x = DateTime::createFromFormat('Y-m-d', (string)$d)) && $x->format('Y-m-d') === $d;
$from = $valid($_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
$to   = $valid($_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
$range = [$from, $to];

$q = db()->prepare("SELECT i.id, i.type, i.title, c.name AS category, i.location, i.date_reported, i.status, u.full_name AS reporter, i.created_at
  FROM items i JOIN categories c ON c.id = i.category_id JOIN users u ON u.id = i.reporter_id
  WHERE DATE(i.created_at) BETWEEN ? AND ? ORDER BY i.created_at");
$q->execute($range);
$rows = $q->fetchAll();

if (isset($_GET['csv'])) {
    $safe = fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v;   // blocks spreadsheet formula injection
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lostfound-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Type', 'Item', 'Category', 'Location', 'Date', 'Status', 'Reported by', 'Submitted']);
    foreach ($rows as $r) fputcsv($out, array_map($safe, [$r['id'], $r['type'], $r['title'], $r['category'], $r['location'], $r['date_reported'], STATUS[$r['status']], $r['reporter'], $r['created_at']]));
    exit;
}

$byStatus = array_fill_keys(array_keys(STATUS), 0); $byType = ['lost' => 0, 'found' => 0]; $byCat = [];
foreach ($rows as $r) { $byStatus[$r['status']]++; $byType[$r['type']]++; $byCat[$r['category']] = ($byCat[$r['category']] ?? 0) + 1; }
arsort($byCat);
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="panel">
  <h2>Lost and found report</h2>
  <form method="get" class="row noprint">
    <label>From<input type="date" name="from" value="<?= e($from) ?>"></label>
    <label>To<input type="date" name="to" value="<?= e($to) ?>"></label>
    <button class="btn alt" type="submit">Apply</button>
    <a class="btn" href="?<?= e(http_build_query(['from' => $from, 'to' => $to, 'csv' => 1])) ?>">Download CSV</a>
    <button class="btn alt" type="button" onclick="window.print()">Print</button>
  </form>
  <p class="meta">Reports submitted from <?= e($from) ?> to <?= e($to) ?>. Total: <?= count($rows) ?></p>
  <div class="stats">
    <div class="stat"><b><?= $byType['lost'] ?></b>Lost</div>
    <div class="stat"><b><?= $byType['found'] ?></b>Found</div>
    <?php foreach (STATUS as $k => $label): ?><div class="stat"><b><?= $byStatus[$k] ?></b><?= e($label) ?></div><?php endforeach; ?>
  </div>
  <h3>By category</h3>
  <div class="tablewrap"><table><tr><th>Category</th><th>Reports</th></tr>
    <?php foreach ($byCat as $name => $n): ?><tr><td><?= e($name) ?></td><td><?= $n ?></td></tr><?php endforeach; ?>
  </table></div>
  <?php if (!$rows): ?><div class="empty">No reports in this date range.</div><?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
