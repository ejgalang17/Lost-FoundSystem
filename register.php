<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('index.php');
$title = 'Register';
$errors = [];
$v = ['full_name' => '', 'email' => '', 'address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $v['full_name'] = trim($_POST['full_name'] ?? '');
    $v['email']     = strtolower(trim($_POST['email'] ?? ''));
    $v['address']   = trim($_POST['address'] ?? '');
    $pw  = $_POST['password'] ?? '';
    $pw2 = $_POST['password2'] ?? '';

    if (mb_strlen($v['full_name']) < 3 || mb_strlen($v['full_name']) > 80) $errors[] = 'Enter your full name (3 to 80 characters).';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($v['address'] === '' || mb_strlen($v['address']) > 160) $errors[] = 'Enter your home address in this barangay.';
    if (strlen($pw) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pw !== $pw2) $errors[] = 'Passwords do not match.';
    if (empty($_POST['consent'])) $errors[] = 'You must agree to the data privacy notice to register.';

    if (!$errors) {
        $s = db()->prepare('SELECT id FROM users WHERE email = ?');
        $s->execute([$v['email']]);
        if ($s->fetch()) $errors[] = 'That email is already registered. Try logging in.';
    }
    if (!$errors) {
        db()->prepare('INSERT INTO users (full_name, email, address, password_hash, consent_at) VALUES (?,?,?,?,NOW())')
            ->execute([$v['full_name'], $v['email'], $v['address'], password_hash($pw, PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)db()->lastInsertId();
        flash('Welcome, ' . $v['full_name'] . '. Your account is ready.');
        redirect('index.php');
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="panel narrow">
  <h2>Create a resident account</h2>
  <?php foreach ($errors as $er): ?><div class="flash err"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post"><?= csrf_field() ?>
    <label>Full name<input name="full_name" required maxlength="80" value="<?= e($v['full_name']) ?>"></label>
    <label>Email<input type="email" name="email" required maxlength="120" value="<?= e($v['email']) ?>"></label>
    <label>Home address in this barangay<input name="address" required maxlength="160" value="<?= e($v['address']) ?>"></label>
    <label>Password (8 or more characters)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    <label>Confirm password<input type="password" name="password2" required minlength="8" autocomplete="new-password"></label>
    <label class="check"><input type="checkbox" name="consent" value="1" required>
      I agree that the barangay may collect and store my name, email, address, and the item details I submit to run this service, as described under the Data Privacy Act of 2012.</label>
    <button class="btn" type="submit">Create account</button>
  </form>
  <p class="note">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
