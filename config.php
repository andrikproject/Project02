<?php
/**
 * Konfigurasi aplikasi.
 * Ubah nilai-nilai di bawah sesuai kebutuhan Anda.
 */

// Tampilkan error saat development. Set ke false di production.
define('APP_DEBUG', true);

// Nama aplikasi (tampil di header / judul tab).
define('APP_NAME', 'Panduan Tutorial');

// Lokasi file database SQLite.
define('DB_PATH', __DIR__ . '/data/app.db');

// --- Kredensial Admin ---
// Username admin.
define('ADMIN_USER', 'admin');
// Password admin: default "admin123".
// Untuk ganti password, buat hash baru:  php -r "echo password_hash('passwordBaru', PASSWORD_DEFAULT);"
define('ADMIN_PASS_HASH', '$2y$12$RQbLDaeo17MJ9wu.J7oN/.3WgAgkQkOJ/D685Zzu4vs5ZwMjj54RC');

// Zona waktu.
date_default_timezone_set('Asia/Jakarta');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
