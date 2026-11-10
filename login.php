<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('index.php');
$title = 'Log in';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_SESSION['lock'] ?? 0) > time()) {
        $error = 'Too many failed attempts. Please wait a few minutes and try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $s = db()->prepare('SELECT id, password_hash, role FROM users WHERE email = ?');
        $s->execute([$email]);
        $u = $s->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            unset($_SESSION['fails'], $_SESSION['lock']);
            redirect($u['role'] === 'admin' ? 'admin/index.php' : 'index.php');
        }
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        if ($_SESSION['fails'] >= 5) { $_SESSION['lock'] = time() + 300; $_SESSION['fails'] = 0; }
        $error = 'Email or password is incorrect.';
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="panel narrow">
  <h2>Log in</h2>
  <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <label>Email<input type="email" name="email" required autocomplete="username"></label>
    <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn" type="submit">Log in</button>
  </form>
  <p class="note">New here? <a href="<?= e(url('register.php')) ?>">Create a resident account</a>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
