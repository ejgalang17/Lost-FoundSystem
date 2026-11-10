<?php
const STATUS = [
    'pending' => 'Pending review', 'listed' => 'Listed', 'claim_pending' => 'Claim pending',
    'claimed' => 'Claimed', 'closed' => 'Closed', 'rejected' => 'Rejected',
];

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p) { header('Location: ' . url($p)); exit; }

function flash(string $msg, string $type = 'ok') { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page, and try again.');
    }
}

function current_user() {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $s = db()->prepare('SELECT id, full_name, email, role FROM users WHERE id = ?');
            $s->execute([$_SESSION['uid']]);
            $u = $s->fetch() ?: null;
        }
    }
    return $u;
}
function is_admin(): bool { $u = current_user(); return $u && $u['role'] === 'admin'; }
function require_login() {
    if (!current_user()) { flash('Please log in first.', 'err'); redirect('login.php'); }
}
function require_admin() {
    require_login();
    if (!is_admin()) { http_response_code(403); exit('This page is for barangay staff only.'); }
}

function log_action(string $action, ?int $item = null) {
    $u = current_user();
    db()->prepare('INSERT INTO activity_log (user_id, item_id, action) VALUES (?,?,?)')
        ->execute([$u['id'] ?? null, $item, $action]);
}

function badge(string $s): string {
    return '<span class="badge s-' . e($s) . '">' . e(STATUS[$s] ?? $s) . '</span>';
}
function categories(): array {
    return db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
}

/** Validates and stores an uploaded photo. Returns the stored file name or null if none was sent. */
function save_photo(array $f): ?string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $msg = 'Photo must be a JPG, PNG or WebP image under 2 MB.';
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > MAX_UPLOAD) throw new RuntimeException($msg);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !getimagesize($f['tmp_name'])) throw new RuntimeException($msg);
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) throw new RuntimeException('Could not save the photo.');
    return $name;
}

function item_card(array $i): string {
    $img = $i['photo'] ? '<img alt="" src="' . e(url('uploads/' . $i['photo'])) . '">' : '<span class="noimg">No photo</span>';
    return '<a class="tag ' . e($i['type']) . '" href="' . e(url('item.php?id=' . $i['id'])) . '">'
        . '<span class="kind">' . ($i['type'] === 'lost' ? 'LOST' : 'FOUND') . '</span>'
        . '<div class="thumb">' . $img . '</div><h3>' . e($i['title']) . '</h3>'
        . '<span class="meta">' . e($i['location']) . ' · ' . e($i['date_reported']) . '</span>' . badge($i['status']) . '</a>';
}

function greeting(): string {
    $h = (int)date('G');
    return $h < 12 ? 'Good morning' : ($h < 18 ? 'Good afternoon' : 'Good evening');
}

/** Adds an in-app notification for all barangay staff. Fails silently if the table is missing. */
function notify_staff(string $type, int $itemId, string $message) {
    try {
        db()->prepare('INSERT INTO notifications (type, item_id, message) VALUES (?,?,?)')
            ->execute([$type, $itemId, mb_substr($message, 0, 200)]);
    } catch (Throwable $x) { /* run database/migration_notifications.sql */ }
}
