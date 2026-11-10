<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();
$title = 'Report an item';
$errors = [];
$v = ['type' => (in_array($_GET['type'] ?? '', ['lost', 'found'], true) ? $_GET['type'] : 'lost'), 'title' => '', 'category_id' => 0, 'description' => '', 'location' => '', 'date_reported' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? $v[$k]);
    $v['category_id'] = (int)$v['category_id'];

    if (!in_array($v['type'], ['lost', 'found'], true)) $errors[] = 'Choose whether the item was lost or found.';
    if ($v['title'] === '' || mb_strlen($v['title']) > 80) $errors[] = 'Enter an item name (up to 80 characters).';
    if (mb_strlen($v['description']) < 10 || mb_strlen($v['description']) > 1000) $errors[] = 'Describe the item in 10 to 1000 characters.';
    if ($v['location'] === '' || mb_strlen($v['location']) > 120) $errors[] = 'Enter where it was lost or found.';
    $d = DateTime::createFromFormat('Y-m-d', $v['date_reported']);
    if (!$d || $d->format('Y-m-d') !== $v['date_reported'] || $v['date_reported'] > date('Y-m-d')) $errors[] = 'Enter a valid date that is not in the future.';
    $s = db()->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
    $s->execute([$v['category_id']]);
    if (!$s->fetchColumn()) $errors[] = 'Choose a category.';

    $photo = null;
    if (!$errors) {
        try { $photo = save_photo($_FILES['photo'] ?? []); } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }
    if (!$errors) {
        db()->prepare('INSERT INTO items (type, title, category_id, description, location, date_reported, photo, reporter_id) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$v['type'], $v['title'], $v['category_id'], $v['description'], $v['location'], $v['date_reported'], $photo, current_user()['id']]);
        $newId = (int)db()->lastInsertId();
        log_action('Submitted ' . $v['type'] . ' report', $newId);
        notify_staff('report', $newId, 'New ' . $v['type'] . ' report: "' . $v['title'] . '" from ' . current_user()['full_name']);
        flash('Submitted. Barangay staff will review your report before it is listed.');
        redirect('my.php');
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="panel narrow">
  <h2>Report an item</h2>
  <p class="note">Do not post ID numbers or full addresses in the description. Staff will ask for those in person.</p>
  <?php foreach ($errors as $er): ?><div class="flash err"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
    <div class="two">
      <label>This item was
        <select name="type">
          <option value="lost" <?= $v['type'] === 'lost' ? 'selected' : '' ?>>Lost by me or someone I know</option>
          <option value="found" <?= $v['type'] === 'found' ? 'selected' : '' ?>>Found by me</option>
        </select></label>
      <label>Category
        <select name="category_id" required>
          <option value="">Choose…</option>
          <?php foreach (categories() as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $v['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select></label>
    </div>
    <label>Item name<input name="title" required maxlength="80" value="<?= e($v['title']) ?>" placeholder="e.g. Black leather wallet"></label>
    <label>Description<textarea name="description" required rows="4" maxlength="1000" placeholder="Color, size, marks, what is inside"><?= e($v['description']) ?></textarea></label>
    <div class="two">
      <label>Where<input name="location" required maxlength="120" value="<?= e($v['location']) ?>" placeholder="Place or purok"></label>
      <label>Date<input type="date" name="date_reported" required max="<?= e(date('Y-m-d')) ?>" value="<?= e($v['date_reported']) ?>"></label>
    </div>
    <label>Photo (optional, JPG, PNG or WebP, up to 2 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
    <button class="btn" type="submit">Submit for review</button>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
