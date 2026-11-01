<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    go(url('index.php'));
}

rb_logout_user();
session_start();
session_regenerate_id(true);
flash('info', 'You have been signed out. Your bag is waiting for you.');
go(url('index.php'));
