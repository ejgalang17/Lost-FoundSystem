<?php
require __DIR__ . '/config.php';
date_default_timezone_set('Asia/Manila');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
