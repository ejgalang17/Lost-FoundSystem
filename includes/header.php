<?php
$u = current_user();
$title = $title ?? 'Lost & Found';
$script = basename($_SERVER['SCRIPT_NAME']);
$inAdmin = basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin';
$typeQ = $_GET['type'] ?? '';
$link = function (string $href, string $label, string $icon, bool $on, string $cls = '', int $n = 0) {
    return '<a class="sl ' . $cls . ($on ? ' on' : '') . '" href="' . e(url($href)) . '"><svg class="ic" aria-hidden="true"><use href="#i-' . $icon . '"/></svg>' . e($label) . ($n > 0 ? '<em class="count">' . $n . '</em>' : '') . '</a>';
};
$nu = ['report' => 0, 'claim' => 0]; $unread = 0; $pendingReports = 0; $pendingClaims = 0;
if (is_admin()) {
    try {
        foreach (db()->query('SELECT type, COUNT(*) n FROM notifications WHERE is_read = 0 GROUP BY type') as $r) $nu[$r['type']] = (int)$r['n'];
        $unread = array_sum($nu);
    } catch (Throwable $x) {}
    try {
        $pendingReports = (int)db()->query("SELECT COUNT(*) FROM items WHERE status = 'pending'")->fetchColumn();
        $pendingClaims = (int)db()->query("SELECT COUNT(*) FROM claims WHERE status = 'pending'")->fetchColumn();
    } catch (Throwable $x) {}
}
try {
    $sideRecent = db()->query("SELECT id, title, location FROM items WHERE status IN ('listed','claim_pending','claimed') ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch (Throwable $x) { $sideRecent = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= e(BARANGAY_NAME) ?> Lost &amp; Found</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body>
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
  <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></symbol>
  <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol>
  <symbol id="i-tag" viewBox="0 0 24 24"><path d="M3 12V3h9l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1"/></symbol>
  <symbol id="i-box" viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></symbol>
  <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></symbol>
  <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24"><rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4h6v3H9zM9 14l2 2 4-4"/></symbol>
  <symbol id="i-inbox" viewBox="0 0 24 24"><path d="M4 13l2-8h12l2 8v6H4z"/><path d="M4 13h5l1 2h4l1-2h5"/></symbol>
  <symbol id="i-folder" viewBox="0 0 24 24"><path d="M3 6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></symbol>
  <symbol id="i-chart" viewBox="0 0 24 24"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></symbol>
  <symbol id="i-up" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></symbol>
  <symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M10.3 21a1.9 1.9 0 0 0 3.4 0"/></symbol>
  <symbol id="i-out" viewBox="0 0 24 24"><path d="M9 21H5V3h4M16 17l5-5-5-5M21 12H9"/></symbol>
</svg>
<div class="app">
  <aside class="side">
    <a class="sbrand" href="<?= e(url('index.php')) ?>"><?= e(BARANGAY_NAME) ?><small>Lost &amp; Found</small></a>
    <?= $link('report.php', 'New report', 'plus', $script === 'report.php', 'new') ?>
    <?= $link('index.php', 'Search', 'search', false) ?>
    <?= $u ? $link('my.php', 'My activity', 'user', $script === 'my.php') : '' ?>
    <div class="sep"></div>
    <?= $link('index.php', 'All items', 'grid', !$inAdmin && $script === 'index.php' && $typeQ === '') ?>
    <?= $link('index.php?type=lost', 'Lost items', 'tag', !$inAdmin && $script === 'index.php' && $typeQ === 'lost') ?>
    <?= $link('index.php?type=found', 'Found items', 'box', !$inAdmin && $script === 'index.php' && $typeQ === 'found') ?>
    <?php if (is_admin()): ?>
      <div class="sep"></div>
      <div class="cap">Staff</div>
      <?= $link('admin/notifications.php', 'Notifications', 'bell', $script === 'notifications.php', '', $unread) ?>
      <?= $link('admin/index.php', 'Overview', 'shield', $inAdmin && $script === 'index.php') ?>
      <?= $link('admin/review.php', 'Review reports', 'check', $script === 'review.php', '', $pendingReports) ?>
      <?= $link('admin/claims.php', 'Claim requests', 'inbox', $script === 'claims.php', '', $pendingClaims) ?>
      <?= $link('admin/records.php', 'All records', 'folder', $script === 'records.php') ?>
      <?= $link('admin/reports.php', 'Reports', 'chart', $script === 'reports.php') ?>
    <?php endif; ?>
    <?php if ($sideRecent): ?>
    <div class="recents">
      <div class="sep"></div>
      <div class="cap">Recents</div>
      <?php foreach ($sideRecent as $r): ?>
        <a class="rc" href="<?= e(url('item.php?id=' . $r['id'])) ?>"><b><?= e($r['title']) ?></b><span><?= e($r['location']) ?></span></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($u): ?>
      <div class="me">
        <div class="av"><?= e(mb_strtoupper(mb_substr($u['full_name'], 0, 1))) ?></div>
        <div class="who"><b><?= e($u['full_name']) ?></b><span><?= e($u['email']) ?></span></div>
        <form method="post" action="<?= e(url('logout.php')) ?>" class="inline"><?= csrf_field() ?>
          <button type="submit" class="iconbtn" title="Log out" aria-label="Log out"><svg class="ic"><use href="#i-out"/></svg></button></form>
      </div>
    <?php else: ?>
      <div class="me guest">
        <a class="btn" href="<?= e(url('login.php')) ?>">Log in</a>
        <a class="btn alt" href="<?= e(url('register.php')) ?>">Register</a>
      </div>
    <?php endif; ?>
  </aside>
  <div class="main">
    <div class="topbar"><nav class="tabs" aria-label="Item type">
      <a href="<?= e(url('index.php')) ?>" class="<?= $script === 'index.php' && !$inAdmin && $typeQ === '' ? 'on' : '' ?>">All items</a>
      <a href="<?= e(url('index.php?type=lost')) ?>" class="<?= $script === 'index.php' && !$inAdmin && $typeQ === 'lost' ? 'on' : '' ?>">Lost</a>
      <a href="<?= e(url('index.php?type=found')) ?>" class="<?= $script === 'index.php' && !$inAdmin && $typeQ === 'found' ? 'on' : '' ?>">Found</a>
    </nav></div>
    <main class="content">
<?php foreach (flashes() as [$t, $m]): ?>
      <div class="flash <?= e($t) ?>" role="status"><?= e($m) ?></div>
<?php endforeach; ?>
<?php if ($unread > 0 && $script !== 'notifications.php'): ?>
      <div class="flash notif" role="status"><svg class="ic" aria-hidden="true"><use href="#i-bell"/></svg>
        <span><b><?= $unread ?></b> new notification<?= $unread === 1 ? '' : 's' ?>:
        <?= $nu['report'] ? $nu['report'] . ' new report' . ($nu['report'] === 1 ? '' : 's') . ' to review' : '' ?><?= $nu['report'] && $nu['claim'] ? ', ' : '' ?><?= $nu['claim'] ? $nu['claim'] . ' new claim request' . ($nu['claim'] === 1 ? '' : 's') : '' ?>.</span>
        <a href="<?= e(url('admin/notifications.php')) ?>">View</a></div>
<?php endif; ?>
