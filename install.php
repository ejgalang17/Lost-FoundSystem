<?php
// One-time setup: creates the first barangay staff (admin) account.
// DELETE THIS FILE after you have created the account.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db.php';
session_start();
require __DIR__ . '/includes/helpers.php';
$title = 'Install';
$msg = ''; $done = false;
try {
    $has = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
} catch (PDOException $ex) {
    exit('Database not ready. Import database/schema.sql in phpMyAdmin first, then check includes/config.php.');
}
if ($has > 0) {
    $done = true; $msg = 'A staff account already exists. Delete install.php from the server.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['full_name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8) {
        $msg = 'Enter a name, a valid email, and a password of at least 8 characters.';
    } else {
        db()->prepare("INSERT INTO users (full_name, email, address, password_hash, role, consent_at) VALUES (?,?,?,?, 'admin', NOW())")
            ->execute([$name, $email, 'Barangay Hall', password_hash($pw, PASSWORD_DEFAULT)]);
        $done = true; $msg = 'Staff account created. Delete install.php now, then log in.';
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="panel narrow">
  <h2>Create the first staff account</h2>
  <?php if ($msg): ?><div class="flash <?= $done ? 'ok' : 'err' ?>"><?= e($msg) ?></div><?php endif; ?>
  <?php if (!$done): ?>
  <form method="post"><?= csrf_field() ?>
    <label>Full name<input name="full_name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Password (8 or more characters)<input type="password" name="password" required minlength="8"></label>
    <button class="btn" type="submit">Create staff account</button>
  </form>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
