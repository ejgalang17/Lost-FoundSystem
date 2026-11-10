<?php
require __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    session_unset();
    session_destroy();
    session_start();
    flash('You have been logged out.');
}
redirect('index.php');
